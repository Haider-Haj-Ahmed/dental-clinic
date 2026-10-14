<p align="center">
  <img src="https://raw.githubusercontent.com/Haider-Haj-Ahmed/dental-clinic/main/public/dental-pms.png" alt="Crystalline Dental PMS" width="100%"/>
</p>

<h1 align="center">Crystalline Dental PMS</h1>

<p align="center">
  A production-ready <strong>Laravel 13 REST API</strong> for full dental practice management —<br/>
  from patient records and clinical workflows to AI-assisted diagnosis, real-time notifications, and webhook integrations.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/OpenAPI-3.0.0-6BA539?logo=openapiinitiative&logoColor=white"/>
</p>

---

## Features

### Core Clinical
- **Patient management** — demographics, medical history, allergies, conditions, medications, consents
- **Appointments** — booking, status tracking, operatory assignment, appointment types
- **Encounters** — SOAP notes, lock/unlock workflow, clinical audit trail
- **Odontogram** — FDI tooth chart entries per encounter
- **Perio exams** — full periodontal charting with pocket depth measures
- **Treatment plans** — multi-item plans with present/accept/reject workflow
- **Prescriptions** — medication lists with dose, frequency, duration, PDF generation
- **Medical cases & documents** — X-rays, referrals, file upload with auth-protected download

### Billing
- **Invoices** — draft → finalized → voided lifecycle, itemized line items, PDF generation
- **Payments** — payment recording, partial payments, balance tracking
- **Payment plans** — installment schedules

### AI Integration (Gemini Flash)
- SOAP note suggestions from encounter text
- X-ray / document analysis
- Prescription suggestions with drug interaction flags
- Periodontal risk scoring
- Recall prioritization

### Authentication & Security
- Sanctum token auth with role-scoped abilities
- Email verification (signed URL, 60-minute expiry)
- **2FA / TOTP** — enable, confirm, disable, recovery codes, login challenge flow
- Active token management — list + revoke individual sessions
- Password history enforcement (last 5 passwords rejected)
- Global JSON error handling (no HTML error pages)
- Rate limiting per role (owner: unlimited, provider: 120/min, etc.)
- 28 authorization policies across all resources

### Notifications & Real-time
- **In-app notifications** (database channel) — per-user bell with unread count
- **Laravel Reverb** — WebSocket broadcasting on private channels (`App.Models.User.{id}`)
- Events: appointment booked/status changed, AI result ready, recall overdue, low stock alert
- All notifications queued — never block HTTP requests

### Clinic Configuration
- **Clinic settings** — identity, locale, branding, billing, appointments, reminders, security
- **Working hours** — per-day open/close times, closed days
- **Clinic closures** — holidays and one-off closure dates

### Webhooks
- Register webhook URLs for 8 event types
- HMAC-SHA256 signed payloads (`X-Webhook-Signature-256`)
- 3 retries with exponential backoff (1min → 5min → 15min)
- Delivery history log per webhook
- Auto-deactivation after 10 consecutive failures
- Test ping endpoint

### Automation
- Appointment reminders (daily 08:00)
- Recall reminders for overdue patients (daily 07:00)
- Low stock alerts (daily 08:30)
- Expired token pruning (daily 03:00)
- Weekly production report to owner (Monday 07:00)

### Infrastructure
- Laravel Reverb (self-hosted WebSocket)
- Database queue with Supervisor workers
- Branded HTML email templates (`BaseMail` + queue)
- Invoice PDF + Prescription PDF (`barryvdh/laravel-dompdf`)
- Full OpenAPI 3.0 spec (`dental-clinic-api.yaml`)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13, PHP 8.5 |
| Database | MySQL 8.0 |
| Auth | Laravel Sanctum |
| Queue | Database driver → Supervisor |
| WebSockets | Laravel Reverb |
| AI | Google Gemini Flash 2.0 |
| PDF | barryvdh/laravel-dompdf |
| 2FA | pragmarx/google2fa-laravel |
| QR Code | bacon/bacon-qr-code |

---

## Getting Started

### Requirements
- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `json`, `xml`, `curl`, `gd`, `zip`
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2.x
- Node.js 18+

### Installation

```bash
git clone https://github.com/Haider-Haj-Ahmed/dental-clinic.git
cd dental-clinic

composer install
cp .env.example .env
php artisan key:generate

# Configure DB credentials in .env, then:
php artisan migrate
php artisan db:seed
```

### Running locally

```bash
# Terminal 1 — app server
php artisan serve

# Terminal 2 — queue worker
php artisan queue:work

# Terminal 3 — Reverb WebSocket server (optional for real-time)
php artisan reverb:start
```

### Default login (after seeding)

| Email | Password | Role |
|---|---|---|
| `owner@clinic.local` | `password` | Owner |
| `provider@clinic.local` | `password` | Provider (Dr. Sarah Mansour) |
| `specialist@clinic.local` | `password` | Provider (Dr. Rami Hassan) |
| `reception@clinic.local` | `password` | Receptionist |

---

## API Reference

Full OpenAPI 3.0 spec: [`dental-clinic-api.yaml`](./dental-clinic-api.yaml)

**Base URL:** `http://localhost:8000/api/v1`

**Authentication:** Bearer token (Sanctum)
```
Authorization: Bearer {token}
```

### Key endpoint groups

| Group | Base path | Auth |
|---|---|---|
| Auth | `/auth/*` | Public + Sanctum |
| 2FA | `/auth/2fa/*` | Sanctum + verified email |
| Patients | `/patients/*` | `patients:read/write` |
| Appointments | `/appointments/*` | `appointments:read/write` |
| Clinical | `/encounters/*`, `/prescriptions/*`, etc. | `clinical:read/write` |
| Billing | `/invoices/*`, `/payments/*` | `billing:read/write` |
| Inventory | `/inventory-items/*` | `inventory:read/write` |
| Notifications | `/notifications/*` | Sanctum |
| Settings | `/settings` | Owner only |
| Working hours | `/working-hours`, `/closures` | Sanctum / Owner |
| Webhooks | `/webhooks/*` | Owner only |
| AI | `/ai-results/*` | `clinical:write` |
| Reports | `/reports/*` | Sanctum |

---

## Project Structure

```
app/
├── Events/          # AppointmentBooked, AiResultReady, RecallOverdue, LowStockAlert, ...
├── Http/
│   ├── Controllers/Api/    # 35+ API controllers
│   ├── Requests/           # Form request validation classes
│   └── Resources/          # API Resource transformers
├── Jobs/            # Scheduled + queued jobs (reminders, PDF, webhooks)
├── Listeners/       # Event listeners (notifications + webhook dispatch)
├── Mail/            # Queued mailables (BaseMail, VerifyEmailMail, TwoFactorEnabledMail)
├── Models/          # 40+ Eloquent models
├── Notifications/   # InAppNotification (database channel)
├── Policies/        # 28 authorization policies
└── Services/        # FileStorageService, WebhookDispatcher, AI services

database/
├── migrations/      # Full schema — all tables
├── seeders/         # CatalogueSeeder (prod-safe) + DevelopmentSeeder (dev only)
└── factories/       # Factories with states for all major models

resources/views/
├── emails/          # Branded HTML email templates
│   ├── layouts/base.blade.php
│   └── auth/        # verify-email, two-factor-enabled
└── pdf/             # PDF templates (invoice, prescription)

routes/
├── api.php          # All API routes (versioned under /v1/)
├── channels.php     # Reverb private channel authorization
└── console.php      # Scheduled task definitions
```

---

## Webhooks

Register a webhook endpoint to receive real-time event notifications:

```bash
POST /api/v1/webhooks
{
  "url": "https://your-system.com/webhooks/dental",
  "events": ["appointment.booked", "invoice.finalized", "payment.received"],
  "description": "ERP integration"
}
```

Every delivery is signed:
```
X-Webhook-Signature-256: sha256=<hmac>
X-Webhook-Event: appointment.booked
X-Webhook-Delivery: 42
```

Verify in your receiver:
```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
if (!hash_equals($expected, $request->header('X-Webhook-Signature-256'))) {
    abort(401);
}
```

Supported events: `appointment.booked`, `appointment.status_changed`, `invoice.finalized`, `payment.received`, `patient.created`, `ai.result_ready`, `recall.overdue`, `inventory.low_stock`

---

## 2FA Flow

```
1. POST /auth/2fa/enable      → returns secret + qr_code_uri
2. User scans QR in authenticator app
3. POST /auth/2fa/confirm     → { code: "123456" }
   → returns 8 recovery codes (shown ONCE — save them)

Login with 2FA active:
1. POST /auth/login            → { requires_2fa: true, two_factor_token: "..." }
2. POST /auth/2fa/challenge    → { two_factor_token, code: "123456", device_name }
   → returns full Sanctum token
```

---

## Roadmap

See [`ROADMAP.md`](./ROADMAP.md) for the full implementation status and what's planned next.

**Current status: Steps 1–12 complete** — auth hardening, notifications, settings, PDF, scheduler, webhooks all implemented.

**Next priorities:**
- 13 missing transactional email templates
- SMS / WhatsApp notifications
- Treatment plan + report PDFs
- Patient portal

---

## License

MIT License — see [`LICENSE`](./LICENSE)

---

*Built with Laravel 13 · Crystalline Dental PMS v3.0 · Last updated 2026-10-05*
