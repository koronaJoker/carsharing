<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller{
    public function create(): View {
        return view('auth.register', [
            'title' => 'Регистрация',
            'active' => null
        ]);
    }
    public function store(Request $request): RedirectResponse {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'regex:/^(\+373|0)[0-9]{8}$/'],
            'idnp' => ['required', 'string', 'digits:13'],
            'driver_license' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'idnp' => $request->idnp,
            'driver_license' => $request->driver_license,
            
        ]);
        event(new Registered($user));
        Auth::login($user);
        return redirect(route('dashboard', absolute: false))
                ->with('success', 'Регистрация успешна');
    }
}
