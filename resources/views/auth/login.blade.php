@extends ('layouts/layout')

@section("content")

<div class="card">
    <form action="{{ route('login') }}" method = "POST">
        @csrf
        <div class="title">Авторизация</div>

        @if ($errors->any())
            <div class="form-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class = "form-group">
            <label for="email">Почта:</label>
            <input id="email" name="email" type="email" class="btn-min" placeholder="andrei222@gmail.com" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>


        <div class = "form-group">
            <label for="password">Пароль:</label>
            <input id="password" name="password" type="password" class="btn-min" placeholder="Пароль" autocomplete="current-password" required>
        </div>


        <div class="submit-block">
            <input type="submit" class="btn" value="Войти">
            <span>Еще не зарегистрированы? <a href="/register">Зарегистрироваться</a></span>
        </div>
    </form>
</div>
@endsection
