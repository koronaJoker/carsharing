@extends ('layouts/layout')

@section("content")

<div class="card">
    <form action="{{ route('register') }}" method = "POST">
        @csrf
        <div class="title">Регистрация</div>
        <div class = "form-group">
            <label for="name">Имя: </label>
            <input name = "name" type="text" class="btn-min" placeholder = "Андрей Галацан">
        </div>

        <div class = "form-group">
            <label for="email">Почта:</label>
            <input name = "email" type="email" class = "btn-min" placeholder = "andrei222@gmail.com">
        </div>

        <div class = "form-group">
            <label for="idnp">Idnp (13 цифр):</label>
            <input name = "idnp" type="text" class="btn-min" placeholder = "999 999 999 999 9">
        </div>

                <div class = "form-group">
            <label for="idnp">Водительские права:</label>
            <input name = "idnp" type="text" class="btn-min" placeholder = "999 999 999 999 9">
        </div>

        <div class = "form-group">
            <label for="password">Пароль:</label>
            <input name="password" type="password" class="btn-min" placeholder = "Пароль">
        </div>

        <div class = "form-group">
            <label for="confirm-password">Подтвердите пароль:</label>
            <input name = "password-confirmation" name="idnp" class = "btn-min" type="password" placeholder = "Подтвердите Пароль">
        </div>

        <div class="submit-block">
            <input type="submit" class = "btn">
            <span>Уже зарегестрированы? <a href="/login">войти</a></span>
        </div>
        
    </form>

</div>
@endsection