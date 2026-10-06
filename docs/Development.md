# Ontwikkelen

## Vereisten

- PHP 8.3 of nieuwer
- Composer
- Node.js 22 of nieuwer

## Lokale installatie

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
npm install
php artisan migrate --seed
composer run dev
```

SQLite is de standaarddatabase. Configureer voor MySQL of PostgreSQL de
bijbehorende `DB_*`-variabelen en voer daarna `php artisan migrate` uit.

`php artisan migrate --seed` maakt lokale demogegevens aan, waaronder de
gebruiker `bas.vanderwiel@rapide.software` met wachtwoord `password`. Gebruik deze gegevens
uitsluitend lokaal en wijzig het wachtwoord voordat een account buiten een
lokale omgeving wordt gebruikt.

## Handige commando's

```bash
php artisan test
vendor/bin/phpstan analyse --no-progress
npm run check
npm run types:check
vendor/bin/pint
php artisan wayfinder:generate --with-form
```

Gebruik `composer run dev` voor de Laravel-ontwikkelserver, queue, logs en
Vite. Genereer Wayfinder-route-typen opnieuw wanneer routes of form requests
wijzigen.

## Wijzigingen aan het domein

- Maak schemawijzigingen als nieuwe migration in `database/migrations`.
- Houd validatie in een Form Request en domeintransacties in een Action.
- Voeg bij nieuwe domeinfunctionaliteit een featuretest toe in
  `tests/Feature`.
- Houd Inertia-props en TypeScript-typen in sync wanneer controllerdata
  wijzigt.
- Controleer UI-wijzigingen tegen `style.md`.

## Testdekking

De featuretests beslaan onder andere authenticatie, klanten, projecten,
teamleden, incidentcreatie en -indexering, checklisttemplates, SLA-monitoring,
dashboarddata, rapportages en business hours.
