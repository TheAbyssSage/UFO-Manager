# 🛸 UFO Meldpunt — Build Plan

> **Project:** Laravel Livewire Starter Kit (Laravel 13, Livewire 4, Flux UI, Filament 5)
> **Doel:** Een UFO-meldplatform waar gebruikers waarnemingen kunnen rapporteren, beheren en admins kunnen opvolgen.

---

## 📋 Fase 0 — Projectanalyse (Huidige status)

### Wat werkt al
| Feature | Status |
|---|---|
| Authenticatie (registratie, login, wachtwoord reset) | ✅ Fortify + Passkeys |
| Gebruikersmodel (`User`) | ✅ Basis (name, email, password) |
| Filament Admin Panel | ✅ Configured op `/admin` |
| Flux UI + Livewire | ✅ Werkend |
| Dashboard (ingelogde gebruiker) | ✅ Standaard layout |
| Settings (profiel, appearance, security) | ✅ Werkend |
| SQLite database | ✅ Geconfigureerd |
| Vite + Asset building | ✅ Werkend |
| Tests (auth, settings, basic) | ✅ Aanwezig |

### Wat moet nog gebouwd worden
| Feature | Prioriteit |
|---|---|
| Database model voor meldingen | 🔴 Hoog |
| Melding indienen (formulier) | 🔴 Hoog |
| "Mijn meldingen" overzichtspagina | 🔴 Hoog |
| Admin Filament resource voor meldingen | 🔴 Hoog |
| Rolgebaseerde toegang (Spatie permissions) | 🟡 Medium |
| Foto upload voor meldingen | 🟡 Medium |
| E-mail notificaties | 🟡 Medium |
| Support fee betaling (Mollie) | 🟡 Medium |
| Nederlandse vertalingen | 🟢 Laag |
| Over ons pagina + publieke content | 🟢 Laag |
| API (Sanctum) — bonus | ⚪ Optioneel |

---

## 🗃️ Fase 1 — Datamodel & Dependencies

### Stap 1.1 — Installeer extra packages

```bash
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require mollie/mollie-api-php
composer require laravel/sanctum
```

### Stap 1.2 — Nieuwe migratie: `create_reports_table`

| Kolom | Type | Opmerkingen |
|---|---|---|
| `id` | bigIncrements | |
| `user_id` | foreignId | nullable — gasten kunnen ook melden |
| `title` | string | Korte titel van de melding |
| `description` | text | Uitgebreide beschrijving |
| `category` | string | enum: 'drone', 'lichtbal', 'cirkel', 'driehoek', 'sigaar', 'humanoid', 'ander' |
| `location` | string | Locatie van waarneming |
| `location_lat` | decimal(10,7) | nullable — GPS coördinaat |
| `location_lng` | decimal(10,7) | nullable — GPS coördinaat |
| `observed_at` | datetime | Datum & tijd van waarneming |
| `status` | string | enum: 'pending', 'in_review', 'confirmed', 'debunked', 'spam' |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Stap 1.3 — Nieuwe migratie: `create_report_media_table`

Gebruikt Spatie MediaLibrary voor foto uploads (geen aparte tabel nodig — MediaLibrary beheert dit via `media` tabel).

### Stap 1.4 — Rol/permisies migratie (Spatie)

| Rol | Permissies |
|---|---|
| `admin` | `view_reports`, `edit_reports`, `delete_reports`, `manage_users` |
| `melder` | `create_reports`, `view_own_reports` |
| `gast` | `create_reports` (zonder account) |

### Stap 1.5 — Modellen

**`app/Models/Report.php`**
- `belongsTo(User)` — nullable
- `category` enum cast
- `status` enum cast
- Spatie MediaLibrary: `registerMediaCollections()` voor foto's
- `scopePending()`, `scopeForUser($user)`

**`app/Models/User.php` — uitbreiden**
- `hasMany(Report)`
- Spatie: `HasRoles` trait

---

## 🧑‍💼 Fase 2 — Gebruikersrollen (Spatie Permissions)

### Stap 2.1 — Seeder voor rollen

```php
// database/seeders/RoleSeeder.php
Role::create(['name' => 'admin']);
Role::create(['name' => 'melder']);
```

### Stap 2.2 — Admin account aanmaken

```php
// database/seeders/AdminSeeder.php
$admin = User::factory()->create([
    'name' => 'Admin',
    'email' => 'admin@ufo-meldpunt.be',
]);
$admin->assignRole('admin');
```

### Stap 2.3 — Nieuwe gebruiker krijgt automatisch 'melder' rol

In `App\Providers\FortifyServiceProvider` of via een event listener op `Registered` event.

---

## 📝 Fase 3 — Melding indienen (Frontend)

### Stap 3.1 — Livewire component: `CreateReport`

**`app/Livewire/CreateReport.php`**
- Form met: titel, beschrijving, categorie (dropdown), locatie, datum/tijd, foto upload
- Validatie: titel (required, max:255), beschrijving (required), categorie (required, in:[...]), locatie (required), observed_at (required, date), foto (nullable, image, max:10MB)
- `submit()` methode: maakt Report aan, koppelt aan ingelogde gebruiker (of sessie voor gast)
- Redirect naar bedankpagina

**`resources/views/livewire/create-report.blade.php`**
- Flux UI formulier componenten
- Nederlandse labels

### Stap 3.2 — Route & navigatie

```php
// routes/web.php
Route::get('/meld', App\Livewire\CreateReport::class)->name('reports.create');
Route::get('/meld/bedankt', function () {
    return view('reports.thanks');
})->name('reports.thanks');
```

Navigatie toevoegen in `resources/views/layouts/app.blade.php`:
- Home (`route('home')`)
- Meld een UFO (`route('reports.create')`)
- Mijn meldingen (`route('reports.my')`)
- Over ons (`route('about')`)

### Stap 3.3 — Gastmeldingen (optioneel)

Als de team kiest voor gastmeldingen:
- Sla `user_id` als `null` op
- Gebruik sessie-ID om gastmeldingen te koppelen
- Toon na submit: "Maak een account aan om je meldingen te beheren"

---

## 👤 Fase 4 — Mijn meldingen

### Stap 4.1 — Livewire component: `MyReports`

**`app/Livewire/MyReports.php`**
- Toont lijst van meldingen van ingelogde gebruiker
- Per melding: titel, datum, status badge, foto preview
- Filter op status
- Zoekveld

**`resources/views/livewire/my-reports.blade.php`**
- Flux UI tabel/kaartweergave
- Status badges met kleurcodes

### Stap 4.2 — Route

```php
Route::get('/mijn-meldingen', App\Livewire\MyReports::class)
    ->middleware(['auth'])
    ->name('reports.my');
```

---

## 🛠️ Fase 5 — Admin (Filament)

### Stap 5.1 — Filament Resource: `ReportResource`

**`app/Filament/Resources/ReportResource.php`**
- Lijstweergave met kolommen: ID, titel, melder (email), categorie, status, datum
- Filters: status, categorie, datum range
- Detailpagina met foto's (MediaLibrary)
- Bewerken: status wijzigen, notitie toevoegen
- Acties: goedkeuren, afkeuren, markeren als spam
- Bulk acties: status wijzigen

### Stap 5.2 — Filament Resource: `UserResource`

**`app/Filament/Resources/UserResource.php`**
- Lijst: naam, email, rol, aantal meldingen, geregistreerd op
- Filter op rol
- Bewerken: naam, email, rol toewijzen

### Stap 5.3 — Nederlandse Filament UI

- `resources/lang/vendor/filament/nl/` — vertalingen
- Labels in het Nederlands: "Meldingen", "Gebruikers", "Status", "Categorie", etc.

### Stap 5.4 — Dashboard widgets

**`app/Filament/Widgets/ReportStats.php`**
- Totaal aantal meldingen
- Aantal per status (pending, confirmed, debunked)
- Aantal per categorie (donut chart)
- Recente meldingen (lijst)

---

## 📧 Fase 6 — Notificaties

### Stap 6.1 — Mailables

```bash
php artisan make:mail ReportReceived --markdown=emails.reports.received
php artisan make:mail ReportStatusUpdated --markdown=emails.reports.status-updated
```

**`app/Mail/ReportReceived.php`**
- Naar: melder (bevestiging) + admin (notificatie)
- Bevat: titel, datum, link naar melding

**`app/Mail/ReportStatusUpdated.php`**
- Naar: melder
- Bevat: oude status → nieuwe status, toelichting

### Stap 6.2 — Notificatie bij nieuwe melding

In `CreateReport::submit()`:
```php
Mail::to($report->user)->send(new ReportReceived($report));
Mail::to(env('ADMIN_EMAIL'))->send(new ReportReceived($report));
```

---

## 💰 Fase 7 — Support fee (Mollie)

### Stap 7.1 — Mollie configuratie

```
MOLLIE_API_KEY=test_xxxxxxxxxxxx
```

### Stap 7.2 — Livewire component: `SupportFee`

**`app/Livewire/SupportFee.php`**
- Knop "Steun ons — €5"
- Maakt Mollie payment aan
- Redirect naar Mollie checkout
- Webhook om betaling te verifiëren

### Stap 7.3 — Database: `payments` tabel

| Kolom | Type |
|---|---|
| `id` | bigIncrements |
| `user_id` | foreignId (nullable) |
| `report_id` | foreignId (nullable) |
| `mollie_payment_id` | string |
| `amount` | decimal(8,2) |
| `status` | string (paid, open, failed) |
| `paid_at` | timestamp (nullable) |
| `timestamps` | |

---

## 🌐 Fase 8 — Frontend & Content

### Stap 8.1 — Nederlandse layout

- `config/app.php`: `'locale' => 'nl'`
- Alle UI teksten in het Nederlands
- Navigatie: Home | Meld een UFO | Mijn meldingen | Over ons

### Stap 8.2 — Over ons pagina

**`resources/views/about.blade.php`**
- Wat is UFO Meldpunt? (missie)
- Hoe werkt het? (5 W's: Wie, Wat, Waar, Wanneer, Waarom)
- Statistieken: aantal meldingen, aantal bevestigd
- Contactinformatie

### Stap 8.3 — Publieke meldingen overzicht (optioneel)

**`app/Livewire/PublicReports.php`**
- Toont goedgekeurde meldingen aan iedereen
- Kaartweergave met locatie
- Filter op categorie

---

## 📱 Fase 9 — Mobielvriendelijk

- Flux UI is responsive out-of-the-box ✅
- Test op mobile viewports
- Foto's worden geoptimaliseerd via MediaLibrary (responsive images)

---

## 🧪 Fase 10 — Tests

### Feature tests

| Test | Bestand |
|---|---|
| Gast kan melding indienen | `tests/Feature/ReportTest.php` |
| Ingelogde gebruiker kan melding indienen | `tests/Feature/ReportTest.php` |
| Melder ziet eigen meldingen | `tests/Feature/MyReportsTest.php` |
| Admin kan meldingen beheren via Filament | `tests/Feature/Admin/ReportResourceTest.php` |
| E-mail notificatie bij nieuwe melding | `tests/Feature/ReportNotificationTest.php` |
| Mollie betaling aanmaken | `tests/Feature/SupportFeeTest.php` |

---

## 🚀 Fase 11 — Deploy & CI

- ✅ GitHub Actions workflows bestaan al (`tests.yml`, `lint.yml`)
- PHP 8.4+ nodig (lock file vereist dit)
- `.env.example` updaten met nieuwe variabelen:
  ```
  MOLLIE_API_KEY=
  ADMIN_EMAIL=admin@ufo-meldpunt.be
  ```

---

## 📅 Geschatte tijdlijn

| Fase | Geschatte tijd |
|---|---|
| Fase 1 — Datamodel & packages | 1-2 uur |
| Fase 2 — Gebruikersrollen | 1 uur |
| Fase 3 — Melding indienen | 2-3 uur |
| Fase 4 — Mijn meldingen | 1-2 uur |
| Fase 5 — Admin Filament | 2-3 uur |
| Fase 6 — Notificaties | 1-2 uur |
| Fase 7 — Support fee (Mollie) | 2-3 uur |
| Fase 8 — Frontend & content | 1-2 uur |
| Fase 9 — Mobiel testen | 0.5 uur |
| Fase 10 — Tests | 2-3 uur |
| **Totaal** | **~14-22 uur** |

---

## 💡 Extra feature ideeën (Unique Selling Points)

- 🗺️ **UFO Kaart**: Google Maps/Leaflet integratie met alle goedgekeurde meldingen
- 📊 **Statistieken dashboard**: Maandoverzicht, meest actieve regio's, piekmomenten
- 🤖 **AI Categorisatie**: Automatisch categoriseren op basis van beschrijving
- 🔔 **Alert systeem**: Notificatie bij nieuwe melding in jouw regio
- 🏆 **Leaderboard**: Meeste meldingen, beste foto's
- 🌙 **Dark mode** (Flux ondersteunt dit al)
- 📱 **Progressive Web App**: Installeerbaar op telefoon
- 🎮 **Gamification**: Badges voor "eerste melding", "10 meldingen", "bewezen melding"
