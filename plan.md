# Plan: ValiGOAL als Laravel TALL App

## Samenvatting
Bouw ValiGOAL opnieuw als productieklare Laravel app in de huidige projectroot. Gebruik Laravel 12.x met de officiële Livewire starter kit, Tailwind CSS v4, Flux UI, Filament 5, Laravel Sail lokaal en MySQL op Forge/VPS live. Het statische prototype wordt vervangen; logo, naam, slogan en Valicare-kleuren blijven, maar het dashboard wordt opnieuw ontworpen.

De app gebruikt e-mail/wachtwoord-auth met verplichte e-mailverificatie en alleen `@valicare.nl`-adressen. Jij wordt de enige `super_admin`; alle andere gebruikers zijn `deelnemer`.

## Kernfunctionaliteit
- Eén centrale Valicare-poule en één algemeen klassement.
- Registratie blijft open; late deelnemers kunnen nog meedoen, maar krijgen geen punten voor gesloten wedstrijden/bonusvragen.
- Deelnemers vullen volledige naam in bij registratie; klassement toont volledige naam.
- Voorspellingen per wedstrijd:
    - wijzigbaar tot lokale aftraptijd;
    - geheim tot deadline;
    - zichtbaar voor andere deelnemers na lock.
- Bonusvoorspellingen:
    - toernooiwinnaar: 15 punten;
    - finalist: 10 punten;
    - topscorer: 10 punten;
    - sluiten bij toernooistart.
- Puntentelling wedstrijden:
    - exacte uitslag: 5 punten;
    - juiste uitkomst: 3 punten;
    - juist doelsaldo: 1 bonuspunt.
- Tie-breakers: totaalpunten, meeste exacte uitslagen, meeste juiste uitkomsten, daarna alfabetisch.
- E-mail reminders voor ontbrekende voorspellingen: standaard 24 uur en 2 uur vóór deadline.
- Geen exports in v1.

## Data, API En Beheer
- football-data.org free plan wordt de leidende bron voor wedstrijden en eindstanden.
- 10 calls/minute is voldoende voor deze app; de sync wordt normaal en betrouwbaar ingericht, niet extreem defensief.
- Omdat scores/schedules vertraagd kunnen zijn:
    - deadlines worden lokaal vastgelegd op geïmporteerde aftraptijd;
    - punten worden pas toegekend wanneer de API een wedstrijd als definitief/eindstand teruggeeft;
    - de app wacht op de API bij vertraagde eindstanden;
    - geen handmatige score-override in v1.
- Gebruik competitiecode `WC` en `FOOTBALL_DATA_TOKEN` in `.env`.
- Syncstrategie:
    - scheduler/jobs voor automatische updates;
    - ruim binnen 10 calls/minute blijven;
    - dagelijks buiten speeldagen;
    - vaker rond bekende aftraptijden en na wedstrijden;
    - synclogs zichtbaar in Filament.
- Historie is compact maar rijk:
    - vorige winnaars;
    - topscorers aller tijden;
    - memorabele WK-wedstrijden;
    - waar API-data ontbreekt, lokaal gecachte/seeded data gebruiken.
- Filament `/admin` voor `super_admin`:
    - gebruikers bekijken/blokkeren;
    - toernooi, teams, wedstrijden, synclogs en historische content bekijken;
    - handmatige “sync nu” actie;
    - geen aparte adminrol naast `super_admin`.

## Technische Implementatie
- Scaffold Laravel met official Livewire starter kit, auth, e-mailverificatie, Tailwind v4 en Flux UI.
- Voeg Filament 5 panel toe voor beheer.
- Gebruik Sail/Docker lokaal met MySQL, queue worker en Mailpit.
- Live deployment via Forge/VPS met MySQL, queue worker, scheduler, SSL, backups, Vite build en correcte `.env`.
- Belangrijkste modellen:
    - `User`
    - `Tournament`
    - `Team`
    - `Match`
    - `Prediction`
    - `BonusPrediction`
    - `HistoricalFact`
    - `ApiSyncLog`
    - `PredictionAuditLog`
- Belangrijkste services:
    - football-data.org client token(4727ff39a0a8435f95522eedc169ea78);
    - sync jobs;
    - score calculation service;
    - prediction lock service;
    - reminder notification jobs.

## Testplan
- Auth tests: alleen `@valicare.nl`, verificatie verplicht, volledige naam verplicht.
- Prediction tests: opslaan, wijzigen vóór deadline, blokkeren na deadline, geheim tot deadline.
- Scoring tests: exact, juiste uitkomst, doelsaldo, gelijkspel, bonuspunten en tie-breakers.
- API tests met gefakete football-data responses: import, eindstanddetectie, vertraagde data, sync logs.
- Scheduler/job tests: syncmomenten, reminders, geen dubbele mails.
- Policy tests: deelnemer versus `super_admin`.
- Livewire/browser tests: mobiel en desktop voor dashboard, voorspellen, klassement en historie.
- Filament smoke tests voor admin login, resources en synclogweergave.

## Aannames
- De huidige prototypebestanden mogen worden vervangen door de Laravel app.
- football-data.org free token blijft beschikbaar met 10 calls/minute.
- Mailprovider wordt later gekozen; Laravel mailconfig blijft provider-neutraal.
- Bronnen: [Laravel starter kits](https://laravel.com/docs/12.x/starter-kits), [Livewire 4](https://livewire.laravel.com/docs/4.x/installation), [Filament](https://filamentphp.com/docs/5.x), [football-data.org quickstart](https://www.football-data.org/documentation/quickstart).
