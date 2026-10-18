<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'tipo_documento' => $request->tipo_documento,
            'numero_documento' => $request->numero_documento,
            'telefono' => $request->telefono,
            'password' => $request->password,
        ]);

        return to_route('home');
    }
}
