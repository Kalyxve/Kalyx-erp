<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /** GET /login */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('pages.auth.login');
    }

    /** POST /auth/login (form clásico) */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($validated, $remember)) {
            $request->session()->regenerate();
            // intended te lleva a donde quería ir antes de que el auth lo redirigiera
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Credenciales inválidas'])
            ->onlyInput('email'); // mantiene el email en el campo
    }
}
