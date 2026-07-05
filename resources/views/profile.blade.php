@extends('layouts/layout')

@section('content')

<div class="card profile">

    <div class="profile-header">

        <img class="img-profile"
             src="{{ asset('images/user.png') }}">

        <div class="profile-name">
            <h1>{{ auth()->user()->name }}</h1>
            <span>{{ auth()->user()->email }}</span>
        </div>

    </div>

    <div class="profile-info">

        <div class="profile-item">
            <span>Телефон</span>
            <span>{{ auth()->user()->phone ?? '—' }}</span>
        </div>

        <div class="profile-item">
            <span>Аренд</span>
            <span>{{ auth()->user()->rent_count ?? 0 }}</span>
        </div>

        <div class="profile-item">
            <span>Баланс</span>
            <span>{{ auth()->user()->balance ?? 0 }} лей</span>
        </div>

    </div>

    <div class="profile-actions">

        <a class="btn" href="{{ route('profile.edit') }}">
            Изменить
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn">
                Выйти
            </button>
        </form>

    </div>

    <a class="payment-btn">
        <span>＋</span>
        Добавить способ оплаты
    </a>

</div>

@endsection