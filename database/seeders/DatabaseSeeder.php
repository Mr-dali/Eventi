<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Mot de passe de tous les comptes de test : "password"
        $admin = new User(['nom' => 'Admin', 'prenom' => 'Système', 'email' => 'admin@eventi.tn', 'password' => 'password']);
        $admin->role = 'admin';
        $admin->save();

        User::create(['nom' => 'Ben Ali', 'prenom' => 'Amine', 'email' => 'amine@eventi.tn', 'password' => 'password']);
        User::create(['nom' => 'Naceur',  'prenom' => 'Seif',  'email' => 'seif@eventi.tn',  'password' => 'password']);

        // [titre, description, date, lieu, capacité, prix, catégorie]
        $events = [
            ['Conférence Tech Tunis 2025', "Journée dédiée aux nouvelles technologies et à l'IA.", '2025-06-15 09:00:00', 'Palais des Congrès, Tunis', 200, 50, 'autre'],
            ['Workshop PHP Avancé', 'Formation intensive sur PHP 8 et bonnes pratiques.', '2025-08-10 09:00:00', 'Hub Ibtikara, Tunis', 30, 120, 'autre'],
            ['Match de Football — Espérance vs Club Africain', 'Derby de Tunis au Stade Olympique de Radès. Ambiance garantie pour ce classique du football tunisien.', '2025-06-14 18:00:00', 'Stade Olympique de Radès, Tunis', 60000, 25, 'sport'],
            ['Tournoi de Tennis Open de Tunis', "Compétition internationale ATP Challenger. Les meilleurs joueurs africains et méditerranéens s'affrontent sur terre battue.", '2025-06-20 10:00:00', 'Club Tennis de la Marsa, Tunis', 3000, 40, 'sport'],
            ['Marathon International de Tunis 2025', 'Courez à travers les plus belles avenues de la capitale. Parcours de 42 km avec ravitaillement toutes les 5 km.', '2025-06-28 07:00:00', 'Avenue Habib Bourguiba, Tunis', 5000, 15, 'sport'],
            ['Championnat de Basketball U21', "Finale du championnat national de basketball juniors. Les quatre meilleures équipes s'affrontent pour le titre.", '2025-07-05 15:30:00', "Salle de Sport de l'Aouina, Tunis", 1200, 10, 'sport'],
            ['Gala de Natation — Championnat du Nord', 'Compétition régionale de natation toutes catégories. 200 nageurs attendus sur 12 épreuves.', '2025-07-12 09:00:00', "Piscine Olympique d'El Menzah, Tunis", 800, 5, 'sport'],
            ['Tournoi de Padel — Coupe Ramadan', 'Tournoi de padel en double mixte. Tous niveaux acceptés. Trophées et lots à gagner.', '2025-07-19 16:00:00', 'Padel Club Lac, Tunis', 64, 30, 'sport'],
            ['Exposition Art Contemporain Tunisien', 'Une sélection de 40 artistes tunisiens émergents présentent leurs œuvres dans un espace immersif unique.', '2025-06-18 10:00:00', 'Musée National du Bardo, Tunis', 500, 12, 'culture'],
            ['Festival du Film Méditerranéen', 'Trois jours de projections, de rencontres avec des réalisateurs et de débats autour du cinéma méditerranéen.', '2025-07-01 18:00:00', 'Cinéma Le Colisée, Tunis', 800, 20, 'culture'],
            ['Nuit du Patrimoine — Médina de Tunis', 'Visite nocturne guidée de la Médina avec animations historiques, conteurs et artisans en direct.', '2025-07-15 20:00:00', 'Médina de Tunis, Bab El Bhar', 300, 18, 'culture'],
            ['Concert Symphonique', "Soirée musicale avec l'Orchestre National de Tunisie. Programme : Mozart, Beethoven et compositeurs tunisiens.", '2025-07-20 20:00:00', 'Théâtre Municipal, Tunis', 500, 30, 'musique'],
            ['Festival Jazz à Carthage', 'Édition spéciale du festival de jazz avec des artistes internationaux et locaux sur scène pendant 4 nuits.', '2025-07-25 20:30:00', 'Amphithéâtre de Carthage, Tunis', 5000, 60, 'musique'],
            ['Soirée Malouf — Musique Andalouse', "Concert de musique malouf tunisien avec l'ensemble El Rachidiya, patrimoine immatériel de l'humanité.", '2025-08-05 19:30:00', 'Salle Ibn Khaldoun, Tunis', 600, 25, 'musique'],
        ];

        foreach ($events as [$titre, $description, $date, $lieu, $capacite, $prix, $categorie]) {
            Event::create([
                'titre' => $titre, 'description' => $description, 'date_evenement' => $date,
                'lieu' => $lieu, 'capacite' => $capacite, 'prix' => $prix,
                'categorie' => $categorie, 'created_by' => $admin->id,
            ]);
        }
    }
}
