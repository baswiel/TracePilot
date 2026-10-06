# TODO — uitvoerbare roadmap voor TracePilot

Dit is de roadmap voor het huidige interne storingenproduct. [PRODUCT.md](PRODUCT.md) bepaalt gedrag en domeintermen; [ARCHITECTURE.md](ARCHITECTURE.md) bepaalt de technische aanpak. Een taak is pas gereed wanneer de toepasselijke **Definition of Done** uit [ARCHITECTURE.md §21](ARCHITECTURE.md#21-definition-of-done) is gehaald. `[x]` = aangetroffen en gecontroleerd in code, relevante UI en tests; `[ ]` = ontbrekend, gedeeltelijk of nog te besluiten. Een afgevinkte deeltaak maakt een open hoofdtaak niet gereed. Besluiten zijn vragen, geen stilzwijgend vastgesteld beleid.

**Uitvoering:** fase 0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8. Werk binnen een fase in de aangegeven volgorde. Elke fase eindigt met een bruikbare flow. Nieuwe domeindata krijgt waar passend een factory/seeder en relevante gedragstests; de algemene testplicht uit de Definition of Done wordt niet bij iedere regel herhaald.

## Beslissingen nodig vóór uitvoering

- [ ] **Toegang, vóór fase 1:** passen zelfregistratie en gelijke rechten voor alle `User`-accounts bij intern gebruik, en is e-mailverificatie verplicht? Leg het toegangsmodel vast vóór policywerk (PRODUCT.md §Rollen en toegang / §Open vragen 1, 19; ARCHITECTURE.md §6, §12–13).
- [ ] **Teamlididentiteit, vóór meldingen:** wordt `TeamMember` aan `User` gekoppeld en veroorzaken vermeldingen/toewijzingen een notificatie (PRODUCT.md §Open vragen 2)?
- [ ] **Projectcontext, vóór fase 2-afronding:** is een klant vereist; welke klantnaam is leidend of historisch; moeten na verwijdering drie responders bestaan; mag een behandelaar daarbuiten vallen; heeft de oude pivot betekenis (PRODUCT.md §Open vragen 4–6, 16)?
- [ ] **Incidentregels, vóór fase 3-afronding:** wat betekenen `p1`–`p4`, wie mag prioriteit wijzigen en welke correctie/audit van meld-, reactie- en oplostijd is toegestaan (PRODUCT.md §Open vragen 3, 7)?
- [ ] **Checklist/postmortem, vóór fase 4-afronding:** bepaal status bij nul verplichte items, n.v.t. op het oplossingsitem, postmortem na heropenen, echte verzendsemantiek en of actiepunten afronding blokkeren (PRODUCT.md §Open vragen 8–12).
- [ ] **SLA-contract, vóór fase 5-afronding:** leg historische snapshot versus herberekening, tijdzone/feestdagen/projecturen en de eenheid en contractwaarden van seedniveaus vast (PRODUCT.md §Open vragen 13–15).
- [ ] **Levenscyclus, vóór fase 8:** mogen accounts met aangemaakte storingen weg en wie neemt `created_by` over; welke audit- en bewaartermijnen gelden (PRODUCT.md §Open vragen 17; ARCHITECTURE.md §12, §15)?
- [ ] **Externe grenzen, vóór eventuele fase 7-uitbreiding:** blijven kennisbankvlag en _Storing Log_-item handmatig of komt er een integratie (PRODUCT.md §Open vragen 18; ARCHITECTURE.md §14)?

## Fase 0 — Reproduceerbaar fundament

**Resultaat:** de app start lokaal en de kwaliteitsgate kan op een ondersteunde runtime draaien.

- [x] **Applicatiebasis:** Laravel 13, Inertia 3, Vue 3/TypeScript, Tailwind 4, Wayfinder, migrations en SQLite-testconfiguratie zijn aanwezig (ARCHITECTURE.md §1, §20).
- [x] **Bestaande checks:** PHPUnit Feature-tests, Pint, PHPStan/Larastan, frontend lint/format en Vue-typecontrole zijn in `composer ci:check`/CI geconfigureerd (ARCHITECTURE.md §10, §20–21).
- [x] **Demo-startpunt:** seeders leveren account, klanten, teamleden, projecten, SLA-niveaus en checklisttemplates (ARCHITECTURE.md §11).
- [ ] **Reproduceerbare gate:** leg Node 22+ vast in lokale setup/CI en draai `composer ci:check` daarmee; lokale Node 21.6.2 stopt op `node:util.styleText`. Met gebundelde Node stopt de gate op opmaak van `docs/ARCHITECTURE.md`, `docs/PRODUCT.md` en `CustomerForm.vue`; `php artisan test` heeft 103 geslaagde tests, 4 failures en 1 error. Herstel de vijf regressies in hun fases en rapporteer de volledige gate opnieuw (ARCHITECTURE.md §20–21).
- [ ] **Ontwikkeldocumentatie:** stem README en `docs/Development.md` af op `UserSeeder`, actuele demo-projecten, startstappen en kwaliteitscommando's; ruim afwijkende oude TODO/productverwijzingen op (PRODUCT.md §Geconstateerde inconsistenties I7).
- [ ] **Gerichte CI-uitbreiding:** voeg `npm run build` toe en later route-/policy- en status/priority-contractchecks zodra fase 1/4-regels zijn besloten; bewerk gegenereerde Wayfinder-bestanden niet handmatig (ARCHITECTURE.md §21).

## Fase 1 — Veilige accounttoegang

**Afhankelijk van:** toegangsbesluit. **Resultaat:** alleen bedoelde accounts bereiken domeinfuncties.

- [x] **Accountbasis:** login/logout, registratie, wachtwoordherstel, profiel en wachtwoordwijziging hebben Fortify-flow, UI en Feature-tests; gasten worden van domeinpagina's geweerd (PRODUCT.md §4.7; ARCHITECTURE.md §6).
- [x] **Aanwezige extra flows:** verificatiescherm/notificatie, 2FA-instelling/challenge en passkeyconfiguratie bestaan; tests dekken verificatie en delen van 2FA (PRODUCT.md §4.7).
- [ ] **E-mailverificatie als toegangseis, indien gekozen:**
    - [x] Domeinroutes hebben `verified` middleware en de verificatieflow heeft tests.
    - [ ] `User` implementeert `MustVerifyEmail` niet: voeg het contract en een negatieve HTTP-test toe zodra verificatie vereist is; nu passeert een niet-geverifieerde gebruiker (PRODUCT.md §Geconstateerde inconsistenties I9).
- [ ] **Toegangsmodel afronden:** toets brede policies voor `Customer`, `Project`, `TeamMember`, `Issue`, instellingen en export aan gekozen rechten; wijzig serverautorisatie, eventuele queryscope, UI-acties en negatieve tests samen. `Customer` is geen tenantgrens (ARCHITECTURE.md §4.5, §12–13).
- [ ] **Accountverwijdering met issuehistorie:** test/verwerk een account met `issues.created_by`; de restrictieve FK kan de bestaande verwijderflow blokkeren. Implementeer een gekozen overdracht of begrijpelijke blokkade (PRODUCT.md §Open vragen 17; ARCHITECTURE.md §15).
- [ ] **Securityregressies:** test directe domein-URL's, gekozen policyweigering, verificatie en relevante registratie/rate-limit- en 2FA/passkey-foutpaden (ARCHITECTURE.md §10, §12, §21).

## Fase 2 — Klanten, projecten en responders als betrouwbare context

**Afhankelijk van:** fase 1 en projectcontextbesluit. **Resultaat:** een storing krijgt eenduidige klant-, project- en respondercontext.

- [ ] **Klantenbeheer conform huidige Definition of Done:**
    - [x] Lijst, aanmaken, hernoemen en verwijderen zonder projecten; unieke naam, validatie en Feature-tests bestaan (PRODUCT.md §4.1).
    - [ ] Los de actuele `CustomerManagementTest`-regressies op: redirect na aanmaken wijkt af en `Customer::factory()` ontbreekt; verifieer route, factory en klantflow samen (ARCHITECTURE.md §10–11).
- [x] **Projectbeheer:** zoeken/filteren/pagineren, maken/wijzigen, klant/contact/SLA kiezen, deactiveren en detail met lopende/afgeronde storingen; drie verschillende responders zijn bij maken/wijzigen vereist (PRODUCT.md §4.1).
- [x] **Teamleden/responders:** `TeamMember`-CRUD, toewijzing, responderoverzicht en verwijdering met `null` op verwijzingen bestaan met Feature-tests (PRODUCT.md §4.1).
- [ ] **Eenduidige klantbron in alle schermen:**
    - [x] `customer_id` voedt klant/projectoverzichten; oud `customer_name`-veld bestaat.
    - [ ] Breng na besluit detail, CSV, formulieren en rapport op één bron of expliciet historisch snapshot; migreer veilig en test ontbrekende/gewijzigde klanten (PRODUCT.md §Geconstateerde inconsistenties I1; ARCHITECTURE.md §22).
- [ ] **Responderintegriteit na verwijderen:** maak gekozen regel voor drie actuele responders ook na `TeamMember`-verwijdering waar; toon getroffen projecten en test verwijderen, archiveren en toewijzen (PRODUCT.md §Open vragen 5–6).
- [ ] **Legacy-pivot:** inventariseer data in `project_team_member`; behoud met productbetekenis of bouw via nieuwe migration/data-/rollbackplan af. Herschrijf geen gedeelde migration (PRODUCT.md §Geconstateerde inconsistenties I5; ARCHITECTURE.md §15).
- [ ] **Contextseed afronden:** houd klant, drie onderscheiden responders, actief/inactief project en beide SLA-vormen representatief voor gekozen regels; test seedflow (ARCHITECTURE.md §11).

## Fase 3 — Storing melden en onderhouden

**Afhankelijk van:** fase 2 en incidentregels. **Resultaat:** actuele en historische storingen zijn betrouwbaar te registreren en te corrigeren.

- [x] **Normale melding:** actief project, titel, `p1`–`p4`, meldtijd en optionele omschrijving zijn gevalideerd; `CreateIssue` schrijft issue, checklistkopie en activiteit atomair; UI en Feature-tests bestaan (PRODUCT.md §4.2).
- [x] **Historische melding conform huidige Definition of Done:**
    - [x] Backend/UI voor reactie-/oplostijd, status, oplossing, oorzaak, notitie en checklistselectie, met validatie en rollbacktest, zijn aanwezig (PRODUCT.md §4.2).
    - [x] Historische `first_response_recorded.created_at` bewaart de gebeurtenistijd; tijdlijn en SLA zijn samen getest (PRODUCT.md §4.2).
- [x] **Detail en bijwerken conform huidige Definition of Done:**
    - [x] Formulier/server voor titel, omschrijving, prioriteit, behandelaar, vlaggen en mijlpaaltijden, idempotente activiteit en eenmalige eerste reactie bestaan (PRODUCT.md §4.2).
    - [x] Detailupdate bewaart titel, omschrijving, behandelaar, vlaggen en mijlpaaltijden; de volledige request/action-flow is getest.
- [ ] **Correcties en audit sluitend:**
    - [x] `UpdateIssueRequest` valideert tijdsvolgorde; `issue_updated` schrijft gewijzigde veldnamen.
    - [ ] Trek na besluit `UpdateIssueDetails`-contract, UI, historie en SLA-weergave gelijk; bewaar oude/nieuwe waarde, actor en reden waar vereist; test terugzetten en tijdscorrecties (PRODUCT.md §Geconstateerde inconsistenties I2).
- [ ] **Prioriteit met betekenis:** toon gekozen impact-/urgentieregels en wijzigrecht in melden/bewerken; houd validatie, badges, rapport en demo-issues consistent (PRODUCT.md §Open vragen 3).
- [ ] **Groeibare demo-/testflow:** voeg representatieve actieve/historische issues toe zodra regels veranderen en bewaak dat alleen actieve projecten meldingen accepteren (ARCHITECTURE.md §10–11).

## Fase 4 — Checklist, oplossing, tijdlijn en postmortem

**Afhankelijk van:** fase 3 en checklist-/postmortembesluiten. **Resultaat:** oplossing en nazorg geven een consistente, controleerbare status.

- [x] **Templates/snapshots:** beheren/verplaatsen/deactiveren, minstens één actief verplicht item, hoogstens één oplossingsmarker; alleen nieuwe storingen kopiëren actieve templates (PRODUCT.md §4.3).
- [x] **Checklistmutatie:** voltooien, heropenen, n.v.t.; parent-issue binding, atomaire opslag, idempotentie, actor en statusovergangen zijn getest (PRODUCT.md §4.3).
- [x] **Tijdlijn/bijlage:** opmerkingen/besluiten met teamlidvermelding, private bijlage met type-/groottevalidatie en geautoriseerde downloadroute bestaan (PRODUCT.md §4.4).
- [x] **Postmortemopslag conform huidige Definition of Done:**
    - [x] Oorzaak, impact, maximaal twintig vervangbare actiepunten, UI, routeguard en Feature-test bestaan (PRODUCT.md §4.4).
    - [x] Postmortemtest gebruikt de vereiste vlag; routevoorwaarden, vreemde actiepunten en rollback zijn getest.
- [ ] **Statusinvariant voor elke checklist:**
    - [x] `open` → `handling` → `completed` en heropenen zijn geïmplementeerd.
    - [ ] Maak de gedeelde `SyncIssueStatus::determineStatus`-berekening uniform voor nul verplichte items/legacy-snapshots; test overgangen, n.v.t. van oplossing en concurrentie volgens besluit (PRODUCT.md §Geconstateerde inconsistenties I3; ARCHITECTURE.md §21).
- [ ] **Postmortemstap zonder naamherkenning:** vervang naamzoekactie in oplosactie/CSV door gekozen expliciete marker, met migratie voor oude snapshots; test hernoemde/vertaalde templates. Maak ‘verstuurd’ alleen een echte status na verzendbesluit (PRODUCT.md §Geconstateerde inconsistenties I4).
- [ ] **Levenscyclus bij heropenen:** laat `postmortem_required`, bestaande `IssuePostmortem`, UI en n.v.t.-stap gekozen beleid volgen; test heropenen, opnieuw oplossen en bestaande actiepunten (PRODUCT.md §Geconstateerde inconsistenties I8).
- [ ] **Actiepunten/verzending indien besloten:** voeg blocking/voortgang of een echte verzendflow inclusief ontvanger, afleverstatus, foutpad en idempotentie toe volgens gekozen productregel (PRODUCT.md §Open vragen 11–12; ARCHITECTURE.md §14).
- [ ] **Template-invariant bij onderhoud:** beoordeel dubbele bewaking in modelhook en `ManageIssueChecklistTemplates`; behoud racebescherming en test mutaties van laatste vereiste/marker (ARCHITECTURE.md §22).

## Fase 5 — SLA als betrouwbaar contract

**Afhankelijk van:** fase 3-mijlpalen en SLA-besluit. **Resultaat:** reactie-/oplosdeadlines en historie zijn verklaarbaar.

- [x] **Configuratie:** `SlaLevel` met vier prioriteitsdoelen, eigen projectminuten als alternatief, globale `BusinessHours`-UI en seedniveaus bestaan met Feature-tests (PRODUCT.md §4.5).
- [x] **Berekening:** `CalculateIssueSla` rekent reactie/oplossing in werkminuten en levert `on_track`, `at_risk`, `overdue`, `met`, `breached` of `unavailable`; relevante grensgevallen zijn getest (PRODUCT.md §4.5).
- [ ] **Seeddoelen corrigeren/duiden:** gezaaide ‘werkdag’ van 1.440 minuten duurt bij 09:00–17:00 drie werkdagen; pas na contractbesluit seed, UI-uitleg, bestaande data en tests aan (PRODUCT.md §Geconstateerde inconsistenties I6).
- [ ] **Historisch SLA-beleid:** snapshot doelen/werkuren op meldmoment óf maak herberekening van oude issues expliciet; test project-/niveau-/urenwijziging na een melding (PRODUCT.md §Open vragen 13).
- [ ] **Tijdzone/kalender:** definieer invoer-/weergavetijdzone; voeg gekozen feestdagen/projecturen toe met configuratie, tests rond daggrenzen/DST en passende demo-data (PRODUCT.md §Open vragen 14; ARCHITECTURE.md §15).
- [x] **Atomaire niveauwijziging:** `SaveSlaLevel` slaat niveau en doelen in één transactie op, lockt updates en heeft een rollbacktest (ARCHITECTURE.md §4.3).

## Fase 6 — Dagelijks overzicht, rapporten en export

**Afhankelijk van:** fasen 3–5. **Resultaat:** actuele werkvoorraad en historische prestaties worden consistent uitgelezen.

- [x] **Dashboard:** statusaantallen, actuele storingen, prioriteit/meldvolgorde, serverfilters, paginering en SLA-aandacht hebben UI/Feature-tests (PRODUCT.md §4.6).
- [x] **Storingsoverzicht:** zoeken/filteren/sorteren via toegestane velden, paginering en CSV van gefilterde selectie hebben tests (PRODUCT.md §4.6).
- [x] **Rapportage:** gekozen/rollende periode, projectfilter, vorige periode, volume, prioriteit, oorzaak, trends, tijden en SLA-/projectresultaten hebben tests (PRODUCT.md §4.6).
- [ ] **Consistente uitvoer:** neem gekozen klantbron, postmortemmarker en historisch-SLA-beleid over in detail, CSV en rapport; test lege/gewijzigde relaties (PRODUCT.md §Geconstateerde inconsistenties I1, I4).
- [x] **Batchverwerking:** CSV streamt en rapportage aggregeert in batches van 250; regressietests overschrijden een batch en controleren aantallen en uitvoer (ARCHITECTURE.md §16, §22). Productievolumes en geheugen blijven te meten bij grotere uitrol.
- [ ] **Queryregressie:** query-count-tests voor dashboard, issueoverzicht en rapport; controleer eager loading/indexen en meet gedeelde `HandleInertiaRequests`-queries (ARCHITECTURE.md §16, §21).
- [ ] **Kernpropcontract:** kies onderhoudbaar schema voor Inertia-props van dashboard/issuedetail en test de server-TS-grens die `vue-tsc` niet bewijst (ARCHITECTURE.md §8.1, §21).

## Fase 7 — Expliciete externe of geautomatiseerde stappen

**Afhankelijk van:** relevante productbesluiten. **Resultaat:** handmatige registratie is eerlijk benoemd; een besloten integratie heeft een betrouwbaar foutpad.

- [x] **Huidige grenzen:** Fortify-accountmail, private lokale bestanden en handmatige CSV bestaan; geen Google Sheets API, domeinjob, webhook, kennisbank- of Storing Log-koppeling (PRODUCT.md §5; ARCHITECTURE.md §14).
- [ ] **Claims rechtzetten:** maak in UI/CSV/docs duidelijk dat `knowledge_base_recorded`, _Toegevoegd aan Storing Log_, vermeldingen en _Postmortem verstuurd_ nu handmatige registraties zijn, zonder aantoonbare externe actie (PRODUCT.md §4.2–4.4).
- [ ] **Alleen bij besloten koppeling:** definieer per kennisbank/Storing Log-, meldings- of verzendflow bron van waarheid, secrets, timeout, retry/idempotentie, rate limit, foutfeedback, logging en fake-tests; voeg queue/failed-job-herstel alleen voor echte async-stappen toe (ARCHITECTURE.md §14, §17).

## Fase 8 — Hardening en grotere uitrol

**Afhankelijk van:** kernfases en toegang-/levenscyclusbesluiten. **Resultaat:** herstel, privacy, security en prestatie zijn aantoonbaar passend bij gekozen gebruik.

- [x] **Bestaande basis:** auth-middleware, policies, Form Requests, private downloads, FK/unique/index-constraints, Laravel logging en CI bestaan (ARCHITECTURE.md §12, §15, §17, §21).
- [ ] **Autorisatie en export:** negatieve tests voor directe routes/geneste records; als klantisolatie wordt gekozen, ontwerp tenantidentiteit en dek queries, export, bijlagen, cache en routebinding met lektests. Een klantfilter is geen autorisatie (ARCHITECTURE.md §12–13).
- [ ] **Bestanden/privacy:** toets MIME/type/size en mislukte opslag van bijlagen; leg retentie/verwijderen van incidenttekst, bestanden, activiteiten en accounts vast en test gekozen audit-/deletegedrag (ARCHITECTURE.md §12, §15).
- [ ] **Operationeel herstel:** documenteer database-/bestandsbackup en test restore voor gekozen hosting; failed-jobprocedure alleen bij gebruikte queues; log veilige context zonder klanttekst, secrets of bestandsinhoud (ARCHITECTURE.md §17).
- [ ] **Releasegate/toegankelijkheid:** draai `composer ci:check` en frontendbuild groen op ondersteunde runtime; toets hoofdflows op toetsenbord, mobiel, dark mode en foutfeedback volgens `style.md`; beslis op basis van clientcomplexiteit over component-/E2E-gate (ARCHITECTURE.md §8.4, §10, §21).

## Technische schuld / architectuurconvergentie

- [x] **`IssueController`-workflows:** postmortem en bijlageopslag zijn verplaatst naar Actions; gestreamde CSV is afzonderlijk getest (fasen 4, 6; ARCHITECTURE.md §22).
- [x] **Template-invariant leeft in modelhook én Action:** beide verdedigingslagen zijn verantwoord in ARCHITECTURE.md §22. Productiedatabase-concurrentietests blijven vervolgwerk.
- [x] **SLA-doelen:** `SaveSlaLevel` beheert de multi-record-transactie met rollbacktest (ARCHITECTURE.md §22).
- [ ] **Legacy datacontracten `customer_name`/`customer_id` en `project_team_member`:** productbesluit, veilige migratie en tests zijn nodig vóór opruimen (fase 2; PRODUCT.md §Geconstateerde inconsistenties I1, I5).
- [ ] **Geen automatische laag-/serverpropbewaking:** voeg alleen concrete architectuur-/contracttests uit fasen 0, 1, 6 toe die regressies ontdekken (ARCHITECTURE.md §21).

## Later / backlog — niet toegezegde productuitbreidingen

- [ ] **Voorstel:** escalatie volgens eerste/tweede/derde responder, pas na besluit over bezetting en meldingen (PRODUCT.md §Open vragen 2, 5).
- [ ] **Voorstel:** klantaccounts of multi-tenancy, alleen met expliciet toegang-/isolatieontwerp en lektests (PRODUCT.md §Rollen en toegang; ARCHITECTURE.md §13).
- [ ] **Voorstel:** automatische trenddetectie, actiepuntherinneringen of projectgebonden SLA-kalenders, na productbesluit en aangetoonde behoefte (PRODUCT.md §Open vragen 12, 14).
- [ ] **Voorstel:** directe Google Sheets-, kennisbank- of Storing Log-integratie in plaats van CSV/handmatige vlaggen, na contract- en eigenaarschapsbesluit (PRODUCT.md §5, §Open vragen 18; ARCHITECTURE.md §14).

## Architectuuronderhoud — uitgevoerd op 30 september 2026

- [x] Historische tijdlijnmomenten opslaan en bestaande mijlpaaltijden bij bewerken behouden.
- [x] SLA-niveau en doelen atomair opslaan met lock en rollbacktest; loaded targets hergebruiken.
- [x] Actuele klantnaam gebruiken in detail, meldopties en CSV, met legacy fallback.
- [x] Tijdlijn- en postmortemworkflows naar Actions verplaatsen; bijlage-opruiming en negatieve parent-/bestandstests toevoegen.
- [x] Projectfilterrequest en werkurenpolicy toevoegen; props beperken en PHP-/Vue-typen aanscherpen.
- [x] Klant-, team-, responder- en klantprojectlijsten pagineren; CSV, rapportage en SLA-teller in batches verwerken.
- [x] Issue-details, tijdlijn, postmortem en SLA-weergave extraheren; gedeelde typen en helpers gebruiken.
- [x] Browserbevestigingen vervangen en veldfouten toegankelijk koppelen; lokale seed- en CI-documentatie corrigeren.
- [ ] Lege-checkliststatus definitief gelijk trekken na productbesluit. Berekening is gedeeld; bestaand gedrag blijft expliciet behouden.
- [ ] Eventuele verdere opsplitsing van dashboard en rapportage, een volledige frontend-E2E-gate en concurrentiebewijs op de productiedatabase blijven vervolgwerk.

De behouden toegang-, SLA-, postmortem- en pivotkeuzes staan in PRODUCT.md onder bevestigde onderhoudsbesluiten. Bovenstaande afgeronde technische stappen vervangen oude open onderhoudspunten waar zij hetzelfde werk beschrijven; productuitbreidingen worden hiermee niet toegezegd.
