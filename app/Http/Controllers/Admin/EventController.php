<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Notifications\EventiNotification;
use App\Services\NotificationDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        return $this->listing(null);
    }

    public function edit(Event $event)
    {
        return $this->listing($event);
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data  = $this->validated($request);
        $image = $this->storeImage($request);

        Event::create(Arr::except($data, 'image') + [
            'image'      => $image,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.events.create')
            ->with('success', 'Événement ajouté avec succès ! Voir les événements →');
    }

    public function update(
        Request $request,
        Event $event,
        NotificationDeliveryService $delivery,
    ): RedirectResponse
    {
        $data = Arr::except($this->validated($request), 'image');
        $notificationFields = ['titre', 'description', 'date_evenement', 'lieu', 'prix', 'statut'];
        $oldTitle = $event->titre;

        if ($image = $this->storeImage($request)) {
            $this->deleteImage($event->image);
            $data['image'] = $image;
        }

        $event->update($data);

        if ($event->wasChanged($notificationFields)) {
            $cancelled = $event->statut === 'annule';
            $users = $event->reservations()
                ->where(function ($query): void {
                    $query->where('statut', 'confirmee')
                        ->orWhere(function ($query): void {
                            $query->where('statut', 'en_attente')
                                ->where('expires_at', '>', now());
                        });
                })
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();

            $delivery->sendToUsers($users, new EventiNotification(
                'event-change:'.$event->id.':'.Str::uuid(),
                $cancelled ? 'Événement annulé' : 'Événement modifié',
                $cancelled
                    ? 'L’événement « '.$oldTitle.' » a été annulé par l’organisateur.'
                    : 'Les informations de l’événement « '.$event->titre.' » ont été modifiées.',
                route('events.index'),
            ));
        }

        return redirect()->route('admin.events.index')->with('success', 'Événement mis à jour avec succès.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->reservations()->exists()) {
            return back()->withErrors([
                'delete' => 'Cet événement a des réservations. Annulez-le pour informer les participants et conserver leur historique.',
            ]);
        }

        $this->deleteImage($event->image);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Événement supprimé avec succès.');
    }

    private function listing(?Event $editing)
    {
        $events = Event::withPlaces()->latest()->get();

        return view('admin.events.index', compact('events', 'editing'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'titre'          => ['required', 'string', 'max:200'],
            'description'    => ['nullable', 'string'],
            'date_evenement' => ['required', 'date'],
            'lieu'           => ['required', 'string', 'max:200'],
            'capacite'       => ['required', 'integer', 'min:1'],
            'prix'           => ['nullable', 'numeric', 'min:0'],
            'statut'         => ['required', 'in:actif,annule,termine'],
            'categorie'      => ['required', 'in:sport,culture,musique,autre'],
            'image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'required'    => 'Veuillez remplir tous les champs obligatoires (titre, date, lieu, capacité).',
            'statut.in'   => 'Statut invalide.',
            'image.image' => 'Format image non autorisé (jpg, jpeg, png, webp).',
            'image.mimes' => 'Format image non autorisé (jpg, jpeg, png, webp).',
            'image.max'   => 'Image trop grande (max 2 Mo).',
        ]);

        $data['prix'] = $data['prix'] ?? 0;

        return $data;
    }

    /** Enregistre l'image dans public/assets/img (même dossier que le projet d'origine). */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $name = uniqid('evt_') . '.' . $file->extension();
        $file->move(public_path('assets/img'), $name);

        return $name;
    }

    private function deleteImage(?string $name): void
    {
        if ($name) {
            File::delete(public_path('assets/img/' . $name));
        }
    }
}
