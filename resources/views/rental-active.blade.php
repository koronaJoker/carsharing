@extends('layouts/layout')

@section('content')

@php
    $activeCommand = session('command_action');
    $isLocked = session('rentals.'.$rental->id.'.locked', true);
@endphp

<main class='active-rental-page'>
    <div class='active-rental-shell'>
        <p class='active-rental-kicker'>Моя аренда</p>

        <section class='card active-rental-card'>
            <div class='active-rental-heading'>
                <span class='active-rental-eyebrow'>Мой автомобиль</span>
                <h1>{{ $rental->car->brand }}</h1>
                <p>{{ $rental->car->year }} · аренда №{{ $rental->id }}</p>
            </div>

            @if (session('success'))
                <div class='rental-toast rental-toast--success'>
                    <i class='fa-solid fa-circle-check'></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('command_status'))
                <div class='rental-toast rental-toast--command'>
                    <i class='fa-solid fa-bolt'></i>
                    <span>{{ session('command_status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class='rental-toast rental-toast--error'>
                    @foreach ($errors->all() as $error)
                        <span>{{ $error }}</span>
                    @endforeach
                </div>
            @endif

            <div class='active-rental-vehicle'>
                <div class='vehicle-halo'></div>
                <div class='rental-plate'>
                    <i class='fa-solid fa-flag-checkered'></i>
                    <span>{{ $rental->car->number_plate }}</span>
                </div>
                <img
                    src="{{ $rental->car->image_src }}"
                    alt="{{ $rental->car->brand }}"
                    class="active-rental-car-image"
                    onerror="this.onerror=null;this.src='{{ asset('images/car-placeholder.webp') }}'"
                >
                <div class='vehicle-shadow'></div>
            </div>

            <div class='rental-control-grid'>
                <form method='POST' action='{{ route('rentals.command', $rental) }}' class='rental-command-form'>
                    @csrf
                    <input type='hidden' name='action' value='flash'>
                    <button type='submit' class='rental-control rental-control--flash {{ $activeCommand === 'flash' ? 'is-triggered' : '' }}'>
                        <i class='fa-solid fa-lightbulb'></i>
                        <span>Помигать</span>
                    </button>
                </form>

                <form method='POST' action='{{ route('rentals.command', $rental) }}' class='rental-command-form'>
                    @csrf
                    <input type='hidden' name='action' value='warmup'>
                    <button type='submit' class='rental-control rental-control--warmup {{ $activeCommand === 'warmup' ? 'is-triggered' : '' }}'>
                        <i class='fa-solid fa-fire-flame-curved'></i>
                        <span>Прогреть</span>
                    </button>
                </form>

                <form method='POST' action='{{ route('rentals.command', $rental) }}' class='rental-command-form'>
                    @csrf
                    <input type='hidden' name='action' value='toggle_lock'>
                    <button type='submit' class='rental-control rental-control--lock {{ $activeCommand === 'toggle_lock' ? 'is-triggered' : '' }}'>
                        <i class='fa-solid {{ $isLocked ? 'fa-lock' : 'fa-lock-open' }}'></i>
                        <span>{{ $isLocked ? 'Открыть двери' : 'Закрыть двери' }}</span>
                    </button>
                </form>

                <button type='button' class='rental-control rental-control--finish' id='open-finish-modal'>
                    <i class='fa-solid fa-flag-checkered'></i>
                    <span>Завершить</span>
                </button>
            </div>

            <div class='active-rental-meta'>
                <span><i class='fa-regular fa-clock'></i> {{ $rental->start_time->format('d.m.Y H:i') }}</span>
                <span class='active-rental-status'><i class='fa-solid fa-circle'></i> Активна</span>
            </div>
        </section>
    </div>
</main>

<div class='confirm-modal hidden' id='finish-modal'>
    <div class='confirm-modal-card'>
        <div class='confirm-modal-icon'><i class='fa-solid fa-flag-checkered'></i></div>
        <h2>Завершить поездку?</h2>
        <p>Проверьте, что автомобиль припаркован по правилам. После подтверждения аренда будет закрыта.</p>

        <form method='POST' action='{{ route('rentals.finish', $rental) }}' class='confirm-form'>
            @csrf
            <label class='confirm-check'>
                <input type='checkbox' name='confirm_finish' value='1' required>
                <span>Подтверждаю завершение поездки</span>
            </label>

            <div class='confirm-actions'>
                <button type='submit' class='rental-control rental-control--finish'>Подтвердить</button>
                <button type='button' class='rental-control rental-control--secondary' id='close-finish-modal'>Отмена</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('finish-modal');
    const openButton = document.getElementById('open-finish-modal');
    const closeButton = document.getElementById('close-finish-modal');

    const closeModal = function () {
        modal?.classList.add('hidden');
    };

    openButton?.addEventListener('click', function () {
        modal?.classList.remove('hidden');
    });

    closeButton?.addEventListener('click', closeModal);

    modal?.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    document.querySelectorAll('.rental-command-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelector('.rental-control')?.classList.add('is-activating');
        });
    });
});
</script>

@endsection
