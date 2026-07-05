@extends('layouts/layout')

@section('content')

<div class="card admin-card">
    <div class="title">Админ панель</div>

    <div class="admin-stats">
        <div class="admin-stat">
            <h3>Авто</h3>
            <p>{{ $carsCount }}</p>
        </div>
        <div class="admin-stat">
            <h3>Активные аренды</h3>
            <p>{{ $activeRentalsCount }}</p>
        </div>
        <div class="admin-stat">
            <h3>Завершенные</h3>
            <p>{{ $completedRentalsCount }}</p>
        </div>
        <div class="admin-stat">
            <h3>Оплаты</h3>
            <p>{{ number_format($paymentsTotal, 2, '.', '') }} MDL</p>
        </div>
    </div>

    <div class="admin-resource-grid">
        <a href="{{ route('admin.records.index', 'users') }}" class="admin-resource-link">
            <i class="fa-solid fa-users"></i><span>Пользователи</span><strong>{{ $usersCount }}</strong>
        </a>
        <a href="{{ route('admin.records.index', 'cars') }}" class="admin-resource-link">
            <i class="fa-solid fa-car"></i><span>Автомобили</span><strong>{{ $carsCount }}</strong>
        </a>
        <a href="{{ route('admin.records.index', 'rentals') }}" class="admin-resource-link">
            <i class="fa-solid fa-key"></i><span>Аренды</span><strong>{{ $rentalsCount }}</strong>
        </a>
        <a href="{{ route('admin.records.index', 'payments') }}" class="admin-resource-link">
            <i class="fa-solid fa-credit-card"></i><span>Платежи</span><strong>{{ $paymentsCount }}</strong>
        </a>
        <a href="{{ route('admin.records.index', 'fines') }}" class="admin-resource-link">
            <i class="fa-solid fa-triangle-exclamation"></i><span>Штрафы</span><strong>{{ $finesCount }}</strong>
        </a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Авто</th>
                    <th>Период</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                    <tr>
                        <td>{{ $rental->id }}</td>
                        <td>{{ $rental->user->name ?? '-' }}</td>
                        <td>{{ $rental->car->brand ?? '-' }}</td>
                        <td>{{ $rental->start_time->format('d.m H:i') }} - {{ $rental->end_time?->format('d.m H:i') ?? '-' }}</td>
                        <td>{{ number_format((float) $rental->total_cost, 2, '.', '') }} MDL</td>
                        <td>{{ $rental->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Пока нет записей</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
