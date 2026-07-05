@extends('layouts.layout')

@section ("content")

<div class="card">

    <form action="{{ route('profile.update') }}" method = "POST">
        @csrf  
        @method('PUT') 
        <div class="title">Профиль</div>
        <h1>Редактировать профиль</h1>

        @if(session('success'))
            <div class="form-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="form-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        
        <div class="form-group">
            <label for="name">Имя</label>
            <input id="name" type="text" name="name" class="btn-min" value="{{ old('name', $user->name) }}" placeholder="Аркадий Паровозов" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" class="btn-min" value="{{ old('email', $user->email) }}" placeholder="mail@example.com" required>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input id="phone" type="text" name="phone" class="btn-min" value="{{ old('phone', $user->phone) }}" placeholder="+37360000000" required>
        </div>

        <div class="form-group">
            <label for="idnp">IDNP (13 цифр)</label>
            <input id="idnp" type="text" name="idnp" class="btn-min" value="{{ old('idnp', $user->idnp) }}" maxlength="13" placeholder="1234567890123" required>
        </div>

        <div class="form-group">
            <label for="driver_license">Водительские права</label>
            <input id="driver_license" type="text" name="driver_license" class="btn-min" value="{{ old('driver_license', $user->driver_license) }}" placeholder="AB1234567">
        </div>

        <div class="submit-block">
            <a href="{{ route('profile') }}">Назад</a>
            <input type="submit" class="btn" value="Сохранить">
        </div>
    
    </form>
</div>

@endsection