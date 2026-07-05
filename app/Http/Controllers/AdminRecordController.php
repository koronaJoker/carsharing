<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Fine;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRecordController extends Controller
{
    public function index(string $resource): View
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);
        $model = $config['model'];
        $records = $model::query()
            ->with($config['with'])
            ->latest('id')
            ->paginate(20);

        return view('admin.records.index', $this->viewData($resource, $config, [
            'records' => $records,
        ]));
    }

    public function create(string $resource): View
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);

        return view('admin.records.form', $this->viewData($resource, $config, [
            'record' => null,
            'options' => $this->options($resource),
        ]));
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);
        $data = $this->validatedData($request, $resource);
        $model = $config['model'];

        $record = DB::transaction(function () use ($model, $resource, $data) {
            $record = $model::create($this->normalizeData($resource, $data));
            $this->syncRelatedState($resource, $record);

            return $record;
        });

        return redirect()
            ->route('admin.records.show', [$resource, $record])
            ->with('success', 'Запись успешно создана.');
    }

    public function show(string $resource, int $record): View
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);
        $item = $this->findRecord($config, $record);

        return view('admin.records.show', $this->viewData($resource, $config, [
            'record' => $item,
            'options' => $this->options($resource),
        ]));
    }

    public function edit(string $resource, int $record): View
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);
        $item = $this->findRecord($config, $record);

        return view('admin.records.form', $this->viewData($resource, $config, [
            'record' => $item,
            'options' => $this->options($resource),
        ]));
    }

    public function update(Request $request, string $resource, int $record): RedirectResponse
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);
        $item = $this->findRecord($config, $record);
        $data = $this->validatedData($request, $resource, $item->getKey());
        $previousCarId = $resource === 'rentals' ? (int) $item->car_id : null;

        DB::transaction(function () use ($resource, $item, $data, $previousCarId) {
            $item->update($this->normalizeData($resource, $data, true));
            $this->syncRelatedState($resource, $item);

            if ($previousCarId && $previousCarId !== (int) $item->car_id) {
                $this->releaseCarWhenPossible($previousCarId);
            }
        });

        return redirect()
            ->route('admin.records.show', [$resource, $item])
            ->with('success', 'Изменения сохранены.');
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        $this->authorizeAdmin();
        $config = $this->config($resource);
        $item = $this->findRecord($config, $record);

        if ($resource === 'users' && $item->is(auth()->user())) {
            return back()->withErrors(['record' => 'Нельзя удалить собственную учётную запись.']);
        }

        $carIds = match ($resource) {
            'rentals' => [(int) $item->car_id],
            'users' => $item->rentals()->pluck('car_id')->map(fn ($id) => (int) $id)->all(),
            default => [],
        };

        DB::transaction(function () use ($item, $carIds) {
            $item->delete();

            foreach (array_unique($carIds) as $carId) {
                $this->releaseCarWhenPossible($carId);
            }
        });

        return redirect()
            ->route('admin.records.index', $resource)
            ->with('success', 'Запись удалена.');
    }

    private function config(string $resource): array
    {
        $configs = [
            'users' => [
                'title' => 'Пользователи',
                'model' => User::class,
                'with' => [],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'name', 'label' => 'Имя'],
                    ['key' => 'email', 'label' => 'Email'],
                    ['key' => 'phone', 'label' => 'Телефон'],
                    ['key' => 'role', 'label' => 'Роль'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Имя', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'phone', 'label' => 'Телефон', 'type' => 'text'],
                    ['name' => 'idnp', 'label' => 'IDNP', 'type' => 'text'],
                    ['name' => 'driver_license', 'label' => 'Водительские права', 'type' => 'text'],
                    ['name' => 'role', 'label' => 'Роль', 'type' => 'select', 'choices' => ['user' => 'Пользователь', 'admin' => 'Администратор']],
                    ['name' => 'email_verified_at', 'label' => 'Email подтверждён', 'type' => 'datetime-local'],
                    ['name' => 'password', 'label' => 'Пароль', 'type' => 'password', 'virtual' => true],
                    ['name' => 'password_confirmation', 'label' => 'Подтверждение пароля', 'type' => 'password', 'virtual' => true],
                ],
            ],
            'cars' => [
                'title' => 'Автомобили',
                'model' => Car::class,
                'with' => [],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'brand', 'label' => 'Автомобиль'],
                    ['key' => 'number_plate', 'label' => 'Номер'],
                    ['key' => 'price_per_minute', 'label' => 'Цена', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'Статус'],
                ],
                'fields' => [
                    ['name' => 'brand', 'label' => 'Марка и модель', 'type' => 'text'],
                    ['name' => 'year', 'label' => 'Год', 'type' => 'number'],
                    ['name' => 'number_plate', 'label' => 'Госномер', 'type' => 'text'],
                    ['name' => 'fuel_type', 'label' => 'Топливо', 'type' => 'select', 'choices' => ['petrol' => 'Бензин', 'diesel' => 'Дизель', 'electric' => 'Электро', 'hybrid' => 'Гибрид']],
                    ['name' => 'transmission', 'label' => 'Коробка', 'type' => 'select', 'choices' => ['manual' => 'Механика', 'automatic' => 'Автомат']],
                    ['name' => 'price_per_minute', 'label' => 'Цена за минуту', 'type' => 'number', 'step' => '0.01'],
                    ['name' => 'status', 'label' => 'Статус', 'type' => 'select', 'choices' => ['available' => 'Свободна', 'busy' => 'Занята', 'maintenance' => 'Обслуживание']],
                    ['name' => 'image_url', 'label' => 'Путь или URL изображения', 'type' => 'text'],
                ],
            ],
            'rentals' => [
                'title' => 'Аренды',
                'model' => Rental::class,
                'with' => ['user', 'car'],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'user.name', 'label' => 'Пользователь'],
                    ['key' => 'car.brand', 'label' => 'Автомобиль'],
                    ['key' => 'start_time', 'label' => 'Начало', 'format' => 'datetime'],
                    ['key' => 'total_cost', 'label' => 'Сумма', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'Статус'],
                ],
                'fields' => [
                    ['name' => 'user_id', 'label' => 'Пользователь', 'type' => 'select', 'dynamic' => true],
                    ['name' => 'car_id', 'label' => 'Автомобиль', 'type' => 'select', 'dynamic' => true],
                    ['name' => 'start_time', 'label' => 'Начало', 'type' => 'datetime-local'],
                    ['name' => 'end_time', 'label' => 'Окончание', 'type' => 'datetime-local'],
                    ['name' => 'total_cost', 'label' => 'Стоимость', 'type' => 'number', 'step' => '0.01'],
                    ['name' => 'status', 'label' => 'Статус', 'type' => 'select', 'choices' => ['active' => 'Активна', 'completed' => 'Завершена', 'cancelled' => 'Отменена']],
                ],
            ],
            'payments' => [
                'title' => 'Платежи',
                'model' => Payment::class,
                'with' => ['rental.user'],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'rental_id', 'label' => 'Аренда'],
                    ['key' => 'rental.user.name', 'label' => 'Пользователь'],
                    ['key' => 'amount', 'label' => 'Сумма', 'format' => 'money'],
                    ['key' => 'payment_method', 'label' => 'Метод'],
                    ['key' => 'payment_status', 'label' => 'Статус'],
                ],
                'fields' => [
                    ['name' => 'rental_id', 'label' => 'Аренда', 'type' => 'select', 'dynamic' => true],
                    ['name' => 'amount', 'label' => 'Сумма', 'type' => 'number', 'step' => '0.01'],
                    ['name' => 'payment_method', 'label' => 'Метод', 'type' => 'select', 'choices' => ['card' => 'Карта', 'cash' => 'Наличные', 'bank_transfer' => 'Перевод']],
                    ['name' => 'payment_status', 'label' => 'Статус', 'type' => 'select', 'choices' => ['paid' => 'Оплачен', 'pending' => 'Ожидает', 'failed' => 'Ошибка', 'refunded' => 'Возврат']],
                    ['name' => 'paid_at', 'label' => 'Дата оплаты', 'type' => 'datetime-local'],
                ],
            ],
            'fines' => [
                'title' => 'Штрафы',
                'model' => Fine::class,
                'with' => ['rental.user'],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'rental_id', 'label' => 'Аренда'],
                    ['key' => 'rental.user.name', 'label' => 'Пользователь'],
                    ['key' => 'title', 'label' => 'Причина'],
                    ['key' => 'amount', 'label' => 'Сумма', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'Статус'],
                ],
                'fields' => [
                    ['name' => 'rental_id', 'label' => 'Аренда', 'type' => 'select', 'dynamic' => true],
                    ['name' => 'title', 'label' => 'Название', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Описание', 'type' => 'textarea'],
                    ['name' => 'amount', 'label' => 'Сумма', 'type' => 'number', 'step' => '0.01'],
                    ['name' => 'rating_penalty', 'label' => 'Штраф рейтинга', 'type' => 'number', 'step' => '0.1'],
                    ['name' => 'status', 'label' => 'Статус', 'type' => 'select', 'choices' => ['pending' => 'Ожидает', 'paid' => 'Оплачен', 'cancelled' => 'Отменён']],
                ],
            ],
        ];

        abort_unless(isset($configs[$resource]), 404);

        return $configs[$resource];
    }

    private function validatedData(Request $request, string $resource, ?int $recordId = null): array
    {
        $rules = match ($resource) {
            'users' => [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($recordId)],
                'phone' => ['required', 'regex:/^(\+373|0)[0-9]{8}$/', Rule::unique('users', 'phone')->ignore($recordId)],
                'idnp' => ['required', 'digits:13', Rule::unique('users', 'idnp')->ignore($recordId)],
                'driver_license' => ['nullable', 'string', 'max:30'],
                'role' => ['required', Rule::in(['user', 'admin'])],
                'email_verified_at' => ['nullable', 'date'],
                'password' => [$recordId ? 'nullable' : 'required', 'confirmed', 'min:8'],
            ],
            'cars' => [
                'brand' => ['required', 'string', 'max:50'],
                'year' => ['required', 'integer', 'between:1950,'.(now()->year + 1)],
                'number_plate' => ['required', 'string', 'max:10', Rule::unique('cars', 'number_plate')->ignore($recordId)],
                'fuel_type' => ['required', Rule::in(['petrol', 'diesel', 'electric', 'hybrid'])],
                'transmission' => ['required', Rule::in(['manual', 'automatic'])],
                'price_per_minute' => ['required', 'numeric', 'min:0', 'max:9999.99'],
                'status' => ['required', Rule::in(['available', 'busy', 'maintenance'])],
                'image_url' => ['nullable', 'string', 'max:255'],
            ],
            'rentals' => [
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'car_id' => ['required', 'integer', 'exists:cars,id'],
                'start_time' => ['required', 'date'],
                'end_time' => ['nullable', 'date', 'after:start_time'],
                'total_cost' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['active', 'completed', 'cancelled'])],
            ],
            'payments' => [
                'rental_id' => ['required', 'integer', 'exists:rentals,id'],
                'amount' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', Rule::in(['card', 'cash', 'bank_transfer'])],
                'payment_status' => ['required', Rule::in(['paid', 'pending', 'failed', 'refunded'])],
                'paid_at' => ['nullable', 'date'],
            ],
            'fines' => [
                'rental_id' => ['required', 'integer', 'exists:rentals,id'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:2000'],
                'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
                'rating_penalty' => ['required', 'numeric', 'min:0', 'max:9.9'],
                'status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])],
            ],
        };

        return $request->validate($rules, [
            'required' => 'Поле «:attribute» обязательно.',
            'unique' => 'Такое значение поля «:attribute» уже используется.',
            'exists' => 'Выбранная связанная запись не существует.',
            'after' => 'Дата окончания должна быть позже даты начала.',
        ]);
    }

    private function normalizeData(string $resource, array $data, bool $updating = false): array
    {
        unset($data['password_confirmation']);

        foreach (['email_verified_at', 'end_time', 'paid_at'] as $dateField) {
            if (array_key_exists($dateField, $data) && $data[$dateField] === '') {
                $data[$dateField] = null;
            }
        }

        if ($resource === 'users') {
            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } elseif ($updating) {
                unset($data['password']);
            }
        }

        return $data;
    }

    private function options(string $resource): array
    {
        $options = [];

        if ($resource === 'rentals') {
            $options['user_id'] = User::orderBy('name')->pluck('name', 'id')->all();
            $options['car_id'] = Car::orderBy('brand')->get()->mapWithKeys(
                fn (Car $car) => [$car->id => $car->brand.' · '.$car->number_plate]
            )->all();
        }

        if (in_array($resource, ['payments', 'fines'], true)) {
            $options['rental_id'] = Rental::with(['user', 'car'])->latest('id')->get()->mapWithKeys(
                fn (Rental $rental) => [$rental->id => '#'.$rental->id.' · '.($rental->user?->name ?? '—').' · '.($rental->car?->brand ?? '—')]
            )->all();
        }

        return $options;
    }

    private function syncRelatedState(string $resource, Model $record): void
    {
        if ($resource !== 'rentals' || ! $record instanceof Rental) {
            return;
        }

        if ($record->status === 'active') {
            $record->car()->update(['status' => 'busy']);

            return;
        }

        $this->releaseCarWhenPossible((int) $record->car_id);
    }

    private function releaseCarWhenPossible(int $carId): void
    {
        $hasActiveRental = Rental::where('car_id', $carId)->where('status', 'active')->exists();
        $car = Car::find($carId);

        if (! $hasActiveRental && $car && $car->status === 'busy') {
            $car->update(['status' => 'available']);
        }
    }

    private function findRecord(array $config, int $record): Model
    {
        $model = $config['model'];

        return $model::query()->with($config['with'])->findOrFail($record);
    }

    private function viewData(string $resource, array $config, array $data = []): array
    {
        return array_merge([
            'title' => $config['title'].' — админ-панель',
            'active' => 'admin',
            'resource' => $resource,
            'config' => $config,
        ], $data);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
