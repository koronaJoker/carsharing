@extends ("layouts/layout")

@section("content")

<div class="card">
    <form action="{{ route('payment.preview') }}" method="POST">
        @csrf
        <div class="title">Бронирование</div>

        @if ($errors->any())
            <div class="form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($car)
            <div class="rent-car-info">
                <h1>{{ $car->brand }} {{ $car->year }}</h1>
                <p>{{ number_format((float) $car->price_per_minute, 2, '.', '') }} лей/мин</p>
            </div>
            <input type="hidden" name="car_id" value="{{ old('car_id', $car->id) }}">
        @else
            <div class="form-group">
                <label>ID автомобиля</label>
                <input class="btn-min" type="number" name="car_id" placeholder="Введите ID авто" value="{{ old('car_id') }}" required>
            </div>
        @endif

        <div class="form-group">
            <h1>Выберите филиал</h1>

            <select class="branch-select" name="branch">
                <option value="unic" {{ old('branch', 'unic') === 'unic' ? 'selected' : '' }}>Unic</option>
                <option value="vokzal" {{ old('branch') === 'vokzal' ? 'selected' : '' }}>ЖД вокзал</option>
                <option value="malldova" {{ old('branch') === 'malldova' ? 'selected' : '' }}>MallDova</option>
                <option value="airport" {{ old('branch') === 'airport' ? 'selected' : '' }}>Аэропорт</option>
                <option value="gum" {{ old('branch') === 'gum' ? 'selected' : '' }}>ГУМ</option>
            </select>
        </div>

        <div class="form-group">
            <label for="start_time">Начало аренды</label>
            <input class="btn-min" name="start_time" id="start_time" placeholder="Выберите дату и время" autocomplete="off" value="{{ old('start_time') }}" required>
        </div>

        <button type="button" class="payment-btn" id="show-end-btn"><span>+</span> Указать конец аренды</button>

        <div class="form-group hidden" id="end-time-group">
            <label for="end_time">Конец аренды</label>
            <input class="btn-min" name="end_time" id="end_time" placeholder="Выберите дату и время" autocomplete="off" value="{{ old('end_time') }}" required>
        </div>

        <div class="submit-block">
            <input type="submit" class="btn" value="Перейти к оплате">
        </div>
    </form>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    if (typeof flatpickr === "undefined") {
      console.error("flatpickr не загрузился — проверь Network tab / CDN");
      return;
    }

    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const endGroup = document.getElementById('end-time-group');
    const showEndBtn = document.getElementById('show-end-btn');

    flatpickr(startInput, {
      locale: "ru",
      dateFormat: "Y-m-d H:i",
      enableTime: true,
      time_24hr: true,
      minDate: "today",
      monthSelectorType: "dropdown",
      altInput: false
    });

    flatpickr(endInput, {
      locale: "ru",
      dateFormat: "Y-m-d H:i",
      enableTime: true,
      time_24hr: true,
      minDate: "today",
      monthSelectorType: "dropdown",
      altInput: false
    });

    const hasOldEndTime = endInput.value !== "";
    if (hasOldEndTime) {
      endGroup.classList.remove('hidden');
    }

    showEndBtn.addEventListener('click', function () {
      endGroup.classList.remove('hidden');
      endInput.focus();
    });
  });
</script>

@endsection
