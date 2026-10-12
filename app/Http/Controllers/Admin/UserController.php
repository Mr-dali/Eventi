<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return $this->listing(null);
    }

    public function edit(User $user)
    {
        return $this->listing($user);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'nom'          => ['required', 'string', 'max:100'],
            'prenom'       => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'role'         => ['required', 'in:user,admin'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ], [
            'required'         => 'Nom, prénom et e-mail sont obligatoires.',
            'email.email'      => 'Adresse e-mail invalide.',
            'email.unique'     => 'Cet e-mail est déjà utilisé par un autre compte.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
        ]);

        $user->fill(['nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email']]);
        $user->role = $data['role'];

        if (! empty($data['new_password'])) {
            $user->password = $data['new_password']; // hashé par le cast
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->delete(); // réservations supprimées en cascade

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    private function listing(?User $editing)
    {
        $users = User::withCount('reservations as nb_resa')
            ->withSum(['reservations as places_total' => fn ($q) => $q->where('statut', 'confirmee')], 'nb_places')
            ->latest()
            ->get();

        $totalUsers  = $users->count();
        $totalAdmins = $users->where('role', 'admin')->count();

        return view('admin.users.index', compact('users', 'editing', 'totalUsers', 'totalAdmins'));
    }
}
