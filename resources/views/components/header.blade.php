<header>
    <div class="logo">
        <h1>
            <i class="fa-solid fa-car"></i>
            AutoPoint
        </h1>
    </div>

    <nav>
        <ul>
            <li><a class="{{ $active === 'home' ? 'active' : '' }}" href="/">Главная</a></li>
            <li><a class="{{ $active === 'rent' ? 'active' : '' }}" href="/rent">Бронь</a></li>
            <li><a href="/cars" class = " {{ $active === 'cars' ? 'active' : '' }}">Авто</a></li>
            @auth
            <li>
                <a href="{{ route('profile') }}" class = " {{ $active === 'profile' ? 'active' : '' }}">
                 Профиль
                </a>
            </li>
            @if(auth()->user()->role === 'admin')
            <li>
                <a href="{{ route('admin.index') }}" class="{{ $active === 'admin' ? 'active' : '' }}">
                    Админ
                </a>
            </li>
            @endif
            @endauth

            @guest
                <li>
                    <a class = "btn-min" href="{{ route('login') }}">
                        Войти
                    </a>
                </li>
            @endguest

        </ul>
    </nav>
</header>