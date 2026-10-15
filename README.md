<p align="center">
  <img src="https://raw.githubusercontent.com/Haider-Haj-Ahmed/dental-clinic/main/public/dental-pms.png" alt="Crystalline Dental PMS" width="100%"/>
</p>

<h1 align="center">Crystalline Dental PMS</h1>

<p align="center">
  A production-ready <strong>Laravel 13 REST API</strong> for full dental practice management —<br/>
  from patient records and clinical workflows to AI-assisted diagnosis, real-time notifications,<br/>
  webhook integrations, and a patient self-service portal.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/OpenAPI-3.0.0-6BA539?logo=openapiinitiative&logoColor=white"/>
  <img src="https://img.shields.io/badge/Tests-30%20suites-brightgreen"/>
</p>

---

## Features

### Core Clinical
- **Patient management** — demographics, medical history, allergies, conditions, medications, consents, archive/restore/purge (GDPR)
- **Appointments** — booking, status tracking, operatory assignment, appointment types, reminders
- **Encounters** — SOAP notes, lock/unlock workflow, clinical audit trail
- **Odontogram** — FDI tooth chart entries per encounter
- **Perio exams** — full periodontal charting with pocket depth measures
- **Treatment plans** — multi-item plans with present/accept/reject workflow + PDF export
- **Prescriptions** — medication lists with dose, frequency, duration, instructions + PDF export
- **Referral letters** — specialist referrals with urgency levels + PDF export
- **Medical cases & documents** — X-rays, referrals, file upload with auth-protected download

### Billing
- **Invoices** — draft → finalized → voided lifecycle, itemized line items, PDF export
- **Payments** — payment recording, partial payments, balance tracking, receipt emails
- **Payment plans** — installment schedules

### AI Integration (Gemini Flash 2.0)
- SOAP note suggestions from encounter text
- X-ray / document analysis
- Prescription suggestions with drug interaction flags
- Periodontal risk scoring
- Recall prioritization

### Authentication & Security
- Sanctum token auth with role-scoped abilities
- **Email verification** — signed URL, 60-minute expiry, resend endpoint
- **2FA / TOTP** — enable, confirm, disable, recovery codes (8), login challenge flow
- **Active token management** — list + revoke individual sessions
- **Password history** — last 5 passwords enforced on reset + change
- **Password expiry** — configurable forced rotation (`AUTH_PASSWORD_EXPIRY_DAYS`)
- **Role-scoped token expiry** — per-role TTL (owner: no expiry, provider: 30d, etc.)
- **IP allowlist middleware** — exact IPs and CIDR ranges
- Global JSON error handling — 401/403/404/405/422/429/500 never return HTML
- Rate limiting per role + strict throttle on auth endpoints
- 28 authorization policies across all resources

### Notifications & Real-time
- **In-app notifications** (database channel) — per-user bell with unread count, mark read, delete
- **Laravel Reverb** — WebSocket broadcasting on private channels (`App.Models.User.{id}`)
- **Events** — appointment booked/status changed, AI result ready, recall overdue, low stock
- All notifications queued — never block HTTP requests

### Transactional Emails (11 templates)
| Email | Trigger | Recipient |
|---|---|---|
| Welcome | Owner creates staff account | New staff member |
| Appointment Confirmation | Booking created | Patient |
| Appointment Reminder | Daily job (24h / 2h before) | Patient |
| Appointment Cancelled | Status → cancelled | Patient |
| Invoice Finalized | Invoice finalized (with PDF) | Patient |
| Payment Receipt | Payment recorded | Patient |
| Recall Reminder | Overdue recall job | Patient |
| Low Stock Alert | Daily stock check | Owner |
| AI Result Pending | AI analysis complete | Requesting provider |
| Password Changed | Password changed | User |
| New Device Login | Successful login | User |

### Clinic Configuration
- **Clinic settings** — identity, locale, branding, billing, appointments, reminders, security
- **Working hours** — per-day open/close, closed days
- **Clinic closures** — holidays and one-off closure dates

### Patient Portal
- Passwordless OTP login (email) — 6-digit code, 10-minute expiry
- Patient-scoped Sanctum tokens (`portal:read` ability only)
- Profile, appointments, invoices (with PDF download), prescriptions

### Webhooks
- 8 event types, HMAC-SHA256 signed payloads
- 3 retries with exponential backoff, delivery history, auto-deactivation, test ping

### PDF Generation
- Invoice, Prescription, Treatment Plan, Referral Letter
- All 5 reports exportable via `?format=pdf` or `?format=xlsx`

### Automation (Scheduled Jobs)
| Job | Schedule | Action |
|---|---|---|
| `PruneExpiredTokensJob` | Daily 03:00 | Delete expired Sanctum tokens |
| `SendRecallRemindersJob` | Daily 07:00 | Email overdue recalls to patients |
| `SendAppointmentRemindersJob` | Daily 08:00 | Email appointment reminders |
| `CheckLowStockJob` | Daily 08:30 | Alert owner of low inventory |
| `GenerateWeeklyReportJob` | Monday 07:00 | Weekly production summary |
| `ArchiveOldAuditLogsJob` | Monthly 1st 02:00 | Archive + purge old audit logs |

### Compliance
- `GET /patients/{id}/export` — full patient record JSON export
- `DELETE /patients/{id}/purge` — two-step hard delete

### API Polish
- `X-API-Version` + `X-Request-ID` headers on every response
- `GET /api/v1/health` — DB + cache + queue + storage status
- `GET /api/docs` — Swagger UI (public)
- `?sort_by=` + `?sort_dir=` on collection endpoints
- Timezone-aware output via `SetClinicTimezone` middleware
- Arabic + English localization (`lang/en/` + `lang/ar/`)
- Sentry integration

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
| PDF | barryvdh/laravel-dompdf ^3.0 |
| Excel | phpoffice/phpspreadsheet ^2.0 |
| 2FA | pragmarx/google2fa-laravel |
| Monitoring | sentry/sentry-laravel ^4.0 |

---

## Getting Started

### Requirements
- PHP 8.2+ (`pdo_mysql`, `mbstring`, `openssl`, `json`, `xml`, `curl`, `gd`, `zip`)
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2.x

### Installation

```bash
git clone https://github.com/Haider-Haj-Ahmed/dental-clinic.git
cd dental-clinic
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Running locally

```bash
php artisan serve          # Terminal 1
php artisan queue:work     # Terminal 2
php artisan reverb:start   # Terminal 3 (optional — real-time)
```

### Default accounts (after seeding)

| Email | Password | Role |
|---|---|---|
| `owner@clinic.local` | `password` | Owner |
| `provider@clinic.local` | `password` | Provider |
| `specialist@clinic.local` | `password` | Provider |
| `reception@clinic.local` | `password` | Receptionist |

---

## API Reference

**Interactive docs:** `GET /api/docs` (Swagger UI, no auth required)

**Base URL:** `http://localhost:8000/api/v1`

**Auth:** `Authorization: Bearer {token}`

### Patient Portal

```bash
POST /api/v1/portal/auth/request-otp  { "email": "patient@example.com" }
POST /api/v1/portal/auth/verify-otp   { "email": "...", "code": "123456", "device_name": "iPhone" }
GET  /api/v1/portal/me
GET  /api/v1/portal/appointments
GET  /api/v1/portal/invoices
GET  /api/v1/portal/invoices/{id}/pdf
GET  /api/v1/portal/prescriptions
```

### Webhook verification

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
if (!hash_equals($expected, $request->header('X-Webhook-Signature-256'))) {
    abort(401);
}
```

### 2FA flow

```
POST /auth/2fa/enable      → secret + qr_code_uri
POST /auth/2fa/confirm     { code: "123456" } → 8 recovery codes
POST /auth/login           → { requires_2fa: true, two_factor_token: "..." }
POST /auth/2fa/challenge   { two_factor_token, code, device_name } → Sanctum token
```

---

## Testing

```bash
php artisan test
```

30 test suites — auth, clinical, billing, AI, audit, patients, inventory, recalls, dashboard, portal, notifications, webhooks, mail dispatch, and more.

---

## Roadmap

See [`ROADMAP.md`](./ROADMAP.md) — API fully complete. Remaining: SMS/WhatsApp, Arabic RTL UI, portal consent signing, CI/CD, S3 storage.

---

## License

MIT — see [`LICENSE`](./LICENSE)

---

*Crystalline Dental PMS v3.0 · Laravel 13 · Last updated 2026-10-14*
