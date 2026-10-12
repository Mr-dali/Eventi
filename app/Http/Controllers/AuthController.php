<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(
            ['email' => ['required'], 'password' => ['required']],
            ['required' => 'Veuillez remplir tous les champs.']
        );

        if (! Auth::attempt($credentials)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email ou mot de passe incorrect.']);
        }

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom'      => ['required', 'string', 'max:100'],
            'prenom'   => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:confirm'],
            'confirm'  => ['required'],
        ], [
            'required'       => 'Tous les champs sont obligatoires.',
            'email.email'    => 'Adresse e-mail invalide.',
            'email.unique'   => 'Cet e-mail est déjà utilisé.',
            'password.min'   => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.same'  => 'Les mots de passe ne correspondent pas.',
        ]);

        User::create(Arr::except($data, 'confirm')); // rôle "user" par défaut, mot de passe hashé par le cast

        return back()->with('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
