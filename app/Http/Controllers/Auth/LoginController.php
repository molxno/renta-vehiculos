<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $request->session()->regenerate();

        return to_route('home');
    }
}
