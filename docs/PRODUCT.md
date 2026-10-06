# Product- en domeinspecificatie — TracePilot

Dit document beschrijft wat TracePilot voor zijn gebruikers doet en welke productregels de huidige repository aantoonbaar uitvoert. Het is de bron voor domeintermen: gebruik de [woordenlijst](#3-woordenlijst) bij UI-tekst, nieuwe features en datamodellen. Technische opbouw en ontwikkelafspraken staan in [ARCHITECTURE.md](ARCHITECTURE.md) en [Development.md](Development.md); de huidige functionele inventaris staat in [TODO.md](TODO.md). Waar de code geen eenduidige bedoeling toont, staat **(Nog bevestigen.)** of een vraag onder [Open vragen](#open-vragen). Dit document is een beschrijving van de huidige werking, geen nieuw vastgesteld beleid.

## 1. Het idee

TracePilot is een intern dashboard voor het registreren, behandelen en evalueren van storingen bij projecten van klanten. Een gebruiker legt een storing vast voor een actief project, wijst desgewenst een behandelaar aan, registreert de eerste reactie en technische oplossing en rondt nazorg af via een checklist. Het dashboard en de rapportages geven zicht op lopend werk en reactie- en oplostijden.

De code en seedgegevens wijzen op een team dat meerdere klantprojecten beheert. De exacte commerciële doelgroep en of klanten zelf toegang moeten krijgen, zijn niet vastgelegd. **(Nog bevestigen.)**

### Productprincipes die uit de werking blijken

1. **Snel melden, later aanvullen.** Voor een nieuwe storing zijn project, titel, prioriteit en meldtijd voldoende; details kunnen op de detailpagina worden bijgewerkt.
2. **Projectcontext bij elke storing.** Elke storing hoort bij precies één project. Het project levert klantcontext, contactgegevens, responders en SLA-afspraken.
3. **Afhandeling is controleerbaar.** De status volgt uit checklistacties; een gebruiker kiest bij normale behandeling geen losse status.
4. **Bestaande storingen houden hun checklist.** Actieve templates worden bij registratie gekopieerd. Latere templatewijzigingen veranderen die kopie niet.
5. **Reactie en oplossing zijn verschillende mijlpalen.** Beide kunnen een afzonderlijk SLA-doel hebben en worden in werkminuten beoordeeld.
6. **Terugkijken is onderdeel van het product.** Tijdlijn, postmortem, historische registratie, CSV-export en periodeverslagen ondersteunen reconstructie en analyse.

### Rollen en toegang

| Rol                 | Wie                                                                       | Wat de huidige applicatie toestaat                                                                                                                                            |
| ------------------- | ------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bezoeker            | Niet aangemeld                                                            | Aanmelden, registreren, wachtwoordherstel en verificatieflow; geen domeinpagina's.                                                                                            |
| Gebruiker / account | `User`                                                                    | Na aanmelden: alle klanten, projecten, teamleden, storingen, SLA's, templates, rapporten en werkuren bekijken of beheren. Policies maken geen beheerder- of klantonderscheid. |
| Teamlid             | `TeamMember`                                                              | Een beheerd contact voor toewijzing, responderrol, vermelding en postmortemactie. Is geen `User` en heeft daardoor niet automatisch een login.                                |
| Projectresponder    | `Project.first_responder_id`, `second_responder_id`, `third_responder_id` | Eerste, tweede of derde vaste contactpersoon van een project. De rangorde is zichtbaar; automatische escalatie is niet aanwezig.                                              |
| Klantcontact        | `Project.contact_*`                                                       | Contactgegevens bij een project, zonder account of eigen toegangsrechten.                                                                                                     |

Registratie van nieuwe `User`-accounts staat aan. Welke organisatie iemand mag vertegenwoordigen wordt niet gemodelleerd. **(Nog bevestigen.)**

## 2. Kernflow

```text
Klant en project beheren ──► drie responders en SLA/werkuren instellen
           │
           ▼
Storing melden voor actief project ──► actieve checklist kopiëren
           │                              │
           ▼                              ▼
Eerste reactie vastleggen          open
           │                              │ oplossingsitem voltooid
           └──────────────► technische oplossing ──► handling
                                              │ verplichte items voltooid
                                              ▼
                                           completed
                                              │
                                              ▼
                                   rapportage / CSV / postmortem
```

Een storing kan ook **achteraf** worden geregistreerd met werkelijke meld-, reactie- en oplostijden en een vooraf ingevulde checklist. De status moet dan overeenkomen met de gekozen checklistitems. Heropenen van een checklistitem kan een afgeronde storing terugzetten naar `handling` of `open`.

## 3. Woordenlijst

| Domeinterm              | Code                                                           | Betekenis en nuance                                                                                                                                                                   |
| ----------------------- | -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Klant                   | `Customer`, `customers`                                        | Organisatie waaraan nul of meer projecten gekoppeld zijn; naam is uniek.                                                                                                              |
| Project                 | `Project`, `projects`                                          | Context voor storingen, klant, contact, responders en SLA. Kan actief of inactief zijn.                                                                                               |
| Legacy klantnaam        | `Project.customer_name`                                        | Los tekstveld naast `customer_id`; uitsluitend fallback wanneer geen klantrelatie bestaat. De actuele `Customer.name` is leidend.                                                     |
| Storing / incident      | `Issue`, `issues`                                              | Geregistreerde gebeurtenis met project, prioriteit, status en tijdstempels. De UI gebruikt vooral _storing_; code en CSV ook _issue_ of _incident_.                                   |
| Prioriteit              | `IssuePriority`, `Issue.priority`                              | Exact `p1`, `p2`, `p3`, `p4`; numeriek lager betekent hogere prioriteit in overzichten. Een inhoudelijke urgentiedefinitie ontbreekt.                                                 |
| Oorzaaktype             | `IssueCause`, `Issue.cause`                                    | Classificatie voor analyse: `internal_knowledge_gap`, `customer_knowledge_gap`, `user_error`, `code_defect`, `configuration_error`, `infrastructure`, `external_dependency`, `other`. |
| Status                  | `IssueStatus`, `Issue.status`                                  | `open` = technisch niet opgelost, `handling` = opgelost met open verplichte nazorg, `completed` = verplichte checklist klaar.                                                         |
| Meldtijd                | `Issue.reported_at`                                            | Startpunt voor beide SLA-mijlpalen en voor selectie in rapportages.                                                                                                                   |
| Eerste reactie          | `Issue.first_responded_at`                                     | Vastgelegd eerste reactiemoment; geen reactie-inhoud of ontvanger vereist.                                                                                                            |
| Technische oplossing    | `Issue.resolved_at`                                            | Mijlpaal die samenhangt met het voltooide oplossingsitem. Verschilt van afronding.                                                                                                    |
| Afronding               | `Issue.completed_at`                                           | Tijdstip waarop de vereiste checklist is afgerond.                                                                                                                                    |
| Toegewezen behandelaar  | `Issue.team_member_id` → `TeamMember`                          | Eén optionele behandelaar; hoeft niet een van de drie projectresponders te zijn.                                                                                                      |
| Checklisttemplate       | `IssueChecklistTemplate`, `issue_checklist_templates`          | Globale definitie met naam, verplichtheid, oplossingsmarker, actief-vlag en volgorde.                                                                                                 |
| Checklistitem           | `IssueChecklistItem`, `issue_checklist_items`                  | Kopie bij één storing; bewaart de template-eigenschappen en eigen voltooiingsgegevens.                                                                                                |
| Oplossingsitem          | `marks_issue_resolved`                                         | Checklistitem waarvan voltooien de technische oplossing markeert. Maximaal één actief template is toegestaan.                                                                         |
| Niet van toepassing     | `is_not_applicable`                                            | Een checklistitem wordt als voltooid geteld, maar herkenbaar als overgeslagen gemarkeerd.                                                                                             |
| Tijdlijnactiviteit      | `IssueActivity`, `issue_activities`                            | Gebeurtenis, opmerking of besluit bij een storing; kan metadata voor mentions en bijlage bevatten.                                                                                    |
| Postmortem              | `IssuePostmortem`, `issue_postmortems`                         | Oorzaak en impact bij een storing, uitsluitend via de route als `postmortem_required` waar is.                                                                                        |
| Postmortemactie         | `PostmortemActionItem`, `postmortem_action_items`              | Actie met optionele teamlid-eigenaar, vervaldatum en voltooiingstijd.                                                                                                                 |
| SLA-niveau              | `SlaLevel`, `sla_levels`                                       | Genoemd pakket met doelen per prioriteit.                                                                                                                                             |
| SLA-doel                | `SlaTarget`, `sla_targets`                                     | Reactie- en oplosminuten voor één prioriteit binnen een SLA-niveau.                                                                                                                   |
| Eigen projectdoel       | `Project.sla_first_response_minutes`, `sla_resolution_minutes` | Alternatieve doelen wanneer geen SLA-niveau is gekoppeld.                                                                                                                             |
| Werkuren                | `BusinessHours`, `business_hours`                              | Applicatiebrede werkdagen en dagvenster voor SLA-berekening.                                                                                                                          |
| SLA-toestand            | `CalculateIssueSla`                                            | `unavailable`, `on_track`, `at_risk`, `overdue`, `met`, `breached`, afzonderlijk voor reactie en oplossing.                                                                           |
| Trendmarkering          | `Issue.is_trend`                                               | Handmatige ja/nee-vlag op een storing; geen automatisch ontdekte trend.                                                                                                               |
| Kennisbankregistratie   | `Issue.knowledge_base_recorded`                                | Handmatige ja/nee-vlag; geen koppeling met een kennisbanksysteem.                                                                                                                     |
| Gebruiker               | `User`, `users`                                                | Login en actor van activiteiten, registratie en checklistacties.                                                                                                                      |
| Teamlid                 | `TeamMember`, `team_members`                                   | Apart personeels-/contactrecord zonder verplichte accountrelatie.                                                                                                                     |
| Oude responderkoppeling | `project_team_member`                                          | Pivot uit een migration; huidige schermen en modellen gebruiken de drie responderkolommen.                                                                                            |

### Terminologie die aandacht nodig heeft

- **Storing, incident, issue:** routes en primaire schermen noemen het een storing, code gebruikt `Issue`, CSV heet incidenten en activiteiten spreken over _issue_. Kies één productterm voor nieuwe UI-tekst; laat codebenamingen expliciet in de woordenlijst staan.
- **Afhandeling versus afronding:** `handling` betekent dat de technische oplossing is vastgelegd maar verplichte nazorg nog open is; `completed` betekent niet alleen dat de storing technisch werkt.
- **Klantnaam versus klant:** `customer_name` is geen synoniem voor de relatie `customer_id` en kan afwijken.
- **Postmortem verstuurd:** de standaardtemplate draagt deze naam, maar er is geen verzendactie. De vlag en checklist registreren een toestand, geen aantoonbare verzending.
- **Rapide-contacten:** CSV gebruikt deze organisatiegebonden naam; de rest van het product spreekt van teamleden/responders.

## 4. Domeinmodel

`*` betekent nul of meer; `+` één of meer. Optionele relaties zijn aangeduid met `?`.

```text
Customer ── Project* ── Issue*
                │            ├── IssueChecklistItem*
                │            ├── IssueActivity*
                │            ├── IssuePostmortem? ── PostmortemActionItem*
                │            ├── TeamMember? (behandelaar)
                │            └── User (melder/aanmaker)
                ├── TeamMember? × 3 (eerste/tweede/derde responder)
                └── SlaLevel? ── SlaTarget+ (per prioriteit)

IssueChecklistTemplate* ── snapshot bij creatie ──► IssueChecklistItem*
BusinessHours (globaal) ──► SLA-berekening voor alle Issues
```

### 4.1 Klanten, projecten en responders

Een ingelogde gebruiker kan een klant aanmaken of hernoemen. De naam is verplicht, maximaal 255 tekens en uniek. Verwijderen mag uitsluitend wanneer de klant geen projecten heeft. Een project mag zonder gekoppelde klant bestaan. Het project houdt een naam, optionele beschrijving, optionele klantrelatie, contactpersoon, e-mail en telefoon, SLA-configuratie en actieve status bij.

Bij aanmaken en volledig wijzigen van een project zijn drie **verschillende** bestaande teamleden als eerste, tweede en derde responder verplicht. Een project blijft na deactivering bewaard en zichtbaar. Nieuwe storingen kunnen alleen voor actieve projecten worden gemeld; bestaande storingen van een inactief project blijven beschikbaar. De UI noemt deactiveren _archiveren_. Er is geen projectverwijderroute, hoewel de policy verwijdering zonder storingen toestaat. Klant-, team- en responderlijsten tonen twintig records per pagina. De klantdetailpagina pagineert projecten met twintig records. Projectlijsten kunnen op naam/klant worden gezocht en op actief/inactief worden gefilterd; de detailpagina toont recente lopende en afgeronde storingen.

Een teamlid heeft een verplichte naam en optioneel geldig e-mailadres, maar geen login. Elk aangemeld account kan teamleden beheren. Bij verwijderen valt de behandelaartoewijzing van storingen weg en worden vaste responderverwijzingen in projecten `null`; historische tijdlijnvermeldingen bewaren eerder gekopieerde naam en ID. Een project kan daardoor minder dan drie actuele responders overhouden. De pagina _Responders_ toont per teamlid de toegewezen projecten en rangorde.

### 4.2 Storing registreren en onderhouden

Een aangemelde gebruiker meldt een storing met een actief project, titel (maximaal 255 tekens), `p1`–`p4` en meldtijd. Omschrijving is optioneel. De gebruiker wordt `created_by`; de nieuwe storing en de eerste activiteit worden samen met de checklistkopie opgeslagen. Normale registratie start in `open`, tenzij meegestuurde checklistgegevens de status anders afleiden. De detailpagina laat titel, omschrijving, prioriteit, behandelaar, meldtijd, eerste reactie, oplostijd, kennisbankvlag en trendvlag wijzigen. Een wijziging schrijft een `issue_updated`-activiteit met gewijzigde veldnamen; een identieke inzending schrijft geen activiteit. Correcties van meld-, reactie- en oplostijden bewaren de oude en nieuwe waarden in `metadata.timestamp_changes`.

Een behandelaar is optioneel en kan elk bestaand teamlid zijn. Er is geen autorisatie op projectlidmaatschap of responderrol. `reported_at ≤ first_responded_at ≤ resolved_at` wordt bij invoer gevalideerd voor aanwezige tijdstempels. De knop voor eerste reactie zet het tijdstip eenmaal op _nu_ en registreert een activiteit; herhalen verandert niets. De bewerkflow kan tijdstempels ook handmatig wijzigen.

Voor historische registratie kan de gebruiker `is_historical` kiezen, status, reactie- en oplostijd, oplossingssamenvatting, oorzaak, interne notitie en voltooide actieve checklisttemplates invoeren. Een niet-open status vereist een oplostijd en aangevinkt oplossingsitem; `completed` vereist alle verplichte items. De gekozen status moet gelijk zijn aan de uit de checklist afgeleide status. De meld-, reactie- en oplossingsactiviteiten krijgen de historische gebeurtenistijd; de registratieactiviteit wordt op invoertijd toegevoegd. SLA en rapportage rekenen met `reported_at`, niet met de aanmaakdatum van het databaserecord.

De oorzaak wordt geclassificeerd met de acht waarden in de woordenlijst. `resolution_summary` is optioneel bij historische invoer. Bij de gewone oplossingsactie accepteert de backend een optionele samenvatting tot 2.000 tekens en een optioneel oorzaaktype; de UI vraagt deze in het oplossingsdialoogvenster.

### 4.3 Checklist, oplossing en status

Alleen **actieve** templates worden bij storingsregistratie gekopieerd, inclusief naam, `is_required`, `marks_issue_resolved` en volgorde. De standaardset is _Storing opgelost_ (verplicht en oplossingsmarker), _Toegevoegd aan Storing Log_ (verplicht) en _Postmortem verstuurd_ (optioneel). Gebruikers kunnen templates aanmaken, wijzigen, verplaatsen, deactiveren en pas na deactivering verwijderen. De actieve set moet minstens één verplicht item houden en mag hoogstens één oplossingsmarker bevatten; de volgorde wordt na mutaties aaneengesloten genummerd. Bestaande checklistkopieën veranderen niet mee.

```text
open ── oplossingsitem voltooid ──► handling ── alle verplichte voltooid ──► completed
  ▲                                  │  ▲                                  │
  └── oplossingsitem heropend ────────┘  └── verplicht item heropend ──────┘
```

De status wordt na elke checklistwijziging opnieuw bepaald: **alle verplichte items voltooid** geeft `completed`; anders geeft een voltooid oplossingsitem `handling`; anders `open`. Bij nul verplichte items kan de runtimeberekening door vacuümwaarheid `completed` opleveren; templatebeheer probeert zo'n actieve set te voorkomen. Het tijdstip `resolved_at` wordt gezet zodra het oplossingsitem voltooid is en gewist bij heropenen. `completed_at` wordt gezet zodra `completed` wordt bereikt en gewist als een vereist item heropent. Opnieuw afronden krijgt dan een nieuw tijdstip. Optionele items blokkeren afronding niet.

Een item kan als _niet van toepassing_ worden gemarkeerd; het geldt dan als `is_completed = true`, heeft een voltooiingstijd en telt mee voor afronding, ook wanneer het verplicht is. Een heropend item verliest die markering en zijn voltooiingsgegevens. De actie registreert actor en tijdlijnactiviteit. Herhaling van exact dezelfde toestand is idempotent en voegt geen activiteit toe. Een checklistitem mag alleen via de route van zijn eigen storing worden bijgewerkt.

Bij het voltooien van het oplossingsitem kan `postmortem_required` worden gekozen. Wanneer die waarde wordt opgegeven, worden checklistitems waarvan de naam _postmortem_ bevat automatisch voltooid als _niet van toepassing_ wanneer geen postmortem nodig is; bij wel nodig worden ze heropend. Dit werkt op de **naam van het snapshotitem**, niet op een apart type. De oplosactie slaat samenvatting en oorzaak op; bij heropenen of niet-van-toepassing van het oplossingsitem worden die twee velden gewist. Een reeds vastgelegde postmortem wordt hierdoor niet automatisch verwijderd.

### 4.4 Tijdlijn, bijlagen en postmortem

Een aangemelde gebruiker kan op een storing een interne opmerking (`comment`) of besluit (`decision`) toevoegen met verplichte tekst van maximaal 5.000 tekens. Vermeldingen verwijzen naar bestaande teamleden; naam en ID worden in de activiteit opgeslagen. De code verstuurt bij een vermelding geen melding. Eén optionele bijlage per activiteit mag maximaal 10 MB zijn en één van `pdf`, `txt`, `log`, `csv`, `json`, `zip`, `png`, `jpg`, `jpeg`, `webp`. Alleen een gebruiker met inzage in de bijbehorende storing kan via de downloadroute het bestand ophalen. Activiteiten worden met aanmaaktijd getoond; het model kent geen gewone updatekolom of bewerkroute.

Als `postmortem_required = true`, kan een gebruiker een postmortem opslaan met verplichte oorzaak en impact (beide maximaal 5.000 tekens). Er is maximaal één postmortem per storing. De huidige inzending vervangt de lijst actiepunten: maximaal 20 items, elk met titel (maximaal 255 tekens), optionele eigenaar uit teamleden, optionele vervaldatum en voltooiingsvlag. Nieuwe voltooiing zet `completed_at`, heropenen wist dit tijdstip; weggelaten bestaande items worden verwijderd. Een opslagactie schrijft `postmortem_updated` naar de tijdlijn. Er is geen automatische verzending, deadlinebewaking of verplichting dat alle postmortemacties klaar zijn voordat de storing `completed` wordt.

### 4.5 SLA-niveaus, werkuren en berekening

Een project gebruikt een gekozen `SlaLevel` met per prioriteit een reactie- en oplosdoel, of — als `sla_level_id` leeg is — de eigen `sla_first_response_minutes` en `sla_resolution_minutes`. Bij een niveau krijgen de eigen minutenvelden geen effect. Een niveau heeft een unieke naam en de beheerformulieren eisen vier verschillende prioriteiten met doelen van 1–525.600 minuten. Voor eigen projectdoelen geldt dezelfde numerieke grens als een waarde is ingevuld; beide mogen leeg blijven. Een niveau met gekoppelde projecten kan niet worden verwijderd. De standaardseed bevat Basic, Standard, Premium en Enterprise; deze waarden zijn initiële configuratie, geen onveranderlijk contract.

Werkuren zijn globaal: minimaal één unieke ISO-weekdag (`1` maandag t/m `7` zondag), één begin- en eindtijd in `HH:mm`, met einde na begin. Standaard zijn maandag t/m vrijdag, 09:00–17:00. Een melding buiten werkuren begint voor de SLA op het eerstvolgende werkmoment. De deadline is `reported_at` plus het doel **in werkminuten**, waarbij niet-werkdagen en uren buiten het venster niet meetellen. Voor reactie gebruikt de berekening `first_responded_at`, voor oplossing `resolved_at`; zolang een tijdstip ontbreekt wordt _nu_ gebruikt. Een doel op de grens telt als gehaald; te laat betekent resterende minuten `< 0`.

Per mijlpaal geldt: ontbrekend doel → `unavailable`; bereikt binnen doel → `met`; bereikt na doel → `breached`; nog niet bereikt met resterende tijd `< 0` → `overdue`; nog niet bereikt met resterende tijd `≤ max(15 minuten, ceil(25% van doel))` → `at_risk`; anders `on_track`. `needs_attention` is waar als reactie of oplossing `at_risk` of `overdue` is. Er is geen aparte pauze- of feestdagenkalender. Een wijziging van SLA-niveau of werkuren beïnvloedt berekeningen van bestaande storingen: er is geen SLA-snapshot per storing.

### 4.6 Dashboard, zoeken, export en rapportage

Het dashboard toont open en in-afhandeling storingen, standaard op prioriteit en daarna meldtijd, 15 per pagina. Filters: project, prioriteit, `open`/`handling`, behandelaar. Tegels tonen het totale aantal `open`, `handling`, in de huidige kalendermaand afgeronde storingen en lopende storingen met SLA-aandacht. De tegels worden onafhankelijk van de lijstfilters berekend.

Het volledige storingsoverzicht toont `open` vóór `handling` vóór `completed`, 15 per pagina. Het ondersteunt zoeken in titel, omschrijving en projectnaam; filters op project, gekoppelde klant, prioriteit, status, behandelaar en meldperiode; sortering op prioriteit, meldtijd of laatste activiteit. De export neemt **alle** storingen die aan de filters voldoen en maakt een CSV met UTF-8 BOM, onder andere meldtijd, klant, contact, betrokken teamleden, oplossing, postmortemstatus, kennisbankvlag, trendvlag en oorzaak. De CSV gebruikt de actuele klantnaam, met de oude projectklantnaam als fallback wanneer de relatie ontbreekt. De CSV-postmortemstatus wordt bepaald uit het **eerste item met ‘postmortem’ in de naam**: geen item of niet van toepassing → ‘Niet van toepassing’, voltooid → ‘Ja’, anders ‘Nee’. Dit is geen bewijs van daadwerkelijke verzending.

Rapportages selecteren storingen op `reported_at` en optioneel project. Standaard zijn het de afgelopen 30 kalenderdagen inclusief vandaag; `week` omvat 7 dagen, `month` 30 dagen, `year` begint twaalf maanden terug tot nu en `custom` neemt een opgegeven inclusieve datumreeks. Een aangepaste reeks vereist begin en eind; bestaande links met `until` blijven geaccepteerd naast `to`. De vergelijking gebruikt de direct voorafgaande periode met hetzelfde aantal kalenderdagen. Bij een vorige waarde van nul of ontbrekende waarden wordt procentuele verandering `null`; anders `round((huidig - vorig) / vorig × 100)`.

Het verslag bevat gemelde, nog actieve en afgeronde aantallen **binnen het op meldtijd geselecteerde cohort**; gemiddelde tijd van melding tot eerste reactie en tot oplossing in kalender-minuten, afgerond op hele minuten; SLA-score `round(gehaald / (gehaald + geschonden) × 100)` voor reeds bereikte mijlpalen; een trend in dagen, weken of maanden; verdeling per prioriteit en oorzaak; en projectprestaties. Niet-geclassificeerde oorzaken tellen niet in de oorzaakpercentages. Een lege noemer levert `null` voor gemiddelden/SLA-percentage en nul voor aantallen. Deze rapportgemiddelden gebruiken kalender-minuten, terwijl de SLA-mijlpalen werkminuten gebruiken.

### 4.7 Accounts en instellingen

Login, zelfregistratie, wachtwoordherstel, e-mailverificatie, tweefactorauthenticatie en passkeys zijn beschikbaar. De domeinroutes vermelden `auth` en `verified`; profiel bekijken/bijwerken vraagt alleen `auth`. Omdat `User` het Laravel-contract `MustVerifyEmail` niet implementeert, houdt de `verified`-middleware een niet-geverifieerd account momenteel niet tegen. Een gebruiker kan eigen profiel, wachtwoord, beveiligingsinstellingen, uiterlijk en accountverwijdering bedienen. Accounts zijn niet gekoppeld aan klanten of teamleden. Domeinpolicies laten elke aangemelde gebruiker de hoofdobjecten beheren, met de genoemde deletebeperkingen. Een account met aangemaakte storingen kan niet worden verwijderd door de restrictie op `issues.created_by`. De UI ontvangt hiervoor een validatiefout en de gebruiker blijft ingelogd. Er is geen productflow om eigenaarschap over te dragen.

## 5. Processen, integraties en UI

| Mechanisme              | Productbetekenis                                                                                                        | Fout-/herstelgedrag                                                                                                          |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Directe gebruikersactie | Melden, wijzigen, checklist, postmortem, beheer en export lopen via webverzoeken.                                       | Validatie geeft veldfouten; storingscreatie, checklistmutaties en postmortemopslag zijn als samenhangende wijziging bedoeld. |
| Achtergrondwerk         | Er is geen domeinscheduler, domeinjob of webhook voor escalatie, synchronisatie of automatische herinneringen gevonden. | Er is daardoor geen automatische retry of handmatige herstartflow voor zulke producttaken.                                   |
| Authenticatiemail       | Laravel Fortify gebruikt mail voor verificatie en wachtwoordherstel.                                                    | Afhankelijk van mailconfiguratie; geen eigen storingnotificatie.                                                             |
| Lokale opslag           | Tijdlijnbijlagen worden lokaal bewaard en via de storingsroute gedownload.                                              | Een ontbrekend bestand geeft 404; geen gebruikersflow voor opnieuw uploaden op dezelfde activiteit.                          |
| CSV                     | Handmatige export voor onder meer Google Sheets.                                                                        | Export is een download, zonder externe Sheets-koppeling of import.                                                           |

De hoofdnavigatie biedt Dashboard, Projecten, Klanten, Alle storingen, Rapportages, Responders, Team en Instellingen. De producttaal is grotendeels Nederlands. Nieuwe storingen hebben een apart meldformulier; verdere behandeling gebeurt op de detailpagina. Status en SLA verschijnen als zichtbare badges. Formulierfouten verschijnen bij velden en diverse succesvolle acties als toast. Er is geen aantoonbare PWA- of offlineflow.

## Besluiten afgeleid uit de huidige implementatie

Dit zijn vastgestelde **huidige gedragskeuzes** uit code en tests, geen nieuwe besluiten namens de product owner.

| #   | Onderwerp             | Vastgesteld gedrag                                                                                                                             |
| --- | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| B1  | Registratie           | Alleen een actief project accepteert een nieuwe storing.                                                                                       |
| B2  | Identiteit            | `User` en `TeamMember` zijn gescheiden; toewijzing verleent geen login of extra recht.                                                         |
| B3  | Responderbezetting    | Nieuwe en volledig bewerkte projecten vragen drie verschillende teamleden.                                                                     |
| B4  | Statusbron            | De checklist bepaalt `open`, `handling` en `completed`; heropenen kan de status terugzetten.                                                   |
| B5  | Checklistgeschiedenis | Nieuwe storingen nemen een snapshot van actieve templates.                                                                                     |
| B6  | Oplossing             | Maximaal één actief oplossings-template en minimaal één actief verplicht template.                                                             |
| B7  | N.v.t.                | Een niet-toepasselijk item telt mee als voltooid.                                                                                              |
| B8  | SLA                   | De actuele project-SLA en globale werkuren worden voor beide mijlpalen gebruikt.                                                               |
| B9  | Historie              | Achteraf registreren vereist een status die overeenkomt met de checklist.                                                                      |
| B10 | Autorisatie           | De huidige domeinpolicies geven alle aangemelde accounts gelijke functionele toegang.                                                          |
| B11 | Verwijderen           | Een klant met projecten en een SLA-niveau met projecten mogen niet worden verwijderd; actieve checklisttemplates moeten eerst inactief worden. |
| B12 | Rapportage            | Rapportcohorten zijn gebaseerd op meldtijd, niet op afrondtijd.                                                                                |

## Geconstateerde inconsistenties

| #   | Bevinding en plaats                                                                                                                                                                                                                           | Waarom dit wringt / productbeslissing                                                                                                                                                                                                              |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| I1  | **Opgelost:** detail, meldopties, overzichten en CSV gebruiken `Project::customerDisplayName()`.                                                                                                                                              | De actuele `Customer.name` is leidend; `customer_name` blijft fallback voor oude gegevens zonder klantrelatie. Er wordt geen historisch klantnaamsnapshot toegevoegd.                                                                              |
| I2  | **Opgelost:** request en Action beschrijven de bewerkbare tijdstempels; detailprops scheiden machinewaarden en weergavelabels.                                                                                                                | Een regressietest bewijst behoud bij bewerken. Historische tijdlijnmomenten worden expliciet opgeslagen.                                                                                                                                           |
| I3  | Eén berekening in `SyncIssueStatus::determineStatus` wordt gebruikt voor creatie, historische validatie en checklistupdates.                                                                                                                  | Zonder verplichte items blijft voorlopig het bestaande gedrag behouden: registratie leidt tot open/handling; checklistupdates kunnen completed opleveren. De expliciete compatibiliteitsoptie blijft totdat de open productkeuze wordt beantwoord. |
| I4  | De oplosactie selecteert postmortemitems met een naam die ‘postmortem’ bevat; CSV kijkt ook naar een naam. De standaardtemplate heet ‘Postmortem verstuurd’, terwijl de applicatie alleen een postmortem opslaat en geen verzending uitvoert. | Hernoemen, vertalen of meerdere gelijknamige items verandert gedrag of export. Bepaal of dit een expliciet type en een echte verzendstatus moet worden.                                                                                            |
| I5  | `project_team_member` bestaat in het schema, maar de applicatie gebruikt drie vaste `*_responder_id`-velden.                                                                                                                                  | De pivot suggereert onbeperkte teamkoppelingen zonder productflow. Bepaal of deze relatie nog een productbetekenis heeft.                                                                                                                          |
| I6  | De standaard `SlaLevelSeeder` noemt 1.440 minuten een ‘werkdag’; de SLA-rekenaar telt werkminuten binnen 09:00–17:00.                                                                                                                         | Een seeded ‘1 werkdag’ duurt bij standaarduren effectief drie werkdagen. Bepaal of standaarddoelen kalender- of werkminuten horen te vertegenwoordigen.                                                                                            |
| I7  | De eerdere korte `docs/Product.md`, README en TODO beschreven enkele regels korter of anders dan de actuele code; README noemt bijvoorbeeld oude seedprojecten en een andere testgebruiker.                                                   | Bepaal welke overige documentatie opgeschoond of doorverwezen moet worden. Deze uitgebreide versie volgt de actuele implementatie.                                                                                                                 |
| I8  | De postmortemroute controleert `postmortem_required`, maar de vlag kan bij heropenen van het oplossingsitem blijven staan; `IssuePostmortem` wordt dan niet verwijderd.                                                                       | Een storing kan technisch open zijn terwijl de postmortem zichtbaar/bewerkbaar blijft. Bepaal de gewenste levenscyclus.                                                                                                                            |

| I9 | Domeinroutes vermelden `verified`, maar `User` implementeert `MustVerifyEmail` niet; Laravel laat daardoor een niet-geverifieerd account door. | Beslis of verificatie werkelijk verplicht is en voeg zo ja contract plus negatieve toegangsproef toe. |

## Open vragen

1. Wie mag zich registreren en mogen alle aangemelde accounts daadwerkelijk alle klanten, projecten en storingen beheren?
2. Moet een `TeamMember` aan een `User` kunnen worden gekoppeld, en moeten vermeldingen of toewijzingen dan meldingen opleveren?
3. Wat betekenen `p1`–`p4` inhoudelijk voor impact en urgentie; wie mag een prioriteit wijzigen?
4. Moet een project altijd een klant hebben, en is de actuele `Customer.name` of een historische klantnaam leidend op detailpagina en in CSV?
5. Moet een project na verwijdering van een responder opnieuw verplicht drie verschillende responders krijgen, en is escalatie op rangorde gewenst?
6. Mag de toegewezen behandelaar buiten de drie projectresponders vallen?
7. Mogen meld-, reactie- en oplostijd achteraf worden gewijzigd; welke correcties moeten zichtbaar blijven in de tijdlijn en SLA-historie?
8. Mag een oplossingsitem _niet van toepassing_ zijn of mag een optioneel oplossingsitem samen met andere verplichte items toch tot `completed` leiden?
9. Wat moet de status zijn voor een bestaande storing zonder verplichte checklistitems?
10. Moet `postmortem_required` bij heropenen van de oplossing worden gereset, en moet een bestaande postmortem dan bewaard, verborgen of verwijderd worden?
11. Is ‘Postmortem verstuurd’ een handmatige bevestiging van daadwerkelijke verzending, of moet het product verzending en ontvanger registreren?
12. Moeten postmortemacties afronding blokkeren en moeten vervaldata, eigenaren en statuswijzigingen actief bewaakt of gemeld worden?
13. Moeten SLA-afspraken en werkuren op het moment van melding als historisch snapshot worden vastgelegd, of mogen latere wijzigingen oude SLA-resultaten herschrijven?
14. Zijn feestdagen, tijdzones of projectspecifieke werkuren nodig bij SLA-berekening?
15. Zijn de seeded SLA-minuten bedoeld als werkminuten, en welke echte contractdoelen horen bij Basic, Standard, Premium en Enterprise?
16. Is de oude `project_team_member`-relatie nog bedoeld voor een productfunctie?
17. Moet een gebruiker met aangemaakte storingen zijn account kunnen verwijderen, en zo ja wie neemt `created_by` over?
18. Is een kennisbank- of Storing Log-integratie bedoeld, of blijven dit handmatige checklist- en ja/nee-registraties?
19. Moet e-mailverificatie toegang tot alle domeinroutes blokkeren, of is verificatie alleen een beschikbare accountflow?

## Onderzoeksbasis en grenzen

Deze specificatie is gebaseerd op de huidige README en docs, routes, migrations, modellen, enums, policies, requests, actions, controllers, seeders, relevante Vue-formulieren/navigatie en featuretests. Er is geen losse API-resource-laag aangetroffen; de controllers leveren Inertia-data. Er zijn geen eigen domeinmails, notificaties, webhooks, imports of geplande domeintaken aangetroffen. De bestaande korte `docs/Product.md` is voor deze opdracht uitgebreid en krijgt de naam `docs/PRODUCT.md`.

## Bevestigde onderhoudsbesluiten — 30 september 2026

- De actuele `Customer.name` is overal leidend, met `Project.customer_name` als fallback zonder klantrelatie.
- Zelfregistratie, gelijke toegang voor ingelogde accounts en optionele e-mailverificatie blijven behouden.
- Actuele SLA-doelen en globale werkuren blijven ook voor oude storingen gelden. Er wordt geen SLA-snapshot of nieuw tijdzonebeleid ingevoerd.
- Postmortemherkenning op naam, de huidige postmortemlevenscyclus en de oude `project_team_member`-pivot blijven voorlopig behouden. Er wordt geen automatische verzending toegevoegd.
- Het gedrag van lege verplichte checklists vraagt nog een afzonderlijk besluit; de technische consolidatie verandert dit randgeval niet stilzwijgend.
