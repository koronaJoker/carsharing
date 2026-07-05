@extends("layouts/layout")

@section("content")

<div class="card filter-block">
    <div class="title">Доступно</div>

    <form method="GET" action="{{ route('cars') }}">

        <h1>Выберите автомобиль</h1>

        <div class="flex">

            <div class="form-group">
                <label>Бренд</label>
                <select name="brand">
                    <option value="">Все бренды</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>{{ $brand }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Топливо</label>
                <select name="fuel">
                    <option value="">Любое</option>
                    <option value="petrol" {{ request('fuel') === 'petrol' ? 'selected' : '' }}>Бензин</option>
                    <option value="diesel" {{ request('fuel') === 'diesel' ? 'selected' : '' }}>Дизель</option>
                    <option value="electric" {{ request('fuel') === 'electric' ? 'selected' : '' }}>Электро</option>
                    <option value="hybrid" {{ request('fuel') === 'hybrid' ? 'selected' : '' }}>Гибрид</option>
                </select>
            </div>

            <div class="form-group">
                <label>Коробка</label>
                <select name="transmission">
                    <option value="">Любая</option>
                    <option value="manual" {{ request('transmission') === 'manual' ? 'selected' : '' }}>Механика</option>
                    <option value="automatic" {{ request('transmission') === 'automatic' ? 'selected' : '' }}>Автомат</option>
                </select>
            </div>
            <div class="form-group">
                <label>Год</label>
                <select name="year">
                    <option value="">Любой</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (string) request('year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Статус</label>
                <select name="status">
                    <option value="">Любой</option>
                    <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Свободна</option>
                    <option value="busy" {{ request('status') === 'busy' ? 'selected' : '' }}>Занята</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Обслуживание</option>
                </select>
            </div>

            <div class="form-group">
                <label>Сортировка</label>
                <select name="sort">
                    <option value="">По умолчанию</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Цена ↑</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Цена ↓</option>
                </select>
            </div>

            <div class="form-group price-range-group">
                <label>Цена (лей/мин)</label>
                <div class="price-values">
                    <span id="price-min-label">От: {{ number_format($minPrice, 2, '.', '') }}</span>
                    <span id="price-max-label">До: {{ number_format($maxPrice, 2, '.', '') }}</span>
                </div>
                <input type="range" id="min_price" name="min_price" min="{{ (int) floor($minAllowedPrice) }}" max="{{ (int) ceil($maxAllowedPrice) }}" value="{{ (int) floor($minPrice) }}">
                <input type="range" id="max_price" name="max_price" min="{{ (int) floor($minAllowedPrice) }}" max="{{ (int) ceil($maxAllowedPrice) }}" value="{{ (int) ceil($maxPrice) }}">
            </div>

            <input type="submit" class="btn" value="Фильтровать">

        </div>
    </form>
</div>

<div class="container">

@if ($errors->any())
    <div class="card form-errors catalog-message">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@forelse($cars as $car)

    <div class="card car-card">

        <img src="{{ $car->image_src }}" alt="{{ $car->brand }}" onerror="this.onerror=null;this.src='{{ asset('images/car-placeholder.webp') }}'">

        <div class="info">

            <div class="flex-group">
                <h2>{{ $car->brand }}</h2>

                <span class="status {{ $car->status === 'available' ? 'free' : ($car->status === 'maintenance' ? 'repair' : 'busy') }}">
                    {{ $car->status === 'available' ? 'Свободна' : ($car->status === 'maintenance' ? 'Обслуживание' : 'Занята') }}
                </span>
            </div>

            <div class="description">
                {{ $car->year }}
                {{ $car->fuel_type }}
                {{ $car->transmission }}
            </div>

            <div class="flex-group">
                <div class="number-plate">{{ $car->number_plate }}</div>
                <div class="price">{{ number_format((float) $car->price_per_minute, 2, '.', '') }} лей/мин</div>
            </div>

            @if ($car->status === 'available')
                <a class="btn btn-rent" href="{{ route('rent.create', ['car_id' => $car->id]) }}">Арендовать</a>
            @else
                <span class="btn btn-rent btn-disabled" aria-disabled="true">Недоступно</span>
            @endif

        </div>
    </div>

@empty
    <div class="card car-card">
        <div class="info">
            <h2>Ничего не найдено</h2>
            <div class="description">Попробуйте расширить диапазон цены или убрать часть фильтров.</div>
        </div>
    </div>
@endforelse

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const minRange = document.getElementById('min_price');
    const maxRange = document.getElementById('max_price');
    const minLabel = document.getElementById('price-min-label');
    const maxLabel = document.getElementById('price-max-label');

    if (!minRange || !maxRange || !minLabel || !maxLabel) {
        return;
    }

    const syncLabels = () => {
        let minValue = parseInt(minRange.value, 10);
        let maxValue = parseInt(maxRange.value, 10);

        if (minValue > maxValue) {
            if (document.activeElement === minRange) {
                maxValue = minValue;
                maxRange.value = String(maxValue);
            } else {
                minValue = maxValue;
                minRange.value = String(minValue);
            }
        }

        minLabel.textContent = 'От: ' + minRange.value;
        maxLabel.textContent = 'До: ' + maxRange.value;
    };

    minRange.addEventListener('input', syncLabels);
    maxRange.addEventListener('input', syncLabels);
    syncLabels();
});
</script>

@endsection
