@extends('layouts/layout')

@section("content")

<div class="card payment-card">
    @if ($errors->any())
        <div class="form-errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="payment-overview">
        <div class="payment-overview-icon"><i class="fa-solid fa-wallet"></i></div>
        <span class="payment-overview-label">Итоговая оплата</span>
        <strong>{{ number_format($total, 2, '.', '') }} <small>MDL</small></strong>
        <p>{{ $car->brand }} · {{ $car->number_plate }}</p>
        <div class="payment-secure"><i class="fa-solid fa-shield-halved"></i> Защищённая оплата</div>
    </section>

    <form action="{{ route('payment.process') }}" method="POST" class="payment-form">
        @csrf

        <input type="hidden" name="car_id" value="{{ old('car_id', $car->id) }}">
        <input type="hidden" name="start_time" value="{{ old('start_time', $startTime->format('Y-m-d H:i')) }}">
        <input type="hidden" name="end_time" value="{{ old('end_time', $endTime->format('Y-m-d H:i')) }}">
        <input type="hidden" name="branch" value="{{ old('branch', $branch) }}">

        <h1>Данные карты</h1>

        <div class="form-group">
            <label>Имя на карте:</label>
            <input type="text" name="card_name" class="btn-min" placeholder="Ivan Petrov" value="{{ old('card_name') }}" required>
        </div>

        <div class="form-group">
            <label>Номер карты:</label>
            <input type="text" name="card_number" class="btn-min" placeholder="1234 5678 9012 3456" value="{{ old('card_number') }}" required>
        </div>

        <div class="form-group">
            <label>Срок действия:</label>
            <input type="text" name="expiry" class="btn-min" placeholder="MM/YY" value="{{ old('expiry') }}" required>
        </div>

        <div class="form-group">
            <label>CVC:</label>
            <input type="password" name="cvc" class="btn-min" placeholder="123" required>
        </div>

        <div class="submit-block">
            <button type="submit" class="btn">ОПЛАТИТЬ</button>
        </div>

    </form>
</div>

@endsection
