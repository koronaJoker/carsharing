<div class="admin-page-head">
    <div>
        <a href="{{ route('admin.index') }}" class="admin-back-link"><i class="fa-solid fa-arrow-left"></i> Админ-панель</a>
        <h1>{{ $config['title'] }}</h1>
    </div>
    @if (! request()->routeIs('admin.records.create'))
        <a href="{{ route('admin.records.create', $resource) }}" class="btn admin-create-btn"><i class="fa-solid fa-plus"></i> Добавить</a>
    @endif
</div>

<nav class="admin-resource-nav" aria-label="Разделы базы данных">
    @foreach (['users' => 'Пользователи', 'cars' => 'Авто', 'rentals' => 'Аренды', 'payments' => 'Платежи', 'fines' => 'Штрафы'] as $resourceKey => $resourceLabel)
        <a href="{{ route('admin.records.index', $resourceKey) }}" class="{{ $resource === $resourceKey ? 'active' : '' }}">{{ $resourceLabel }}</a>
    @endforeach
</nav>

@if (session('success'))
    <div class="form-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="form-errors" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
