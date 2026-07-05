@extends ('layouts/layout')

@section("content")

<div class="card">
    <form action="{{ route('login') }}" method = "POST">
        <div class="title">Авторизация</div>

        <div class = "form-group">
            <label for="email">Почта:</label>
            <input name = "email" type="email" class = "btn-min" placeholder = "andrei222@gmail.com">
        </div>


        <div class = "form-group">
            <label for="password">Пароль:</label>
            <input name="password" type="password" class="btn-min" placeholder = "Пароль">
        </div>


        <div class="submit-block">
            <input type="submit" class = "btn">
            <span>Еще не зарегестрированы? <a href="/register">Зарегистрироваться</a></span>
        </div>
    </form>
</div>
@endsection