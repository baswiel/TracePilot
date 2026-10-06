# TracePilot

TracePilot is een intern Laravel- en Vue-dashboard voor het registreren,
opvolgen en afronden van storingen. De applicatie gebruikt Laravel, Inertia,
Vue 3, TypeScript en Tailwind CSS.

## Installatie

Vereist: PHP 8.3 of nieuwer, Composer en Node.js 22 of nieuwer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
npm install
php artisan migrate --seed
```

Start de lokale ontwikkelomgeving met:

```bash
composer run dev
```

De applicatie gebruikt standaard SQLite. Configureer voor MySQL of PostgreSQL
de databasevariabelen hieronder en voer daarna opnieuw `php artisan migrate`
uit.

## Environmentvariabelen

Minimaal vereist:

```dotenv
APP_NAME="TracePilot"
APP_ENV=local
APP_KEY=
APP_URL=http://localhost:8000
APP_LOCALE=nl

DB_CONNECTION=sqlite
DB_DATABASE=/volledig/pad/naar/database.sqlite
```

Voor een externe database stel je `DB_CONNECTION`, `DB_HOST`, `DB_PORT`,
`DB_DATABASE`, `DB_USERNAME` en `DB_PASSWORD` in. Voor productie zijn ook een
veilige `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, de mailinstellingen
en een passende sessie/cache-driver vereist.

## Migraties en seeders

Voer de schemawijzigingen uit met:

```bash
php artisan migrate
```

De standaardchecklist bestaat uit ‘Storing opgelost’, ‘Toegevoegd aan Storing
Log’ en ‘Postmortem verstuurd’. Seed alleen deze gegevens met:

```bash
php artisan db:seed --class=IssueChecklistTemplateSeeder
```

`php artisan migrate --seed` voert ook de standaardseeders uit en maakt een
lokale testgebruiker aan (`bas.vanderwiel@rapide.software`, wachtwoord: `password`) en de
demo-projecten onder andere ‘WieKiesJij’ en ‘Motor2go’. Wijzig het wachtwoord via de
reguliere wachtwoordflow voordat je deze gebruiker buiten lokaal gebruik inzet.

## Ontwikkelcommando’s

```bash
composer run dev                 # Laravel-server, queue, logs en Vite
npm run check                    # Frontend-formattering en lint
npm run check:fix                # Herstel frontend-formattering
npm run types:check              # TypeScript-controle
vendor/bin/pint                  # PHP-formattering
php artisan wayfinder:generate --with-form  # Route-typen regenereren
```

## Tests en statische analyse

```bash
php artisan test
vendor/bin/phpstan analyse --no-progress
npm run check
npm run types:check
vendor/bin/pint
```

## Issuestatussen

- **Open**: de storing is gemeld en het technische oplossingsitem is nog niet
  voltooid.
- **Opgelost / afhandeling**: het resolution-item is voltooid, maar er staan
  nog verplichte nazorgstappen open.
- **Afgerond**: alle verplichte checkliststappen zijn voltooid. Optionele
  stappen blokkeren deze status niet.

Wanneer een verplicht item opnieuw wordt geopend, wordt de status automatisch
opnieuw berekend.

## Checklist snapshots

Bij het aanmaken van een issue kopieert de applicatie uitsluitend actieve
checklisttemplates naar eigen checklistitems. Naam, verplichtheid,
resolution-marker en volgorde worden vastgelegd als snapshot. Latere wijzigingen
aan templates veranderen bestaande issues dus nooit.

Issue, checklistitems en de eerste activiteit worden in één
database-transactie aangemaakt. Checklistwijzigingen gebruiken eveneens een
transactie en een lock op het issue, zodat gelijktijdige wijzigingen de status
niet inconsistent maken.
