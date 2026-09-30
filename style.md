# TracePilot stijlgids

Deze richtlijn beschrijft de bestaande visuele taal van de dashboard- en
overzichtspagina's. Gebruik hem voor nieuwe of aangepaste UI, zodat TracePilot
een rustige, betrouwbare interface voor incidentbeheer blijft.

## Ontwerpprincipe

TracePilot is een operationele werkplek: informatie is snel scanbaar,
actiegericht en betrouwbaar. Houd pagina's licht, ruim en rustig. Gebruik kleur
voor betekenis en urgentie, nooit alleen als versiering. De gebruiker moet een
kritieke storing, de status en de eerstvolgende actie direct kunnen vinden.

## Fundamenten

### Typografie

- Gebruik `Inter` via de bestaande `font-sans`-token.
- Primaire kop- en inhoudskleur is navy `#101d3f`; gebruik geen zuiver zwart
  voor nieuwe inhoud.
- Gebruik `text-muted-foreground` (basis `#6e7b93`) voor toelichtingen,
  metadata en tabelinformatie van secundair belang.
- Pagina-titels zijn `text-2xl font-semibold tracking-tight`. Het dashboard
  gebruikt voor de persoonlijke begroeting een schaalbare, iets ruimere titel:
  `text-[clamp(1.875rem,3vw,2.5rem)] font-semibold tracking-[-0.035em]`.
- Sectie- en kaarttitels zijn doorgaans `text-lg` of `text-xl`, `font-semibold`
  en navy. Gebruik `text-sm font-medium` voor labels en tabelkoppen.
- Gebruik `tabular-nums` voor compacte, veranderlijke tellerwaarden, zoals
  badges in de navigatie.

### Kleur en betekenis

Gebruik bij voorkeur de bestaande CSS-variabelen en semantische Tailwind-kleuren.

| Rol          | Token / kleur                         | Gebruik                                          |
| ------------ | ------------------------------------- | ------------------------------------------------ |
| Primair      | `--tracepilot-blue` / `#1267f4`       | primaire actie, focus en geselecteerde navigatie |
| Inhoud       | `--tracepilot-navy` / `#101d3f`       | koppen en belangrijke tekst                      |
| Pagina       | `--tracepilot-background` / `#f6f8fc` | rustige app-achtergrond                          |
| Oppervlak    | `--tracepilot-surface` / `#ffffff`    | kaarten, sidebar en pop-overs                    |
| Rand         | `--tracepilot-border` / `#dfe5ee`     | subtiele scheiding van oppervlakken              |
| Kritiek      | `--tracepilot-danger` / `#ef3340`     | P1, open storing, verlopen SLA                   |
| Waarschuwing | `--tracepilot-warning` / `#f58a07`    | P2, afhandeling, SLA-risico                      |
| Succes       | `--tracepilot-success` / `#10945a`    | afgerond, op schema                              |

- Combineer een statuskleur altijd met tekst, een icoon of een statusstip;
  kleur is nooit de enige drager van betekenis.
- Gebruik zachte tinten (`*-50`) als badge- of meldingachtergrond en een
  duidelijker tint voor rand, tekst of icoon.
- P3 en P4 blijven bewust neutraal met slate-tinten. Reserveer rood en oranje
  voor werkelijke urgentie.
- Ondersteun de bestaande donkere modus uitsluitend via de semantische tokens
  (`bg-background`, `text-foreground`, `bg-card`, enz.). Hardgecodeerde lichte
  kleuren zijn alleen passend waar een bestaand dashboardpatroon dat expliciet
  vereist.

### Vorm, ruimte en diepte

- Basale afronding is `0.875rem` (`rounded-xl`) voor kaarten, meldingen en
  grotere oppervlakken. Invoer en kleine bedieningselementen gebruiken
  `rounded-md` of `rounded-lg`.
- Kaarten hebben een dunne rand en een zeer zachte navy-schaduw. Voeg geen
  zwaardere schaduwen of gradients toe.
- Gebruik een verticale ritmische afstand van `gap-6` tussen hoofdblokken en
  `gap-5` in dashboards met kaarten. Kaartinhoud krijgt meestal `p-6`; compacte
  filterkaarten `p-4 sm:p-5`.
- Houd ruim wit rondom de hoofdinhoud: dashboards gebruiken een gecentreerde
  container met `w-full`, `flex-1`, `px-5 sm:px-8` en ondermarge. Gebruik
  `max-w-[1440px]` voor het dashboard en `max-w-6xl` voor dichte
  beheeroverzichten.

## Pagina-opbouw

1. Begin met een duidelijke titel en een korte, beschrijvende subtitel.
2. Plaats contextacties rechts van de paginakop; op smalle schermen stapelt de
   kop vanzelf verticaal.
3. Toon eerst de informatie die directe aandacht vraagt: KPI's, actieve issues
   of filters. Detailanalyses volgen daarna.
4. Groepeer samenhangende inhoud in kaarten. Gebruik een kaartheader met
   onderrand wanneer filters of acties van de tabelinhoud gescheiden moeten
   zijn.
5. Beperk brede tabellen met `overflow-x-auto` en een realistische `min-w-*`;
   knijp kolommen niet onleesbaar samen.

## Herbruikbare patronen

### KPI-kaarten

- Gebruik een raster met `gap-5` en op desktop drie kolommen.
- Een KPI-kaart heeft een gekleurde linkerborder (`border-l-4`), een rond icoon
  op een zachte kleurachtergrond, een kort label, een groot getal en één regel
  duiding.
- Houd de hoogte consistent (`min-h-36`) en de inhoud links uitgelijnd.
- Gebruik rood voor actieve incidenten, oranje voor afhandeling en groen voor
  afgeronde of gezonde waarden.

### Tabellen en lijsten

- Gebruik `text-sm`, een licht getinte kop (`bg-[#fcfdff]`) en rijen met
  `divide-y`.
- Hanteer `px-6 py-4` voor cellen. Koppen zijn `font-medium` en gedempt; de
  primaire titelkolom is `font-medium` en navy.
- Laat rijen subtiel reageren met `hover:bg-[#fafcff]`.
- Zet datums, relatieve tijden, SLA's en voortgang op één regel waar dat de
  scanbaarheid verhoogt (`whitespace-nowrap`).
- Geef een tijd in een operationeel overzicht bij voorkeur relatief weer
  (bijvoorbeeld `32 min open`); maak de exacte datum en tijd beschikbaar via
  een tooltip of in de detailweergave.
- Plaats rijacties aan de rechterkant als een compacte outline-knop. Houd de
  actietekst concreet, bijvoorbeeld `Bekijken`.

### Status, prioriteit en SLA

- Gebruik de bestaande `IssuePriorityBadge` en `IssueStatusBadge` in plaats
  van nieuwe, afwijkende badges.
- Prioriteiten: P1 rood, P2 oranje, P3 slate, P4 licht slate. Badges zijn
  compact, afgerond en bevatten altijd de tekstuele prioriteit.
- Toon een status met een stip plus label: rood voor `Open`, oranje voor
  `Afhandeling`, groen voor `Afgerond`.
- SLA's gebruiken de gedeelde `IssueSlaBadge`: een compacte, tweeregelige pill
  met de belangrijkste mijlpaal en resterende of overschreden tijd. Gebruik hem
  in elk incidentoverzicht en in de detailheader. De volledige SLA-bewaking op
  de detailpagina geeft daarnaast deadlines en beide mijlpalen weer. Gebruik
  zachte semantische achtergronden:
  rood voor overschreden, oranje voor risico, groen voor op schema en slate
  wanneer de SLA niet beschikbaar is.
- Een voortgangsindicator is laag (`h-1.5`), volledig afgerond en gebruikt de
  statuskleur; geef daarnaast altijd een numerieke tekst zoals `2 van 4`.
- SLA-percentages in rapportages tonen altijd ook de onderliggende aantallen,
  bijvoorbeeld `18 van 20 binnen SLA`.
- Grafieken tonen waarden ook zonder hover: plaats getallen bij datapunten en
  bied daarnaast een compacte tekstuele samenvatting. Een dagelijkse tijdreeks
  toont elke kalenderdag als compacte dagwaarde op de as; toon daarnaast
  uitsluitend dagen met incidenten in de samenvatting. Gebruik bij langere
  week- of maandreeksen een leesbaar, volledig periode-label.
- Projectnamen in rapportages linken naar het storingenoverzicht, gefilterd op
  project en de gekozen rapportageperiode.
- Een rapportage toont direct onder de periodekeuze één korte operationele
  conclusie, bijvoorbeeld het aantal incidenten dat nog opvolging vraagt.
- Toon SLA-percentages in projecttabellen als semantische badges; `—` betekent
  dat er geen bruikbare SLA-meting is, niet dat de score nul is.

### Filters, formulieren en knoppen

- Groepeer filters in een aparte kaart of in de header van de relevante kaart.
  Laat filters bij wijziging of via een duidelijke knop toepassen.
- Invoer en select-elementen zijn 40–44 px hoog (`h-10` of `h-11`), hebben een
  lichte rand, rustige achtergrond en een compacte tekstgrootte.
- Focus is altijd herkenbaar via de bestaande blauwe rand en ring; verwijder
  die niet.
- Gebruik de standaard primaire knop voor de hoofdactie, een outline-knop voor
  secundaire acties en een ghost-knop alleen voor lage-prioriteitsacties.
- Gebruik iconen uit Lucide naast tekst waar ze de handeling verduidelijken;
  vervang een tekstlabel niet door alleen een icoon als dat de betekenis
  onduidelijk maakt.
- Laat meerdere dashboardfilters eerst samen toepassen via één duidelijke knop;
  voorkom een paginavernieuwing bij elke afzonderlijke selectie.
- De snelle storingregistratie begint met alleen project, titel, prioriteit en
  starttijd. Omschrijving is optioneel; historische afhandeling blijft een
  afzonderlijke, expliciete vervolgstap.
- Koppel validatiefouten aan hun veld met `aria-invalid` en `aria-describedby`;
  toon verplichte velden met een tekstueel teken, niet alleen met kleur.

### Meldingen en lege staten

- Aandachtsmeldingen zijn zachte, afgeronde panelen met semantische rand,
  icoon, een korte vetgedrukte conclusie en één regel toelichting.
- Lege staten zijn gecentreerd, compact en behulpzaam: een gedempt icoon,
  duidelijke titel en een concrete uitleg of vervolgstap. Vermijd illustraties
  die de operationele rust verstoren.
- Gebruik voor een destructieve actie de gedeelde `ConfirmDeleteDialog` in
  plaats van browserbevestigingen. Benoem het doelobject, het gevolg en bied
  altijd een veilige annuleeractie.

## Navigatie en responsiviteit

- De sidebar is wit, heeft alleen een subtiele rechterrand en gebruikt het
  TracePilot-logo bovenaan. Hoofdnavigatie gebruikt Lucide-iconen en een
  duidelijke actieve staat via de bestaande sidebar-tokens.
- Scheid primaire werkgebieden en incidentbeheer visueel met een dunne divider
  in de navigatie.
- Laat rasterindelingen op kleinere schermen naar één kolom terugvallen.
  Paginakoppen, acties en filtervelden mogen wrappen; behoud daarbij een
  minimale tikgrootte van circa 40 px.
- Het dashboard bevat altijd de directe primaire actie `Storing melden` en
  ondersteunt project-, status-, prioriteits- en verantwoordelijke-filters.
- Houd tabelgegevens beschikbaar op mobiel via horizontaal scrollen in plaats
  van belangrijke informatie te verbergen.
- Kondig horizontaal scrollende tabellen op mobiel kort aan. Maak sortering via
  een zichtbare, focusbare kolomkop beschikbaar en behoud deze in de URL.

### Incidentdetail

- Plaats direct onder de incidentheader één semantisch actiepanel `Volgende
stap`. Dit benoemt precies de eerstvolgende vereiste handeling, zoals eerste
  reactie, een openstaande verplichte checkliststap of postmortem.
- Toon naast status, prioriteit en SLA altijd de voortgang van verplichte
  checkliststappen.

## Toegankelijkheid en kwaliteit

- Gebruik semantische koppen in logische volgorde en koppel labels aan invoer;
  alleen visueel verborgen labels zijn toegestaan wanneer de bediening al een
  duidelijke toegankelijke naam heeft.
- Geef icoonknoppen een toegankelijk label via zichtbare context of `aria-*`.
- Controleer contrast voor tekst, badges en focusstaten in zowel lichte als
  donkere modus.
- Gebruik Nederlands voor zichtbare interfacecopy: kort, actief en zonder
  technisch jargon waar dat niet nodig is.
- Hergebruik eerst de bestaande UI-primitieven en issuecomponenten. Nieuwe
  patronen moeten aansluiten op de tokens, afronding, ruimte en semantiek uit
  deze gids.
