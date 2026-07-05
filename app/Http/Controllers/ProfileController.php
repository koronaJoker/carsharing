<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile', [
            'user' => Auth::user(),
            'title' => 'Ваш профиль',
            'active' => 'profile',
        ]);
    }

    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
            'title' => 'Редактирование профиля',
            'active' => 'profile',
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'idnp' => [
                'sometimes',
                'string',
                'size:13',
                Rule::unique('users')->ignore($user->id),
            ],
            'driver_license' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);

        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

        return redirect()
            ->route('profile')
            ->with('success', 'Профиль обновлён');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
