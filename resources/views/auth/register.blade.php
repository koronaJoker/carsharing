@extends ('layouts/layout')

@section("content")

@if ($errors->any())

<div class="card error-block">

    <ul>
        @foreach ($errors->all() as $error)

            <li>{{ $error }}</li>

        @endforeach
    </ul>

</div>

@endif

<div class="card">
    <form action="{{ route('register') }}" method = "POST">
        @csrf
        <div class="title">Регистрация</div>
        <div class = "form-group">
            <label for="name">Имя: </label>
            <input id="name" name="name" type="text" class="btn-min" placeholder="Андрей Галацан" value="{{ old('name') }}" required>
        </div>

        <div class = "form-group">
            <label for="email">Почта:</label>
            <input id="email" name="email" type="email" class="btn-min" placeholder="andrei222@gmail.com" value="{{ old('email') }}" required>
        </div>

          <div class = "form-group">
            <label for="phone">Телефон:</label>
            <input id="phone" name="phone" type="tel" class="btn-min" placeholder="+37367676767" value="{{ old('phone') }}" required>
        </div>


        <div class = "form-group">
            <label for="idnp">Idnp (13 цифр):</label>
            <input id="idnp" name="idnp" type="text" class="btn-min" placeholder="2000000000000" value="{{ old('idnp') }}" required>
        </div>

                <div class = "form-group">
            <label for="driver_license">Водительские права:</label>
            <input id="driver_license" name="driver_license" type="text" class="btn-min" placeholder="MD123456" value="{{ old('driver_license') }}" required>
        </div>

        <div class = "form-group">
            <label for="password">Пароль:</label>
            <input id="password" name="password" type="password" class="btn-min" placeholder="Пароль" required>
        </div>

        <div class = "form-group">
            <label for="confirm-password">Подтвердите пароль:</label>
            <input id="password_confirmation" name="password_confirmation" class="btn-min" type="password" placeholder="Подтвердите пароль" required>
        </div>

        <div class="submit-block">
            <input type="submit" class="btn" value="Зарегистрироваться">
            <span>Уже зарегистрированы? <a href="/login">Войти</a></span>
        </div>
        
    </form>

</div>
@endsection
