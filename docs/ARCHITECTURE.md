# Architectuur-spec — TracePilot

**Deze spec is normatief.** Zij geldt voor developers, reviewers en AI-agents die deze repository wijzigen. **MOET** is verplicht; **MAG NIET** verbiedt een werkwijze; **BIJ VOORKEUR** is de standaard waarvan alleen met een gedocumenteerde reden wordt afgeweken; **MAG** is toegestaan. Regels voor nieuwe of gewijzigde code gelden vanaf nu. Bestaande afwijkingen in §22 zijn geen automatisch voorbeeld voor nieuw werk.

[PRODUCT.md](PRODUCT.md) is leidend voor productgedrag en terminologie; dit document bepaalt de technische werkwijze. [TODO.md](TODO.md) inventariseert functionaliteit en [Development.md](Development.md) geeft setuphulp. Bij een UI-wijziging **MOET** vooraf ook [style.md](../style.md) gelezen worden, conform `AGENTS.md`.

## 1. Systeemoverzicht

| Onderdeel | Techniek | Verantwoordelijkheid |
| --- | --- | --- |
| Backend | PHP 8.3+, Laravel 13 | HTTP, validatie, autorisatie, domeinmutaties en Inertia-responses. |
| Frontend | Vue 3, TypeScript, Inertia 3 | Schermen en interactie op basis van serverprops. |
| Styling | Tailwind CSS 4, Reka UI-primitieven | Semantische tokens, componenten en responsiviteit. |
| Database | Eloquent; standaard SQLite | Persistente bron voor klant, project, storing, checklist, SLA en tijdlijn. |
| Auth | Laravel Fortify, sessieguard | Login, registratie, reset, verificatie, 2FA en passkeys. |
| Bestand | Laravel Storage, disk `local` | Private tijdlijnbijlagen en geautoriseerde downloads. |
| Cache/queue | Laravel-configuratie, standaard database | Frameworkinfrastructuur; geen eigen domeincache of domeinjob. |
| Mail | Laravel mail; lokaal `log` | Fortify-verificatie en wachtwoordherstel. |
| Build | Vite Plus, Wayfinder, Vue TSC | Assets, getypeerde routehelpers en frontendtypes. |

```text
Browser (Vue/Inertia)
  │ sessie, CSRF, Inertia-formulieren en serverprops
  ▼
routes/web.php + routes/settings.php
  ▼
Controller ──► FormRequest / Policy
  ├──► Action ──► Eloquent ──► database
  └──► schermquery / prop-mapping ──► Eloquent
                             └────► Storage (bijlagen)
```

**Harde grenzen.** De backend **MOET** de bron voor status, SLA, autorisatie en validatie blijven. Vue **MAG NIET** een definitieve domeinuitkomst berekenen die van de server kan afwijken. De browser **MAG** invoer voor de gebruiker controleren, maar de server **MOET** mutaties opnieuw valideren. Er is geen eigen publieke domein-API, tenant-resolutie of externe storingsintegratie. De aanwezigheid van configureerbare queue-, cache- en S3-drivers bewijst niet dat het product die gebruikt.

## 2. Architectuurprincipes

| Principe | Concrete regel voor nieuwe code |
| --- | --- |
| Eén bron per regel | Status **MOET** via `SyncIssueStatus`, SLA via `CalculateIssueSla`; kopieer die formules niet in Vue of controllers. |
| Duidelijke HTTP-grens | Form Requests **MOETEN** niet-triviale input valideren en autoriseren; controllers verbinden invoer, use-case/query en response. |
| Atomiciteit | Multi-record-mutaties **MOETEN** één transactie gebruiken; concurrerende issue/checklist-mutaties **MOETEN** binnen die transactie locken. |
| Expliciete waarden | `IssuePriority`, `IssueStatus` en `IssueCause` **MOETEN** de serverwaarden blijven bepalen. |
| Projectcontext | Een genest record **MOET** bij het issue in de URL horen; route model binding alleen is niet voldoende. |
| Hergebruik | Bestaande Actions, requests, badges, formulieren en UI-primitieven **MOETEN** worden onderzocht voor iets nieuws wordt toegevoegd. |
| Minimaal aantal lagen | Repository, DTO, contract of adapter **MAG** alleen bij een concrete grens, hergebruik of testnoodzaak worden toegevoegd. |
| Fail closed | Ontbrekende actor, verkeerde parent of ontbrekende autorisatie **MAG NIET** op een frontendcontrole worden afgewenteld. |

Een eenvoudige enkelvoudige CRUD-mutatie **MAG** in een controller blijven, zoals `CustomerController::update`, zolang zij geen gedeelde invariant, auditactiviteit of multi-record-transactie raakt. De incidentlevenscyclus **MOET** een Action gebruiken. Dit onderscheid sluit aan bij de dominante code zonder de bestaande grote `IssueController` tot standaard te verheffen.

## 3. Backend-structuur en domeingrenzen

```text
app/
├── Actions/                 # issue-use-cases, berekeningen, templatebeheer
│   └── Fortify/             # accountacties voor Fortify
├── Console/Commands/        # install:features; geen domeinbatch
├── Enums/                   # IssueCause, IssuePriority, IssueStatus
├── Http/
│   ├── Controllers/         # web/settings, Inertia-props
│   ├── Middleware/          # gedeelde weergave-/Inertia-context
│   └── Requests/            # validatie, filters, authorize()
├── Models/                  # Eloquent-relaties, casts, fillable
├── Policies/                # objectautorisatie
├── Providers/               # Fortify en applicatieconfiguratie
└── Support/                 # kleine hulpen zoals IssueReportPeriod
routes/{web,settings,console}.php
database/{migrations,factories,seeders}/
tests/{Feature,Unit}/
```

`Http` **MAG** Actions, Policies en Models gebruiken. Actions **MOGEN** Models, Enums en kleine Support-objecten gebruiken, maar **MOGEN NIET** van Inertia, Vue of een Request afhangen. Models **MOETEN** relaties, casts en lokale modelinvarianten dragen; workflows over meerdere modellen **MOETEN** naar een Action. Leesqueries **MOGEN** in een controller wanneer één scherm ze gebruikt; gedeelde domeinselecties **MOETEN** bij hergebruik op één herkenbare plaats worden samengebracht. Nieuwe appmappen **MOGEN NIET** worden ingevoerd uitsluitend om een theoretisch lagenmodel na te bootsen.

| Domeingebied (PRODUCT.md §4) | Primaire code | Grens |
| --- | --- | --- |
| Klant, project, responders | `Customer`, `Project`, `TeamMember` en beheercontrollers | Project is context van iedere storing. |
| Storingslevenscyclus | `Issue`, `CreateIssue`, `UpdateIssueDetails`, `MarkIssueFirstResponse` | Status komt uit checklist. |
| Checklist, tijdlijn, postmortem | Templates/items, `SyncIssueStatus`, `IssueActivity`, `IssuePostmortem` | Snapshot en audit horen bij de mutatie. |
| SLA, rapport | `CalculateIssueSla`, `BuildIssueReport`, `IssueReportPeriod`, `BusinessHours` | Berekening op server. |
| Account | `User`, Fortify, policies | Een `TeamMember` is geen account. |

## 4. Backend-bouwstenen

### 4.1 Routes en controllers

Nieuwe domeinroutes **MOETEN** in de bestaande `auth`/`verified`-groepen van `routes/web.php` of `routes/settings.php` komen; de bestaande profielroutes met alleen `auth` zijn een bewuste uitzondering. Gebruik bestaande Nederlandse URL-segmenten (`storingen`, `projecten`, `klanten`) en stabiele Engelse routenamen (`issues.store`, `projects.update`). Specifieke paden zoals `/storingen/exporteren` **MOETEN** vóór `/{issue}` blijven. Route model binding **MOET** gecombineerd worden met controle van geneste parentrelaties.

Een controller **MOET** leesroutes en niet door een Form Request afgedekte acties met een policy autoriseren. Een controller **MAG** schermspecifieke Eloquent-leesqueries en expliciete Inertia-prop-mapping bevatten. Hij **MAG NIET** nieuwe status- of SLA-formules bevatten. Een nieuwe multi-record-mutatie **MOET** een Action krijgen. Returntypes (`Response`, `RedirectResponse`, `StreamedResponse`) **MOETEN** bij de werkelijke response passen. Test de HTTP-grens als Feature-test.

```php
public function store(StoreIssueRequest $request, CreateIssue $createIssue): RedirectResponse
{
    $project = Project::query()->findOrFail($request->integer('project_id'));
    $issue = $createIssue->handle($project, $request->user(), $request->issueAttributes());

    return to_route('issues.show', $issue);
}
```

Dit is een verkort voorbeeld van het bestaande pad; de echte controller zet ook een toast.

### 4.2 Form Requests en filtervalidatie

Een nieuw schrijf- of filterendpoint **MOET** bij niet-triviale input een Form Request met `rules()` en `authorize()` krijgen. Valideer enums met `Rule::enum`, IDs met `exists`, datumvolgorde expliciet en waarden die een parentrelatie vereisen ook tegen die parent. `StoreIssueRequest::after()` controleert historische status/checklistcombinaties. Een Action die buiten HTTP aanroepbaar is **MOET** zijn eigen kerninvariant behouden. Geef een Action uitsluitend `validated()` of een expliciete transformatie door, nooit ruwe `$request->all()`. Filters **MOETEN** sorteer- en richtingwaarden whitelisten zoals `IssueIndexFilterRequest`; gebruikersinput **MAG NIET** rechtstreeks SQL-sortexpressies vormen.

```php
'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('is_active', true)],
'priority' => ['required', Rule::enum(IssuePriority::class)],
```

Test geldige invoer, ongeldige waarden, ontbrekende actor en parentmismatch.

### 4.3 Actions en berekeningen

Een Action **MOET** één herkenbare use-case of berekening benoemen: `CreateIssue`, `UpdateIssueChecklistItemCompletion`, `CalculateIssueSla`, `BuildIssueReport`. Gebruik constructorinjectie voor een andere Action, zoals checklistupdate → `SyncIssueStatus`. Multi-record-mutaties **MOETEN** een transactie gebruiken; bij concurrerende checklistwijziging **MOET** het issue/item binnen die transactie opnieuw worden gelezen en gelockt. Herhaalde identieke acties **MOGEN NIET** dubbele audit of tijdstempels schrijven wanneer idempotentie onderdeel van de flow is. Berekeningen **MOGEN NIET** afhankelijk zijn van Vue-formattering; `CalculateIssueSla::handle($issue, $now)` maakt tijdgrenzen testbaar.

Een nieuwe Action **MOET** gedragstests krijgen. Test rollback wanneer gedeeltelijke opslag een concrete risicofactor is, zoals bij `CreateIssue`. De tijdelijke mutable velden in `CreateIssue` zijn een bestaande uitzondering: kopieer die **MAG NIET** als patroon; houd aanroepdata **BIJ VOORKEUR** lokaal in `handle()`.

```php
return DB::transaction(function () use ($item): Issue {
    $issue = Issue::query()->lockForUpdate()->findOrFail($item->issue_id);
    // Lees het item opnieuw binnen dezelfde transactie en controleer issue_id.
    return $this->syncIssueStatus->handle($issue);
});
```

Dit toont de grens uit `UpdateIssueChecklistItemCompletion` in verkorte vorm; kopieer voor een echte actie ook de itemlock, mutatie en audit uit de broncode.

### 4.4 Modellen, enums en relaties

Models **MOETEN** expliciete `#[Fillable]`-velden, passende casts en getypeerde relaties gebruiken. Persistente prioriteit, status en oorzaak **MOETEN** de bestaande backed enums respecteren. `IssueActivity.metadata` en `BusinessHours.working_days` zijn JSON-casts; een nieuw JSON-veld **MOET** een beschreven shape en test hebben. Geef geen volledig `Model::toArray()` aan een pagina als een beperkte propvorm volstaat. Modelhooks **MOGEN** een kleine lokale invariant bewaken (zoals het maximum van één actief oplossings-template), maar een workflow over meerdere records **MOET** in een Action. Een nieuw model **MOET** relaties, casts en databaseconstraints hebben en krijgt een factory als tests dat model moeten maken.

### 4.5 Policies

Een nieuw domeinobject met gebruikersacties **MOET** een policy of gelijkwaardige expliciete serverautorisatie hebben. `IssuePolicy` laat momenteel iedere aangemelde gebruiker toe; dat is huidig productgedrag, geen reden om checks weg te laten. Deletevoorwaarden horen in een policy (`CustomerPolicy::delete` weigert een klant met projecten) én waar nodig in databaseconstraints. Nieuwe rollen of klantisolatie **MOGEN NIET** zonder productbeslissing worden verondersteld. Test een toegestane en geweigerde actie.

### 4.6 Overige bouwstenen

`IssueReportPeriod` is een klein waardeobject. `InstallFeaturesCommand` hoort bij de starterkit, niet bij domeinbatchverwerking. Er zijn geen eigen repositories, DTO-hiërarchie, API Resources, domeinevents/listeners, jobs, observers of notificatieklassen. Die bouwstenen **MOGEN** bij een echte grens worden toegevoegd, maar **MOGEN NIET** alleen voor symmetrie als lege laag ontstaan. Een toekomstige job **MOET** idempotent zijn, zonder Request-/sessieafhankelijkheid, met retry-, fout- en teststrategie.

## 5. HTTP- en Inertia-conventies

TracePilot heeft **geen eigen publieke JSON-API** of `/api`-routes. Controllers leveren benoemde Inertia-props. Formulieren sturen via `useForm` of `router` en tonen Laravel/Inertia-validatiefouten met `InputError`. Voor gewone webverzoeken geldt het frameworkgedrag; er is geen projectspecifiek JSON-errorcontract of API-versiecontract. `bootstrap/app.php` kan voor `api/*` en `expectsJson()` JSON renderen, maar dit maakt nog geen API.

Nieuwe endpoints **MOETEN** methode, naam, middleware en autorisatie expliciet vastleggen. Na mutatie **MOET** een redirect naar een logische route volgen en, waar feedback nodig is, het bestaande `Inertia::flash('toast', ...)`-patroon. Props **MOETEN** datums en enumwaarden bewust serialiseren (`toDateTimeString()`, `->value`); frontendtypen **MOETEN** de werkelijke vorm volgen. Groeiende lijsten **MOETEN** pagineren of expliciet begrenzen. Wayfinder-helpers in `resources/js/routes`, `actions` en `wayfinder` zijn gegenereerd en **MOGEN NIET** handmatig worden aangepast.

## 6. Authenticatie en autorisatie

De `web`-guard gebruikt een Laravel-sessie en webmiddleware met CSRF-bescherming. Fortify levert login/logout, zelfregistratie, reset, verificatieroutes, 2FA en passkeys. Een nieuwe domeinfeature **MAG NIET** een tweede token-/loginmechanisme introduceren. De meeste domeinroutes vermelden `auth` + `verified`; profiel lezen/bewerken vraagt alleen `auth`, beveiliging vraagt wachtwoordbevestiging. **Belangrijke huidige grens:** `User` implementeert `MustVerifyEmail` niet. Laravel `EnsureEmailIsVerified` blokkeert voor dit model daarom een niet-geverifieerd account niet. Behandel `verified` pas na een expliciete codewijziging en een negatieve toegangsproef als afgedwongen securitygrens. `settings/password` heeft `throttle:6,1`; Fortify heeft aparte login-, 2FA- en passkeylimieten.

Frontendzichtbaarheid **MAG NOOIT** de autorisatiegrens zijn. Controleer op de server ook de relatie van geneste records, zoals bij checklistupdates en bijlagedownloads. Gastverzoeken naar webpagina's gaan naar login; verboden policyacties geven 403 en een verkeerd gekoppeld record 404. `User` en `TeamMember` blijven gescheiden totdat PRODUCT.md §Open vragen een andere keuze vastlegt. `HandleInertiaRequests` deelt gebruiker, teller, meldingslijst en meldopties; nieuwe gedeelde props **MOETEN** klein, geautoriseerd en op iedere pagina betaalbaar zijn.

## 7. Frontend-structuur

```text
resources/
├── css/app.css                    # Tailwind, semantische en light/dark tokens
└── js/
    ├── app.ts                     # Inertia, layoutkeuze, theme/toast
    ├── pages/                     # Dashboard, Issues, Projects, Reports, settings, auth
    ├── layouts/                   # app-, auth- en settingsschillen
    ├── components/
    │   ├── ui/                    # generieke Reka/UI-primitieven
    │   ├── issues/                # prioriteit-, status- en SLA-badges
    │   ├── projects/, customers/  # hergebruikte formulieren
    │   └── *.vue                  # gedeelde blocks zoals InputError
    ├── composables/               # browser-/UI-gedrag
    ├── lib/                       # helpers zoals flashToast en cn
    ├── types/                     # gedeelde TS-typen
    └── routes/, actions/, wayfinder/ # gegenereerd
```

Pagina's **MOGEN** layouts, domeincomponenten, primitives, composables, types en routehelpers importeren. Domeincomponenten **MOGEN NIET** pagina's importeren; `ui/`-primitieven **MOGEN NIET** van `Issue` of `Project` afhangen. Een composable **MAG** browser-/interactiestatus dragen, maar domeinbesluiten blijven op de backend. `app.ts` kiest `AuthLayout` voor `auth/*`, `[AppLayout, SettingsLayout]` voor `settings/*` en `AppLayout` voor overige pagina's; nieuwe pagina's **MOETEN** die conventie volgen.

## 8. Frontend-bouwstenen en regels

### 8.1 TypeScript en props

`tsconfig.json` heeft `strict: true` en `@/*` wijst naar `resources/js/*`. Nieuwe code **MAG NIET** `any` toevoegen om bekende props of routevormen te omzeilen. Gebruik expliciete uniontypes voor enumwaarden en `null` wanneer de server `null` levert. Wijzig controllerprops, relevante paginatypen en tests samen. Bestaande `any` in generieke helpers is geen standaard. Gebruik Wayfinder-helpers zoals `show(issue.id)` uit `@/routes/issues` in plaats van losse URL-concatenatie.

### 8.2 Data, state en formulieren

Productdata **MOET** via Inertia-props of de bestaande Inertia-requestflow komen. Een nieuw domeinscherm **MAG NIET** een parallelle `fetch`-client en eigen cache introduceren zonder expliciete architectuurbeslissing. Gebruik `useForm` voor formulierstaat, `router.get` voor filter-URL's en gerichte router-methoden voor acties. `ProjectForm` en `CustomerForm` tonen gedeeld create/edit-gedrag; `Issues/Report` is het meldformulier. Serverfouten **MOETEN** bij velden verschijnen en `processing` **MOET** dubbele inzendingen beperken. Clientvalidatie **MAG** helpen maar **MAG NIET** servervalidatie vervangen.

Serverstate leeft in Inertia-props; tijdelijke dialoog-, filter- en formulierstaat blijft lokaal in Vue. Globale state **MAG** voor werkelijk appbreed UI-gedrag zoals `useAppearance`; er is geen Pinia/Vuex-store. Actieve filters **MOETEN** in de URL terugkomen. Na een mutatie **MOET** de serverresponse de canonieke toestand leveren; status of SLA wordt niet definitief in Vue bepaald.

```ts
const form = useForm({ name: props.project?.name ?? '' });
form.post(store.url()); // routehelper, servervalidatie en Inertia-fouten
```

Dit is de vorm van het bestaande project-/klantformulier, ingekort tot één veld; de werkelijke velden blijven bepaald door de Form Request.

### 8.3 Componenten en navigatie

Een pagina coördineert props, routeactie en schermindeling. Een domeincomponent bevat een herbruikbaar deel van één feature; een algemene component bevat geen featurekennis. Props **MOETEN** getypeerd zijn. Grote bestaande pagina's zoals `Issues/Show.vue`, `Dashboard.vue` en `Reports/Index.vue` zijn geen gewenste maatstaf: bij nieuwe complexe secties **MOET** zelfstandig te begrijpen presentatielogica worden geëxtraheerd. Gebruik `Head` voor de titel, `Link` voor navigatie en de bestaande breadcrumbconventie. Toasters lopen via `initializeFlashToast`; voeg geen tweede meldingssysteem per pagina toe.

### 8.4 Styling en toegankelijkheid

Bij **iedere** UI-wijziging **MOET** `style.md` vooraf gelezen worden. Nieuwe stijlen **MOETEN** vooral semantische Tailwind-klassen en tokens uit `resources/css/app.css` gebruiken (`bg-background`, `text-foreground`, `border-border`, `text-primary`). Hardgecodeerde kleuren **MOGEN** alleen wanneer een gedocumenteerd TracePilot-patroon dit vraagt. Gebruik componentvarianten en `cn()` voor conditionele klassen; controleer dark mode en mobiele breedtes. Statuskleur **MAG NOOIT** zonder tekst/icoon de enige betekenisdrager zijn. Labels, focus, `aria-invalid`/`aria-describedby`, lege staten en destructieve bevestiging via `ConfirmDeleteDialog` **MOETEN** de stijlgids volgen. Nieuwe zichtbare productcopy **MOET** Nederlands zijn.

## 9. Herbruikbare componenten

De hiërarchie is: `components/ui/*` (primitives zoals Button, Card, Dialog), gedeelde blocks (`InputError`, `ConfirmDeleteDialog`), domeincomponenten (`issues/IssueStatusBadge`, `IssueSlaBadge`, `projects/ProjectForm`, `customers/CustomerForm`) en daarna pagina's. Voor nieuwe UI **MOET** eerst een bestaand component worden gezocht; **BIJ VOORKEUR** breid een bijna passende variant met een duidelijke prop of slot uit; extraheer iets algemeens zodra minstens twee concrete gebruiksplaatsen dezelfde structuur hebben. Badges of foutpresentatie kopiëren **MAG NIET**. Een featurecomponent **MAG** featurewaarden kennen, een primitive **MAG NIET** afhankelijk worden van routes of domeinpropnamen.

## 10. Teststrategie

De tests zijn overwegend PHPUnit Feature-tests met `RefreshDatabase`. `phpunit.xml` gebruikt SQLite `:memory:`, arraycache/-sessie/-mail en synchrone queue. Er zijn alleen voorbeeld-Unit-tests en geen geconfigureerde frontendcomponent- of E2E-runner. Nieuwe domeinfunctionaliteit **MOET** via gedragstests worden bewezen: geslaagde flow, validatiefout, gast/verboden toegang, parentbinding en relevante status-/audit-/SLA-grenzen. De bestaande tests voor `CreateIssue` dekken snapshot en rollback; voor checklistacties heropenen, idempotentie en statusovergangen. Volg dat patroon, niet alleen een assert op HTTP 200.

| Wijziging | Minimaal bewijs |
| --- | --- |
| Nieuwe route/mutatie | Feature-test voor request, policy, validatie en response/redirect. |
| Domeininvariant/berekening | Grensgevallen, lege input, herhaling en tijdgrenzen; test de Action direct als HTTP niet relevant is. |
| Database-/relatiewijziging | Test constraint, deletegedrag en relaties; bij bestaande data ook migratiepad controleren. |
| Query/rapport | Test cohort, filter, paginering en nulnoemer; meet N+1 bij nieuwe relaties in lijsten. |
| Bestandsactie | `Storage::fake('local')`, type/grootte, verkeerde issuebinding en downloadrecht. |
| Alleen presentatie | Typecheck, formatter/linter, build en gerichte controle van mobiel, toetsenbord en dark mode. |

Er is geen tenancy; schrijf geen schijnbare cross-tenant-tests. Als klantisolatie wordt ingevoerd, worden negatieve cross-customer-tests verplicht. `php artisan test` draait PHPUnit; `composer test` draait ook PHP-formatcheck en PHPStan. CI voert via `composer ci:check` frontendcheck en Vue-typecontrole uit. De huidige CI heeft geen afzonderlijke `npm run build`-gate; bouw lokaal bij asset-/Vitewijzigingen.

## 11. Seeders, factories en lokale testdata

`DatabaseSeeder` roept de seeders voor gebruiker, teamleden, klanten, SLA-niveaus, projecten en checklisttemplates aan. Factories maken tests onafhankelijk: `IssueFactory` maakt project en gebruiker, `IssueChecklistTemplateFactory` heeft `resolutionMarker()` en `inactive()`, `ProjectFactory` heeft `inactive()`. Tests **MOETEN** de kleinste benodigde dataset via factories opzetten; ze **MOGEN NIET** afhankelijk zijn van alle demo-seeds tenzij juist de seedinhoud wordt getest. Nieuwe domeinmodellen **MOETEN** een factory krijgen als meerdere tests ze zelfstandig nodig hebben. Factory-states **MOETEN** een betekenisvolle domeintoestand benoemen.

Seeders **MOETEN** herhaalbaar zijn waar zij bij lokale setup opnieuw gedraaid worden (`updateOrCreate`/`firstOrCreate` is bestaand patroon). Voeg alleen representatieve demo-/configuratiedata toe, geen productiegeheimen. `UserSeeder` maakt momenteel een geverifieerd lokaal account met een bekend wachtwoord; dat **MAG NIET** als productieprovisioning worden gebruikt. Er is bovendien een verschil tussen de actuele seed en de oude README/Development-tekst over testaccount en demo-projecten; neem de seedcode als huidige feitelijke bron totdat de documentatie is opgeschoond. Productcontracten, zoals definitieve SLA-minuten, **MOGEN NIET** uit toevallige seedgegevens worden afgeleid.

## 12. Security

Autorisatie **MOET** op de server voor elke domeinroute plaatsvinden; lees daarnaast PRODUCT.md §Open vragen over het huidige model waarin alle aangemelde accounts brede toegang hebben. Er is **geen** tenantgrens: een `customer_id` is relationele productdata, geen beveiligingsscope. Introduceer dus geen schijnisolatie in de UI. Als isolatie productbeleid wordt, **MOETEN** alle queries, routebindingen, downloads, exports en gedeelde Inertia-props tegelijk worden herzien vóór uitrol.

`#[Fillable]` beperkt mass assignment; Actions en controllers **MOETEN** uitsluitend gevalideerde attributen muteren. Houd `APP_KEY`, mail-/databasegeheimen en passkeyinstellingen in environment/config; commit geen `.env`, tokens of private sleutels. Verificatie-, reset- en 2FA-data **MAG NIET** in Inertia-props, tijdlijnmetadata of logs belanden. `User` verbergt wachtwoord, 2FA-secrets en remember token; blijf daarnaast props expliciet beperken.

Bijlagen **MOETEN** server-side op type/grootte worden gevalideerd en op een private disk blijven. Download **MOET** het issue autoriseren, de koppeling `activity.issue_id === issue.id` controleren en ontbrekende metadata/bestanden afwijzen. Een oorspronkelijke bestandsnaam **MAG NIET** als opslagpad of autorisatiesleutel worden vertrouwd. Vue toont gebruikersinhoud standaard escaped; gebruik geen `v-html` voor tijdlijntekst zonder bewezen sanitatie. Filters/zoektermen **MOETEN** parameters blijven; whitelisted sortering is nodig omdat kolomnamen niet via bindings worden beschermd. Laravel-webformulieren **MOETEN** CSRF-bescherming behouden. Rate limits voor auth **MOETEN** intact blijven; stel voor nieuwe misbruikgevoelige routes afzonderlijke limieten vast.

Er zijn geen eigen webhooks, API keys, signed-download-URLs of SSRF-gevoelige externe HTTP-calls. Voeg daarvoor pas normen en tests toe als de functie wordt ontworpen. Logs **MOGEN NIET** bijlage-inhoud, wachtwoorden, resetcodes, 2FA-secrets of volledige ongefilterde requestpayloads bevatten. Bij incidentonderzoek **MAG** een interne ID en veilige actiecode gelogd worden; opslag en retentie van klant-/incidenttekst vragen een expliciete privacybeslissing.

## 13. Multi-tenancy

TracePilot is momenteel **niet multi-tenant**. `Customer` groepeert projecten; `User` heeft geen `customer_id` of tenantlidmaatschap, en de policies geven geen klantgebonden scope. Een klantfilter in het overzicht is dus functioneel zoeken, geen beveiligingsgrens. Nieuwe code **MAG NIET** een tenantclaim, tenantcacheprefix of cross-tenant-autorisatie vooronderstellen. Als isolatie later gewenst is, **MOET** eerst een product- en architectuurbesluit worden genomen over tenantidentiteit, queryscoping, routebinding, jobs, bestanden, cache, export en negatieve lektests. Een datalek tussen toekomstige tenants is dan een securityprobleem.

## 14. Externe integraties

De huidige externe grenzen zijn Fortify/authmail, de browser/Inertia-verbinding en een handmatige CSV-download. Er is geen Google Sheets API-koppeling: “Google Sheets-compatibel” betekent alleen CSV-formaat. Er is ook geen automatische kennisbank-, Storing Log-, melding- of postmortemverzendintegratie. De lokale bestandsdisk is een interne adapter van Laravel Storage.

Voor een **nieuwe** externe dienst **MOET** vóór implementatie in een korte ontwerpnotitie staan: eigenaar van het contract, input/output en bron van waarheid; configuratie en geheimen; timeout en foutvertaling; retry en idempotentie indien asynchroon; rate limit; webhookverificatie indien van toepassing; observability; test met fake/stub; herstel voor de gebruiker. Een eigen interface/adapter **MAG** wanneer de externe grens op meerdere plaatsen wordt gebruikt of tests anders afhankelijk van netwerk worden. Een interface voor één eenvoudige lokale Laravel-call **MAG NIET** louter voor vorm worden toegevoegd. Productbetekenis en foutgedrag **MOETEN** ook in PRODUCT.md worden vastgelegd.

## 15. Database- en dataconventies

Migrations gebruiken `id()` voor numerieke primaire sleutels, `foreignId()->constrained()` voor relaties en expliciete deleteactie. Nieuwe foreign keys **MOETEN** een benoemd levenscyclusgedrag hebben: `issues.project_id` en `issues.created_by` beperken verwijderen; checklistitems/activiteiten cascaderen met het issue; optionele actor-/teamlidrelaties worden `null` bij verwijdering. Kies deletegedrag op productbasis en test het; kopieer niet blind een naburige `cascadeOnDelete()`. Er zijn momenteel geen soft deletes; voeg ze alleen toe met gedefinieerde UI-, query- en auditsemantiek. Bestaande migrations **MOGEN NIET** achteraf worden gewijzigd als zij al gedeeld/uitgevoerd zijn; voeg een nieuwe migration toe.

Gebruik `snake_case` voor tabellen/kolommen, meervoud voor tabellen, `*_id` voor verwijzingen en `*_at` voor datetimemijlpalen. Persistente enumkolommen zijn strings met PHP-enumcast; verander waarden alleen met migratie-, compatibiliteits- en rapportageplan. Geldbedragen komen nu niet voor; introduceer geen opslagvorm zonder producteenheid/afronding. SLA-minuten zijn positieve integers; rapportpercentages worden berekend en niet opgeslagen. JSON (`metadata`, `working_days`) **MOET** een bekende shape en cast hebben. `IssueActivity` heeft alleen `created_at`; tijdlijnmutaties **MOETEN** auditbetekenis expliciet maken in plaats van stilzwijgend een activiteit te herschrijven.

```php
$table->foreignId('issue_id')->constrained()->cascadeOnDelete();
$table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
```

Dit is het bestaande verschil tussen een checklistitem dat met zijn issue verdwijnt en een optionele actorverwijzing die bij accountverwijdering leeg kan worden.

`config/app.php` gebruikt UTC, terwijl `.env.example` `APP_LOCALE=nl` zet. Datums **MOETEN** als tijdstippen worden behandeld, met expliciete conversie bij invoer/weergave wanneer een feature een lokale tijd bedoelt. SLA-berekening gebruikt globale werkuren; tijdzone- en historisch-SLA-beleid staan nog open in PRODUCT.md. Indexeer nieuwe filter- en joinpaden wanneer groeiend volume dat vereist; het schema heeft al samengestelde indexen voor status, prioriteit, meldtijd, project en behandelaar. Unieke constraints **MOETEN** servervalidatie ondersteunen waar uniciteit een invariant is (`customers.name`, `sla_levels.name`, SLA-doel per niveau/prioriteit).

## 16. Performance

Lijstcontrollers **MOETEN** relaties die per rij worden gebruikt eager loaden en aantallen via `withCount` ophalen; `DashboardController` en `IssueController::index` tonen dat patroon. Groeiende lijsten **MOETEN** pagineren; huidige standaard is 15 storingen en 10 projecten per pagina. Een nieuwe rapport- of exportquery die alle storingen in geheugen laadt **MOET** bij verwacht volume worden begrensd, gestreamd/gechunked of van een onderbouwde limiet voorzien. `IssueController::export` en `BuildIssueReport` laden momenteel de volledige selectie; beschouw dat als schaalrisico, niet als toekomstig standaardpatroon.

Voeg bij een nieuwe relationele kolom in een overzicht een N+1-controle toe. Filter/sorteer alleen op gevalideerde velden en controleer indexen via queryplan wanneer een grote tabel wordt geraakt. `HandleInertiaRequests` telt en haalt meldingen voor elke aangemelde pagina op; nieuwe gedeelde queries **MOETEN** een expliciete kostenafweging krijgen. Cache **MAG** pas bij gemeten herhaald werk en met invalidatiestrategie; er is nu geen domeincache. Een background job **MAG** zware niet-interactieve taken verplaatsen, mits productflow, retries en voortgang zijn ontworpen. Frontendbundelwijzigingen **MOETEN** met `npm run build` worden gecontroleerd wanneer Vite, dependencies of imports veranderen.

## 17. Logging, monitoring en fouten

Laravel exception reporting en de kanalen uit `config/logging.php` zijn aanwezig. Er is geen projectspecifieke structured-logging-, monitoring- of failed-jobprocedure. Nieuwe externe of asynchrone stappen **MOETEN** hun falen herkenbaar rapporteren met veilige context zoals issue-ID, actie en externe correlatie-ID; zij **MOGEN NIET** secrets, volledige klanttekst of bestandsinhoud loggen. Een onverwachte mislukking **MAG NIET** als succesvolle toast worden gepresenteerd. Validatiefouten blijven formulierfouten; 403/404 blijven onderscheiden voor autorisatie en verkeerde relatie. Voor een nieuwe job **MOET** worden vastgelegd waar mislukte jobs zichtbaar zijn en hoe herstart zonder duplicatie werkt.

## 18. Werkwijze voor AI/LLM-agents

Bij elke codewijziging **MOET** de agent eerst [PRODUCT.md](PRODUCT.md), deze spec en relevante broncode lezen. Bij UI-werk **MOET** ook [style.md](../style.md) worden gelezen. Zoek minstens één, **BIJ VOORKEUR** twee, vergelijkbare bestaande implementaties. Bepaal of zij dominant patroon of legacy-afwijking zijn (§22). Volg namen, plaats code in de juiste laag, hergebruik componenten en verander een productregel niet stilzwijgend. Voeg geen dependency toe zonder concrete noodzaak. Werk PRODUCT.md bij als gedrag verandert en dit document als een architectuurregel verandert. Schrijf of pas de relevante gedragstest aan en voer de toepasselijke kwaliteitscommando's uit. Rapporteer welke gates werkelijk zijn uitgevoerd en welke niet.

**Nieuwe backend-feature — normale checklist:** route + naam/middleware; policy; Form Request; Action bij workflow/multi-record; model/migration als data verandert; expliciete Inertia-props; Feature-tests voor succes, afwijzing en invariant; documentatiebesluit waar nodig. **Nieuwe frontend-feature:** serverprop/routecontract; getypeerde pagina; bestaande layout, componenten en badges; `useForm`/router; errors en loading; toegankelijkheid, mobiel en dark mode; Vue-types/check/build. **Nieuwe database-entiteit:** migration met FK/delete/index/unique-keuze; model met relaties/casts/fillable; factory; beleid voor seeddata; beleid voor verwijderen; tests. **Nieuwe integratie:** contract en data-eigenaarschap, secrets/config, timeout, retry/idempotentie, fake, fout-UI, logging en productdocumentatie.

Een agent **MAG NIET** alleen op een succesvol buildresultaat vertrouwen als een nieuwe serverregel is toegevoegd. Een agent **MAG NIET** onbesliste PRODUCT.md-vragen zelf als beleid invullen. Als een keuze nodig is, beschrijf feitelijke huidige werking en leg de concrete keuzevraag voor.

## 19. Anti-patterns

| ❌ Niet | ✅ Wel |
| --- | --- |
| Status in een controller of Vue herberekenen | `SyncIssueStatus` gebruiken. |
| SLA in badges/rapporten anders berekenen | `CalculateIssueSla` gebruiken. |
| Een actieve checklisttemplate achteraf op bestaande issues toepassen | Snapshot bij creatie respecteren. |
| Een genest item alleen op ID binden | `item.issue_id` met route-issue vergelijken. |
| Autorisatie alleen door een verborgen knop | Policy/Form Request op server. |
| Ruwe `$request->all()` mass assignen | Gevalideerde, expliciete velden. |
| Sorteringsinput in SQL interpoleren | Whitelist uit Form Request. |
| Multi-record-checklist zonder transactie/lock | Action met lock en status-sync. |
| Elke controllerquery in een fictieve repository wrappen | Schermquery laten staan of gedeelde query abstraheren bij hergebruik. |
| Een nieuwe Action met mutable toestand tussen aanroepen | Aanroepdata lokaal of als parameters houden. |
| Alle relaties lazy laden in paginarijen | `with`, `withCount`, selecties. |
| Onbegrensde lijst als standaard | Serverpaginering of expliciete limiet. |
| Hele Eloquent-modellen als browserprops lekken | Bewuste prop-mapping. |
| Een eigen fetch/cache-stack voor domeinschermen | Inertia-props, `useForm`, router. |
| URL's als strings aan elkaar plakken | Wayfinder-routehelpers. |
| Gegenereerde routes handmatig bewerken | Generator opnieuw draaien. |
| `any` gebruiken voor bekende props | Exact TS-type en nullability. |
| Een tweede prioriteit/status/SLA-badge bouwen | Bestaande issuecomponent uitbreiden. |
| Hardgecodeerde statuskleur zonder tekst | Semantisch token plus label/icoon. |
| `window.confirm` voor verwijderen | `ConfirmDeleteDialog`. |
| Publieke URL voor een private bijlage | Geautoriseerde downloadroute. |
| “Postmortem verstuurd” gelijkstellen aan echte verzending | Huidige handmatige checklistsemantiek documenteren tot productbesluit. |
| Klantfilter als tenantautorisatie behandelen | Huidige globale toegang erkennen en productbesluit vragen. |
| Demo-seedwachtwoord als productaccount gebruiken | Apart veilig accountbeheer. |
| Een externe integratie zonder fake/retrybeleid | Ontworpen contract, foutpad en test. |

## 20. Development workflow

Installeer volgens README/[Development.md](Development.md): PHP 8.3+, Node 22+, `composer install`, `npm install`, `.env`, `php artisan key:generate`, database en `php artisan migrate --seed`. `composer run dev` start de lokale ontwikkelomgeving. De bestaande CI draait op push naar `main` en pull requests; er is geen repositoryregel voor branchnaam of verplichte PR-tekst af te leiden. Gebruik bij commits de Conventional Commits-afspraak uit de projectinstructies.

| Doel | Bestaand commando |
| --- | --- |
| Migrations | `php artisan migrate` |
| Frontendroutes/types regenereren | `php artisan wayfinder:generate --with-form` |
| PHPUnit | `php artisan test` |
| PHP-formatcontrole | `composer lint:check` |
| PHP-formatfix | `composer lint` |
| PHPStan | `composer types:check` |
| Frontend lint/formatcontrole | `npm run check` |
| Frontend automatische fix | `npm run check:fix` |
| Vue-typecontrole | `npm run types:check` |
| Frontendbuild | `npm run build` |
| Gecombineerde lokale/CI-gate | `composer ci:check` |

`composer ci:check` voert `npm run check`, `npm run types:check` en `composer test` uit; `composer test` omvat Pint-check, PHPStan en PHPUnit. `.github/workflows/tests.yml` gebruikt `composer setup` en daarna `composer ci:check`. Nieuwe migrations **MOETEN** als nieuwe bestanden worden toegevoegd en met tests op het testdatabasepad werken. Na route-/requestwijzigingen **MOET** Wayfinder opnieuw gegenereerd worden; gegenereerde bestanden staan in `.gitignore` en worden niet handmatig vastgelegd. De lintconfig in `vite.config.ts` negeert gegenereerde paden en een deel van de UI-primitieven; `npm run check` bewijst dus niet automatisch elke componentstijl.

## 21. Definition of Done

Een feature/PR is technisch gereed wanneer alle toepasselijke punten zijn gecontroleerd:

- [ ] Productregel en terminologie volgen [PRODUCT.md](PRODUCT.md); een nieuwe keuze is expliciet vastgelegd, niet geïmpliceerd.
- [ ] Routes staan in de juiste groep, zijn benoemd en hebben policy/Form Request-controles.
- [ ] Geneste records worden op parentrelatie gecontroleerd; serverautorisatie hangt niet af van UI-zichtbaarheid.
- [ ] Nieuwe mutaties staan in de juiste laag; multi-record-werk is atomair, concurrerende statuswijzigingen zijn veilig en relevante herhaling is idempotent.
- [ ] Servervalidatie dekt enums, IDs, datumvolgorde, bestandsregels en filter-/sorteerwaarden die geraakt worden.
- [ ] Models, casts, relaties, FK/deletegedrag, unique constraints en indexen zijn aangepast waar data verandert.
- [ ] Inertia-props zijn bewust beperkt; TypeScript-typen en Wayfinder-routes zijn bijgewerkt.
- [ ] Bestaande UI-primitieven, badges, formulieren en [style.md](../style.md) zijn gevolgd; mobiel, toetsenbord, feedback en dark mode zijn bekeken bij UI-werk.
- [ ] Feature-/berekeningstests dekken geslaagde flow, afwijzingen en relevante grenzen; geen N+1 of onbeheerst volume in nieuwe lijsten/rapporten.
- [ ] Bijlagen en secrets blijven privé; logs en props bevatten geen gevoelige data.
- [ ] `composer ci:check` is groen voor codewijzigingen; `npm run build` is uitgevoerd bij build-/assetwijzigingen. Afwijkingen zijn in de PR vermeld.
- [ ] PRODUCT.md, deze spec, TODO.md en ontwikkelhandleiding zijn bijgewerkt als hun onderwerp verandert.

### Bestaande automatische handhaving

| Regel | Huidige handhaving | Grens |
| --- | --- | --- |
| Formaat PHP | Pint via `composer lint:check` en CI | Toetst geen laaggrenzen. |
| PHP-typen | PHPStan/Larastan level 7 via `composer types:check` | Toetst geen productregel. |
| Frontend formaat/lint | Vite Plus `npm run check` met `denyWarnings` en type-aware lint | Gegenereerde en bepaalde UI-paden zijn uitgesloten. |
| Vue-typen | `vue-tsc --noEmit` via CI | Serverpropcontract kan nog runtime afwijken. |
| Gedrag | PHPUnit Feature-tests in CI | Dekt alleen beschreven cases. |
| Data-invarianten | FK's, unieke constraints, Form Requests, policies en Action-/modelchecks | Sommige regels leven alleen in applicatiecode. |
| CI | GitHub Actions op push `main` en PR | Geen aparte build-, architectuur- of frontend-E2E-gate. |

### Architectuurregels die we nog automatisch kunnen afdwingen

Deze voorstellen zijn **niet** geïmplementeerd:

1. Een architecture test of custom PHPStan-regel kan verbieden dat `app/Actions` Inertia/HTTP-controllerklassen importeert.
2. Een test kan alle domeinroutes inventariseren op `auth`/`verified` en aantoonbare policycontrole, met expliciete uitzonderingen voor profiel/auth. Voor e-mailverificatie is bovendien eerst het `MustVerifyEmail`-contract en een negatieve HTTP-test nodig.
3. Een test kan controleren dat alle `IssueStatus`- en `IssuePriority`-waarden overeenkomen met de TypeScript-unions/badges.
4. Een contracttest kan kritieke Inertia-props van dashboard en issue-detail tegen een typed schema of snapshot controleren; kies eerst een onderhoudbaar schema.
5. Een gerichte query-count-test kan dashboard, issueoverzicht en rapportage op nieuwe N+1-relaties bewaken.
6. Een aanvullende CI-stap `npm run build` kan bundel- en assetfouten vangen die typecheck/lint missen.
7. Een test kan de twee statusberekeningspaden bij lege/legacy-checklists gelijk trekken nadat PRODUCT.md de gewenste regel vastlegt.
8. Een static rule kan direct `window.confirm`, ongecontroleerde `v-html` en handgeschreven domein-URL's in niet-gegenereerde Vue-paden signaleren.

## 22. Bestaande afwijkingen en architectuurbeslissingen die nog nodig zijn

| Bevinding | Huidige plaats | Norm/benodigde keuze |
| --- | --- | --- |
| Grote controller coördineert ook postmortemtransactie, tijdlijnopslag en export. | `IssueController` | Nieuwe complexe mutaties krijgen Actions; bepaal bij toekomstig onderhoud of bestaande methoden stapsgewijs worden uitgeplaatst. |
| SLA-niveaus worden in een settingscontroller opgeslagen met transactie. | `Settings/SlaLevelController` | Bepaal of iedere configuratiemutatie met meerdere records een Action moet krijgen; voor nieuwe complexe mutaties geldt §4.3. |
| Templates controleren een invariant zowel in modelhook als Action. | `IssueChecklistTemplate`, `ManageIssueChecklistTemplates` | Bepaal of de dubbele bewaking gewenst is of centraal moet worden gemaakt, met behoud van racebescherming. |
| Lege verplichte checklist krijgt niet overal dezelfde status. | `CreateIssue`, `SyncIssueStatus` | Productbesluit nodig vóór technische consolidatie; zie PRODUCT.md §Geconstateerde inconsistenties. |
| Postmortemgedrag hangt van itemnaam af. | `UpdateIssueChecklistItemCompletion`, CSV-export | Productbesluit over expliciet type/marker nodig; bouw geen nieuwe naamherkenning. |
| Oude `project_team_member`-pivot bestaat naast drie responderkolommen. | migration versus `Project`/UI | Bepaal of pivot legacy is en veilig migreerbaar. |
| `customer_name` en `customer_id` leveren verschillende scherm-/exportdata. | `Project`, `IssueController` | Bepaal canonieke bron en eventuele historische snapshot vóór verdere mapping. |
| Gedeelde props doen per aangemelde pagina queries. | `HandleInertiaRequests` | Bepaal bij gemeten schaalproblemen of lazy props, cache of paginacontext nodig zijn. |
| Export en rapport laden hele selectie in geheugen. | `IssueController::export`, `BuildIssueReport` | Bepaal verwacht volume en streaming/aggregatiegrens voordat export wordt uitgebreid. |
| `config/app.php` staat op UTC; werkuren zijn globaal en invoer is lokaal geformuleerd. | datum-/SLA-paden | Bepaal tijdzonecontract en of SLA-afspraken historisch vastgelegd worden. |
| Zelfregistratie en brede policies bestaan naast een “intern” product. | Fortify, Policies | Productbesluit over toegangsmodel nodig vóór rollen/tenantisolatie. |
| `verified` staat op routes, maar `User` implementeert `MustVerifyEmail` niet. | `routes/*`, `User`, Laravel `EnsureEmailIsVerified` | Bepaal of verificatie verplicht is; voeg pas dan contract en negatieve toegangstest toe. |
| De huidige frontend heeft geen component-/E2E-testtool. | `package.json` | Bepaal bij groei van clientlogica welke runner en gate passend zijn. |

De vragen in deze tabel zijn geen stilzwijgende toestemming voor codewijziging. Leg een genomen productbesluit eerst vast in PRODUCT.md en pas daarna deze norm aan.

**Concrete keuzevragen voor onderhoud en review:**

1. Krijgen alle configuratiemutaties met meerdere records een Action, en worden de bestaande postmortem- en SLA-controllertransacties dan verplaatst?
2. Blijft de template-invariant bewust dubbel afgedwongen in modelhook en Action, of krijgt zij één centrale eigenaar met aantoonbare racebescherming?
3. Welk expliciet veld vervangt de naamherkenning van postmortemitems, als het product die stap werkelijk als type wil behandelen?
4. Blijft `customer_name` als historisch snapshot bestaan, of wordt `Customer.name` de enige klantbron voor detail en export?
5. Is e-mailverificatie verplicht vóór toegang tot domeinroutes; zo ja, welke bestaande accounts en tests moeten mee veranderen?
6. Welke tijdzone geldt voor invoer, werkuren, SLA-deadlines en historische weergave?
7. Vanaf welk selectievolume moeten CSV-export en rapportage streamen of aggregeren, en hoort dit synchroon te blijven?
8. Wanneer wordt frontendcomponent- of E2E-testen een verplichte gate, en met welke runner?
