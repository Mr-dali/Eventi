# Eventi — migration vers Laravel

Ce dossier contient **uniquement vos fichiers** (modèles, contrôleurs, vues, routes, migrations, CSS, images).
On les copie dans un projet Laravel neuf. Prérequis : PHP 8.2+, Composer, MySQL, **Laravel 11 ou 12**.

## Installation

```bash
# 1. Créer un projet Laravel neuf
composer create-project laravel/laravel eventi
cd eventi

# 2. Copier le contenu de ce dossier dans le projet (fusionner / écraser si demandé)
#    app/  config/  database/  public/  resources/  routes/

# 3. Supprimer la migration "users" fournie par défaut (elle est remplacée par la nôtre)
rm database/migrations/0001_01_01_000000_create_users_table.php
```

## Configuration du fichier `.env`

```env
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_evenements
DB_USERNAME=root
DB_PASSWORD=

# La table "sessions" par défaut a été supprimée avec la migration users :
SESSION_DRIVER=file
CACHE_STORE=file

# Clé OpenWeatherMap (n'est plus écrite dans le code)
OPENWEATHER_API_KEY=votre_cle
```

## Lancement

```bash
php artisan migrate --seed   # crée les tables + données de démo
php artisan serve            # http://127.0.0.1:8000
```

Comptes de test (mot de passe **password**) :
- admin : `admin@eventi.tn`
- utilisateurs : `amine@eventi.tn`, `seif@eventi.tn`

## Correspondance ancien → nouveau

| Ancien fichier | Nouveau |
|---|---|
| `config/db.php` | `.env` |
| `schema.sql.sql` | `database/migrations/*` + `database/seeders/DatabaseSeeder.php` |
| `login.php`, `register.php`, `logout.php` | `AuthController` + `resources/views/auth/*` |
| `events.php`, `reserve.php` | `EventController` + `resources/views/events/*` |
| `weather.php` | `WeatherController` + `resources/views/weather.blade.php` |
| `admin/dashboard.php` | `Admin\DashboardController` |
| `admin/add_event.php`, `admin/edit_event.php` | `Admin\EventController` (resource) |
| `admin/users.php` | `Admin\UserController` |
| `<style>` de chaque page | `public/css/*.css` |
| `assets/img/*` | `public/assets/img/*` |

## Ce qui a changé volontairement

- **Déconnexion et suppressions en POST/DELETE avec jeton CSRF** (avant : simples liens GET, donc exploitables à distance).
- **Clé API météo déplacée dans `.env`**. La clé qui était dans `weather.php` a été partagée : pensez à la régénérer sur openweathermap.org.
- **Les 6 événements « démo » codés en dur dans `events.php` sont supprimés** : ils existaient déjà en base (doublons) et n'étaient pas réservables. Ils sont dans le seeder.
- **La catégorie utilise maintenant la colonne `categorie`** (avant elle était devinée par mots-clés dans le titre). Un champ « Catégorie » a donc été ajouté aux formulaires admin.
- **Réservation sécurisée** : transaction + verrou pour éviter de dépasser la capacité.
- L'utilisateur « Seif » avait un mot de passe invalide dans le SQL (`$1919123`) : il reçoit `password` dans le seeder.
- `index.php` (classe Ville) et `exemple.php` sont des exercices sans lien avec Eventi : non migrés. `/` redirige vers la connexion.

## Si vous avez déjà des données réelles

Faites un export **données seules** de l'ancienne base (`mysqldump --no-create-info`) et importez-le après `php artisan migrate` (sans `--seed`). Les noms de tables et de colonnes sont identiques.
