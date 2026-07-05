<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\MongoTelemetryService;
use App\Services\RentalPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalController extends Controller
{
    public function __construct(private readonly RentalPricingService $pricingService) {}

    public function create(Request $request)
    {
        $carId = $request->integer('car_id');
        $car = $carId ? Car::find($carId) : null;

        if ($car && $car->status !== 'available') {
            return redirect()->route('cars')->withErrors([
                'car' => 'Этот автомобиль сейчас недоступен для аренды.',
            ]);
        }

        return view('rent', [
            'title' => 'Бронирование',
            'active' => 'rent',
            'car' => $car,
        ]);
    }

    public function showPayment(Request $request)
    {
        $validated = $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'branch' => ['nullable', 'string', 'max:100'],
        ]);

        $car = Car::findOrFail($validated['car_id']);
        $this->ensureCarIsAvailable($car);

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $minutes = $start->diffInMinutes($end, false);

        if ($minutes <= 0) {
            return back()->withInput()->withErrors([
                'end_time' => 'Время окончания должно быть позже времени начала.',
            ]);
        }

        $request->session()->put('payment_preview', $validated);

        return redirect()->route('payment.show');
    }

    public function payment(Request $request)
    {
        $preview = $request->session()->get('payment_preview');

        if (! is_array($preview) || empty($preview['car_id'])) {
            return redirect()->route('cars');
        }

        $car = Car::findOrFail($preview['car_id']);
        $this->ensureCarIsAvailable($car);
        $start = Carbon::parse($preview['start_time']);
        $end = Carbon::parse($preview['end_time']);
        $minutes = $start->diffInMinutes($end, false);
        $pricePerMinute = (float) $car->price_per_minute;
        $total = $this->pricingService->calculate($minutes, $pricePerMinute);

        return view('payment', [
            'title' => 'Оплата',
            'active' => 'payment',
            'car' => $car,
            'branch' => $preview['branch'] ?? null,
            'startTime' => $start,
            'endTime' => $end,
            'minutes' => $minutes,
            'pricePerMinute' => $pricePerMinute,
            'total' => $total,
        ]);
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'branch' => ['nullable', 'string', 'max:100'],
            'card_name' => ['required', 'string', 'max:100'],
            'card_number' => ['required', 'string', 'max:25'],
            'expiry' => ['required', 'string', 'max:10'],
            'cvc' => ['required', 'string', 'max:4'],
        ]);

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $minutes = $start->diffInMinutes($end, false);

        if ($minutes <= 0) {
            return back()->withInput()->withErrors([
                'end_time' => 'Время окончания должно быть позже времени начала.',
            ]);
        }

        $rental = DB::transaction(function () use ($validated, $start, $end, $minutes) {
            $car = Car::query()->lockForUpdate()->findOrFail($validated['car_id']);
            $this->ensureCarIsAvailable($car);
            $total = $this->pricingService->calculate($minutes, (float) $car->price_per_minute);

            $rental = Rental::create([
                'user_id' => auth()->id(),
                'car_id' => $car->id,
                'start_time' => $start,
                'end_time' => $end,
                'total_cost' => $total,
                'status' => 'active',
            ]);

            Payment::create([
                'rental_id' => $rental->id,
                'amount' => $total,
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $car->status = 'busy';
            $car->save();

            return $rental;
        });

        $request->session()->forget('payment_preview');

        app(MongoTelemetryService::class)->logEvent(
            'rental_started',
            'Rental started after successful payment',
            [
                'rental_id' => (int) $rental->id,
                'car_id' => (int) $rental->car_id,
                'user_id' => (int) $rental->user_id,
                'total_cost' => (float) $rental->total_cost,
            ]
        );

        return redirect()
            ->route('rentals.active', $rental)
            ->with('success', 'Оплата успешна. Аренда активирована.');
    }

    public function active(Rental $rental)
    {
        $this->authorizeRentalAccess($rental);

        return view('rental-active', [
            'title' => 'Активная аренда',
            'active' => 'rent',
            'rental' => $rental->load('car'),
        ]);
    }

    public function command(Request $request, Rental $rental)
    {
        $this->authorizeRentalAccess($rental);

        $validated = $request->validate([
            'action' => ['required', 'in:warmup,toggle_lock,flash'],
        ]);

        $messages = [
            'warmup' => 'Прогрев автомобиля запущен.',
            'flash' => 'Фары мигают — автомобиль проще найти.',
        ];

        if ($validated['action'] === 'toggle_lock') {
            $lockSessionKey = 'rentals.'.$rental->id.'.locked';
            $isLocked = ! session($lockSessionKey, true);
            session([$lockSessionKey => $isLocked]);
            $messages['toggle_lock'] = $isLocked ? 'Двери закрыты.' : 'Двери открыты.';
        }

        app(MongoTelemetryService::class)->logEvent(
            'car_command',
            'Command sent to car',
            [
                'action' => $validated['action'],
                'rental_id' => (int) $rental->id,
                'car_id' => (int) $rental->car_id,
                'user_id' => (int) $rental->user_id,
            ]
        );

        return back()->with([
            'command_status' => $messages[$validated['action']],
            'command_action' => $validated['action'],
        ]);
    }

    public function finish(Request $request, Rental $rental)
    {
        $this->authorizeRentalAccess($rental);

        $request->validate([
            'confirm_finish' => ['required', 'accepted'],
        ]);

        if ($rental->status !== 'completed') {
            $rental->status = 'completed';
            $rental->end_time = now();
            $rental->save();

            $car = $rental->car;
            if ($car) {
                $car->status = 'available';
                $car->save();
            }

            app(MongoTelemetryService::class)->logEvent(
                'rental_completed',
                'Rental completed by user confirmation',
                [
                    'rental_id' => (int) $rental->id,
                    'car_id' => (int) $rental->car_id,
                    'user_id' => (int) $rental->user_id,
                ]
            );
        }

        return redirect()->route('cars')->with('success', 'Поездка завершена. Спасибо за поездку.');
    }

    private function authorizeRentalAccess(Rental $rental): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->id !== $rental->user_id && $user->role !== 'admin') {
            abort(403);
        }
    }

    private function ensureCarIsAvailable(Car $car): void
    {
        if ($car->status !== 'available') {
            throw ValidationException::withMessages([
                'car_id' => 'Этот автомобиль уже занят или находится на обслуживании.',
            ]);
        }
    }
}
