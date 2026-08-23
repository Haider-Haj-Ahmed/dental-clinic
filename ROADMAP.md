# Crystalline Dental PMS — SaaS Completion Roadmap

> **Option 1 — Per-clinic deployment model.**
> Each clinic gets their own server instance and database.
> This document covers everything left to build for a production-ready, commercially deployable system.

---

## Legend
- ✅ Already implemented
- 🔴 Missing — critical (blocks production)
- 🟡 Missing — important (needed for commercial viability)
- 🔵 Missing — enhancement (professional polish)

---

## 1. Authentication & Security

### Already built ✅
- Login / logout / logout-all (Sanctum tokens)
- Register (owner-bootstrap + owner-creates-staff)
- Role-scoped token abilities (owner, provider, receptionist, assistant)
- 28 authorization policies
- Throttle on auth routes (10/min per IP)
- Pessimistic locking on appointment booking
- Full audit logging via AuditObserver (14 models)

### Missing

#### 🔴 Two-Factor Authentication (2FA / TOTP)
- Implement TOTP (Time-based One-Time Password) via `google2fa-laravel`
- Fields needed on `users` table: `two_factor_secret`, `two_factor_confirmed_at`, `two_factor_recovery_codes`
- Endpoints:
  - `POST /auth/2fa/enable` — generate secret + QR code URI
  - `POST /auth/2fa/confirm` — confirm with first OTP code
  - `POST /auth/2fa/disable` — disable with password confirmation
  - `GET  /auth/2fa/recovery-codes` — list recovery codes
  - `POST /auth/2fa/recovery-codes` — regenerate recovery codes
- Login flow: after credentials pass, if 2FA is enabled return `{ requires_2fa: true, temp_token: "..." }` instead of a full Sanctum token. Client submits OTP to `POST /auth/2fa/challenge` with the temp token to receive the real Sanctum token.
- Package: `pragmarx/google2fa-laravel` + `bacon/bacon-qr-code`

#### 🔴 OTP via Email (for password reset confirmation)
- Currently password reset only sends a link via email
- Add optional OTP step: 6-digit code sent to email, valid for 10 minutes
- Use Laravel's built-in `password_reset_tokens` table — just change the broker to store a 6-digit code instead of a URL token
- Endpoint: `POST /auth/otp/verify`

#### 🔴 Email Verification
- `email_verified_at` is not in `User::$fillable` — fix this
- Fire `Illuminate\Auth\Events\Registered` on register so Laravel's built-in verification mail sends automatically
- Endpoint: `POST /auth/email/verify/{id}/{hash}` (signed URL)
- Endpoint: `POST /auth/email/resend`
- Gate new staff accounts behind `verified` middleware for sensitive routes

#### 🔴 Active Session / Token Management
- Endpoint: `GET /auth/tokens` — list all active tokens with device name, last used, IP, created at
- Endpoint: `DELETE /auth/tokens/{id}` — revoke a specific token (not just current)
- Store `last_used_at` and `last_used_ip` on token creation/use

#### 🟡 Account Lockout (Web)
- Web login has no brute-force protection beyond Vite not running
- Implement `RateLimiter` in `LoginController` — lock account for 15 minutes after 5 failed attempts
- Return `429` with `retry_after` header

#### 🟡 Password Policy Enforcement
- Already: `min:8 + mixedCase + numbers` on register/reset ✅
- Add: prevent reuse of last 5 passwords (store hashed history in `password_histories` table)
- Add: force password change after X days (configurable in clinic settings)
- Field on `users`: `password_changed_at`

#### 🟡 Sanctum Token Expiry
- Currently 7 days in `sanctum.php`
- Make this configurable per role in clinic settings (e.g. receptionist tokens expire in 8h, provider tokens in 24h)

#### 🔵 IP Allowlist (optional per clinic)
- Clinic owner can whitelist IP ranges
- Middleware rejects requests from outside allowed IPs
- Store in `clinic_settings` table

---

## 2. Notifications

> Currently: zero notification infrastructure. No Jobs, Mail, Notifications, or Events exist.

### 🔴 Mail Infrastructure
- Configure `MAIL_*` in `.env` (SMTP, Mailgun, or SES)
- Create base `Mailable` with clinic branding (logo, name, color)
- All transactional emails must use queued jobs — never send synchronously in a request

#### Email notifications to implement:
| Trigger | Recipient | Content |
|---|---|---|
| New staff account created | New staff member | Welcome email + temporary password |
| Password reset requested | User | Reset link / OTP code |
| Email verification | New user | Verification link |
| Appointment booked | Patient (if email on file) | Confirmation with date/time/provider |
| Appointment reminder | Patient | 24h and 2h before appointment |
| Appointment cancelled | Patient | Cancellation notice |
| Appointment rescheduled | Patient | New date/time |
| Invoice finalized | Patient | Invoice summary + PDF attachment |
| Payment received | Patient | Receipt |
| Recall due | Patient | Recall reminder with booking link |
| Low stock alert | Owner / admin | Item name, current qty, reorder level |
| AI result pending review | Provider | Summary of pending AI suggestions |
| 2FA enabled | User | Security confirmation |
| Password changed | User | Security alert |
| New login from new device | User | Security alert with device info |

### 🔴 Queue Setup
- Switch `QUEUE_CONNECTION` from `sync` to `database` (simplest) or `redis` (production)
- Run `php artisan queue:table && php artisan migrate` for database driver
- All mail and heavy jobs must be dispatched to queue
- Set up `php artisan queue:work` as a system service (Supervisor on Linux)
- Set up `php artisan schedule:run` via cron for recurring tasks

### 🟡 SMS Notifications
- Critical for clinics where patients don't check email
- Integrate Twilio or a local SMS provider (for Syria: consider local SMS gateway)
- Notifications to send via SMS:
  - Appointment reminder (24h before)
  - Appointment reminder (2h before)
  - Recall overdue reminder
  - OTP code (for 2FA or password reset)
- `NotificationService` with `channel` preference per patient (`email` | `sms` | `both`)

### 🟡 In-App Notifications
- Bell icon in topbar already exists in UI — needs a backend
- `notifications` table (Laravel's built-in `DatabaseNotification`)
- Endpoint: `GET /notifications` — paginated list
- Endpoint: `POST /notifications/{id}/read` — mark single as read
- Endpoint: `POST /notifications/read-all`
- Endpoint: `DELETE /notifications/{id}`
- Real-time: consider Laravel Reverb (WebSocket) or Pusher for live bell count updates

### 🟡 WhatsApp Notifications
- For the Arab market this is more important than SMS or email
- Integrate via WhatsApp Business API (Meta) or a third-party like Twilio WhatsApp
- Send: appointment reminders, recalls, payment receipts
- Optional: two-way booking confirmation ("Reply YES to confirm")

### 🔵 Notification Preferences per User/Patient
- Table: `notification_preferences`
- Fields: `notifiable_type`, `notifiable_id`, `channel` (email/sms/whatsapp), `type` (appointment_reminder/recall/etc.), `enabled`
- Endpoint: `GET /notification-preferences`
- Endpoint: `PATCH /notification-preferences`

### 🔵 Notification Templates
- Clinic owner can customize the text of each notification type
- Table: `notification_templates` (`type`, `channel`, `subject`, `body`, `variables`)
- Variables system: `{{patient_name}}`, `{{appointment_date}}`, `{{clinic_name}}`, etc.

---

## 3. Clinic Settings

> Currently: no settings system exists. Clinic name/logo are hardcoded in views.

### 🔴 Core Clinic Settings
- Table: `clinic_settings` (key-value or single JSON column)
- Or: dedicated `clinics` table with structured columns (recommended)
- Fields:
  - `name`, `logo_path`, `address`, `phone`, `email`, `website`
  - `currency` (default: USD or local)
  - `timezone`
  - `date_format`, `time_format`
  - `language` (ar / en)
  - `tax_rate` (%)
  - `tax_name` (e.g. "VAT")
  - `invoice_prefix` (e.g. "INV-")
  - `invoice_starting_number`
  - `appointment_slot_duration` (in minutes, default: 15)
  - `cancellation_policy_hours` (how many hours before appointment patient can cancel)
  - `session_timeout_minutes`
  - `require_2fa` (force 2FA for all staff)

- Endpoints:
  - `GET  /settings` — owner + admin only
  - `PATCH /settings`
  - `POST /settings/logo` — upload clinic logo

### 🟡 Working Hours Configuration
- Table: `working_hours`
- Fields: `day_of_week` (0–6), `open_time`, `close_time`, `is_closed`
- Endpoints: `GET /working-hours`, `PUT /working-hours`
- Used by appointment booking to block slots outside working hours

### 🟡 Holiday / Closure Management
- Table: `clinic_closures`
- Fields: `date`, `reason`, `all_day` (bool), `start_time`, `end_time`
- Endpoints: `GET /closures`, `POST /closures`, `DELETE /closures/{id}`
- Used by appointment system to block bookings on closure days

### 🟡 Appointment Reminder Settings
- How many hours before to send first reminder
- How many hours before to send second reminder
- Which channels to use (email / SMS / WhatsApp)
- Stored in `clinic_settings`

### 🔵 Branding Settings
- Upload clinic logo (stored via `FileStorageService`)
- Primary color (used in emails and printed documents)
- Letterhead content for prescriptions and invoices

---

## 4. PDF Generation

> Currently: no PDF generation exists. Invoices, prescriptions, and reports are API JSON only.

### 🔴 Invoice PDF
- Package: `barryvdh/laravel-dompdf` or `spatie/laravel-pdf`
- Endpoint: `GET /invoices/{invoice}/pdf`
- Content: clinic logo + letterhead, patient details, itemized services, totals, tax, payment status
- Attach to the "Invoice finalized" email automatically

### 🔴 Prescription PDF
- Endpoint: `GET /prescriptions/{prescription}/pdf`
- Content: clinic letterhead, provider name + signature line, patient details, medication list with dosage/frequency/duration, issue date
- Print-ready format (A5 or A4)

### 🟡 Treatment Plan PDF
- Endpoint: `GET /treatment-plans/{plan}/pdf`
- Content: proposed procedures, fees, acceptance status
- Used for presenting to patients

### 🟡 Report PDF / Excel Export
- All report endpoints should support `?format=pdf` and `?format=xlsx`
- Package for Excel: `maatwebsite/excel`

### 🔵 Referral Letter PDF
- Endpoint: `POST /patients/{patient}/referral-letter`
- Template-based: provider selects referred specialist + reason, generates a formatted letter

---

## 5. Patient Portal (optional but high value)

> A separate interface for patients to view their own data.

### 🟡 Patient Authentication
- Patients authenticate separately from staff (different guard)
- Login via email + OTP (no password — magic link or OTP only)
- Table: `patient_portal_tokens` or use Laravel Sanctum with a separate guard
- Endpoints (separate prefix `/portal/`):
  - `POST /portal/auth/request-otp`
  - `POST /portal/auth/verify-otp`
  - `POST /portal/auth/logout`

### 🟡 Patient-Facing Endpoints
- `GET /portal/me` — own profile
- `GET /portal/appointments` — upcoming + past
- `GET /portal/invoices` — own invoices with PDF download
- `GET /portal/treatment-plans` — own treatment plans (present/accepted/rejected)
- `GET /portal/prescriptions` — own prescriptions
- `GET /portal/consents` — consent forms with ability to sign online
- `POST /portal/appointments/request` — request an appointment (staff confirms)

---

## 6. Scheduling & Automation (Background Jobs)

> Currently: no queue workers, no scheduled tasks.

### 🔴 Scheduled Tasks (Laravel Scheduler)
Add to `routes/console.php` or `App\Console\Kernel`:

| Schedule | Job | Description |
|---|---|---|
| Daily 8:00 AM | `SendAppointmentRemindersJob` | Find appointments in next 24h, send reminders |
| Daily 7:00 AM | `SendRecallRemindersJob` | Find overdue recalls, send reminders |
| Daily | `PruneExpiredTokensJob` | Delete expired Sanctum tokens |
| Daily | `CheckLowStockJob` | Find items below reorder level, notify owner |
| Weekly | `GenerateWeeklyReportJob` | Email production report to owner |
| Monthly | `ArchiveOldAuditLogsJob` | Move audit logs older than 1 year to archive |

### 🟡 Queue Workers (Jobs)
- `SendEmailJob` — wraps all outgoing emails
- `SendSmsJob` — wraps all SMS sends
- `SendWhatsAppJob` — wraps WhatsApp messages
- `ProcessAiAnalysisJob` — move AI calls off the HTTP request (currently synchronous)
- `GeneratePdfJob` — async PDF generation for large reports
- `ImportPatientsJob` — bulk patient CSV import

### 🔵 Appointment Reminder Pipeline
```
AppointmentBooked event
  → SendConfirmationEmailListener
  → ScheduleReminderJob (dispatched 24h and 2h before appointment)
      → SendAppointmentReminderNotification (email + SMS + WhatsApp)
```

---

## 7. File Storage

### Already built ✅
- `FileStorageService` exists
- Patient medical document upload

### 🔴 Production Storage Driver
- Currently likely using `local` disk — files stored on server
- Switch to `s3` or compatible (MinIO for self-hosted, Wasabi for cheap S3-compatible)
- All document uploads, logos, X-ray images must use the configured disk
- Never store files inside `public/` — always behind auth (already done via download endpoint ✅)

### 🟡 File Validation Hardening
- Max file size limits per type (X-ray: 20MB, document: 10MB, logo: 2MB)
- MIME type validation using `finfo` not just extension
- Virus scan hook (optional — ClamAV integration via `sunspikes/php-clamav`)

### 🔵 Automatic Backup
- Daily DB dump + file storage backup
- Package: `spatie/laravel-backup`
- Store backups on a separate disk (S3 / remote)
- Notify owner on backup failure

---

## 8. API Completions & Polish

### 🔴 Global Error Handling
- Currently Laravel's default exception handler returns HTML for some errors
- Override `Handler.php` to always return JSON for API routes:
  - `404` → `{ message: "Resource not found" }`
  - `422` → `{ message: "Validation failed", errors: {...} }`
  - `401` → `{ message: "Unauthenticated" }`
  - `403` → `{ message: "Forbidden" }`
  - `429` → `{ message: "Too many requests", retry_after: N }`
  - `500` → `{ message: "Server error" }` (no stack trace in production)

### 🔴 Pagination Consistency
- Ensure all collection endpoints support:
  - `?per_page=` (max 100)
  - `?page=`
  - `?sort_by=` + `?sort_dir=asc|desc`
  - `?search=` where applicable

### 🔴 Health Check Endpoint
- `GET /api/health` — public, no auth
- Returns: DB connectivity, queue connectivity, storage connectivity, app version
- Used by uptime monitors and deployment checks

### 🟡 API Versioning Strategy
- Current: `/api/v1/` ✅
- Document the versioning policy: v1 is stable, breaking changes go to v2
- Add `X-API-Version` response header to every response

### 🟡 Webhook System
- Clinics can register webhook URLs for events
- Table: `webhooks` (`url`, `events[]`, `secret`, `active`)
- Events to support: `appointment.created`, `appointment.cancelled`, `invoice.finalized`, `payment.received`, `patient.created`
- Sign payloads with HMAC-SHA256 using the webhook secret
- Queue delivery with retry on failure (3 attempts, exponential backoff)
- Endpoints: `GET /webhooks`, `POST /webhooks`, `DELETE /webhooks/{id}`

### 🟡 Swagger UI Hosting
- Host `dental-clinic-api.yaml` via `darkaonline/l5-swagger` or serve the file statically
- Accessible at `/api/docs` (protected by auth or IP in production)
- Auto-update the spec when new endpoints are added

### 🔵 Request ID Tracing
- Add `X-Request-ID` header to every response (UUID)
- Log this ID with every log entry
- Useful for debugging production issues

### 🔵 API Changelog
- Maintain `CHANGELOG.md` with semantic versioning
- Document every breaking change, new endpoint, and deprecation

---

## 9. Web UI — Remaining Screens

> Currently only Login + AI Assistant screens are implemented.

### 🔴 Dashboard (Overview)
- KPI cards: today's appointments, revenue this month, pending recalls, low stock alerts
- Appointment calendar (today's schedule)
- Recent activity feed
- Pending AI suggestions count

### 🔴 Appointments Screen
- Calendar view (day / week / month)
- Appointment creation modal
- Drag-to-reschedule
- Status badges (scheduled / confirmed / in-chair / completed / cancelled / no-show)
- Quick patient search

### 🔴 Patients Screen
- Searchable, filterable patient list
- Patient profile page:
  - Demographics tab
  - Medical history tab (conditions, allergies, medications)
  - Clinical tab (encounters, odontogram, perio, treatment plans)
  - Financial tab (invoices, payments, payment plans)
  - Documents tab
  - AI Insights tab
  - Timeline tab

### 🟡 Encounters / Clinical Screens
- SOAP note editor with lock/unlock toggle
- Odontogram (FDI tooth chart — interactive SVG)
- Perio exam data entry grid
- Treatment plan presenter

### 🟡 Billing Screens
- Invoice list + creation
- Invoice detail with PDF download
- Payment recording
- Payment plan management

### 🟡 Reports Screen
- Charts: revenue over time, appointment volume, recall performance
- Date range picker
- Export to PDF / Excel

### 🟡 Settings Screen
- Clinic profile form
- Working hours editor
- Staff management (create / edit / deactivate users)
- Notification preferences
- 2FA setup wizard

### 🔵 All Remaining Screens
- Recalls list + send reminder action
- Inventory list + stock adjustment
- Purchase orders
- Prescriptions list + PDF view
- Audit logs viewer
- Provider profiles

---

## 10. Seeder & Factory Corrections

### 🔴 `email_verified_at` not in `User::$fillable`
- Add to `$fillable` in `User.php`
- OR change seeder to use `User::forceCreate()`

### 🔴 Split Seeder by Environment
```php
// DatabaseSeeder.php
public function run(): void
{
    $this->call(CatalogueSeeder::class);   // operatories, appt types — safe in production

    if (! app()->isProduction()) {
        $this->call(DevelopmentSeeder::class); // test users, fake patients, appointments
    }
}
```

### 🟡 `InvoiceFactory` — `->create()` inside state definition
- `finalized()` state calls `User::factory()->create()` at definition time
- Change to use `User::factory()` (without `->create()`) — let Laravel resolve lazily

### 🔵 Factory States for all models
- Every factory should have meaningful states:
  - `UserFactory::provider()`, `::receptionist()`, `::assistant()`
  - `AppointmentFactory::cancelled()`, `::noShow()`, `::completed()`
  - `PatientFactory::archived()`, `::withAllergies()`, `::withActiveMedications()`
  - `InvoiceFactory::paid()`, `::overdue()`, `::voided()`

---

## 11. Testing

### Already built ✅
- 21 PHPUnit feature test suites

### 🔴 Auth Test Coverage
- Test register (first user = owner, owner creates staff, non-owner blocked)
- Test forgot-password / reset-password flow end-to-end
- Test 2FA enable / challenge / disable (once built)
- Test token expiry
- Test account lockout after failed attempts

### 🔴 Notification Tests
- Assert emails are queued (not sent) during tests — use `Mail::fake()`
- Assert correct mailable is dispatched for each trigger
- Assert SMS job is dispatched

### 🟡 Policy Test Coverage
- Every policy method tested for every role
- Assert receptionist cannot access clinical write endpoints
- Assert provider cannot access billing write endpoints

### 🟡 Factory / Seeder Tests
- Assert `DatabaseSeeder` runs without errors on fresh DB
- Assert factories produce valid model instances with correct relationships

### 🔵 Performance Tests
- Identify N+1 queries using `barryvdh/laravel-debugbar` in dev
- Assert key endpoints complete under 200ms with realistic data volume

---

## 12. Infrastructure & Deployment

### 🔴 Environment Configuration
Required `.env` additions beyond Laravel defaults:
```dotenv
# App
APP_URL=https://clinic.yourdomain.com
APP_TIMEZONE=Asia/Damascus

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@clinic.yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue
QUEUE_CONNECTION=database   # or redis in production

# Storage
FILESYSTEM_DISK=local        # or s3 in production
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=

# Session (production)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# AI
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash

# SMS (when implemented)
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=

# 2FA
GOOGLE2FA_ENABLED=true
```

### 🔴 Server Requirements (document this)
- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `json`, `xml`, `curl`, `gd`, `zip`
- MySQL 8.0+ or MariaDB 10.6+
- Node.js 18+ (for asset compilation)
- Composer 2.x
- Supervisor (for queue workers)
- Nginx or Apache
- SSL certificate (Let's Encrypt — free)
- Minimum: 2 vCPU, 2GB RAM, 20GB SSD

### 🔴 Nginx Configuration
```nginx
server {
    listen 80;
    server_name clinic.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name clinic.yourdomain.com;
    root /var/www/dental-clinic/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/clinic.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/clinic.yourdomain.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    client_max_body_size 25M;
}
```

### 🔴 Supervisor Configuration (queue worker)
```ini
[program:dental-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dental-clinic/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/dental-queue.log
```

### 🔴 Cron (Laravel Scheduler)
```cron
* * * * * cd /var/www/dental-clinic && php artisan schedule:run >> /dev/null 2>&1
```

### 🟡 Deployment Script
```bash
#!/bin/bash
cd /var/www/dental-clinic
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
npm ci && npm run build
php artisan queue:restart
sudo systemctl reload nginx
```

### 🟡 Automated Backups
- Package: `spatie/laravel-backup`
- Daily DB dump + uploaded files
- Store on separate S3 bucket or remote server
- Notify owner on backup failure
- Retention: keep 7 daily, 4 weekly, 3 monthly

### 🔵 Docker / Containerization
- `Dockerfile` for the Laravel app
- `docker-compose.yml` for local dev: app + mysql + redis + mailhog
- Makes onboarding new developers and deploying new clinic instances faster

### 🔵 CI/CD Pipeline (GitHub Actions)
```yaml
# .github/workflows/ci.yml
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with: { php-version: '8.2' }
      - run: composer install
      - run: cp .env.testing .env && php artisan key:generate
      - run: php artisan migrate --env=testing
      - run: php artisan test
```

---

## 13. Compliance & Data Protection

### 🟡 Data Retention Policy
- Patient data must be retained for a minimum period (check Syrian medical regulations)
- Implement soft delete on all patient-related models (most already done ✅)
- Add `scheduled_deletion_at` to patients who have been archived for 7+ years
- Notify owner before permanent deletion

### 🟡 Patient Data Export
- `GET /patients/{patient}/export` — returns full patient record as JSON or PDF
- Patient's right to their own data

### 🟡 Patient Data Deletion
- `DELETE /patients/{patient}/purge` — hard delete all records (GDPR-style)
- Requires owner authorization + confirmation token
- Cascades to all sub-resources and documents

### 🔵 Consent Management
- Digital consent forms with patient signature (date + IP logged)
- Consent versioning — if form template changes, re-consent required
- Already have `PatientConsent` model ✅ — add `signature_data`, `ip_address`, `consented_at`

### 🔵 Terms of Service & Privacy Policy
- Static pages: `/terms`, `/privacy`
- Store acceptance in `user_agreements` table on first login
- If T&C version changes, require re-acceptance

---

## 14. Localization

### 🟡 Arabic / RTL Support
- All UI strings must go through Laravel's translation system (`__('key')`)
- Create `lang/ar/` translation files for all UI strings
- RTL CSS: add `dir="rtl"` to `<html>` and include RTL stylesheet
- Date formatting: Hijri calendar option for Arab users
- Number formatting: Arabic-Indic numerals option

### 🟡 Timezone Handling
- Store all datetimes in UTC in the DB (already the default ✅)
- Convert to clinic timezone for display — use `Carbon::setTimezone($clinicTimezone)`
- Let clinic set their timezone in settings

### 🔵 Multi-Currency
- Store `currency_code` and `currency_symbol` in clinic settings
- All monetary values stored as integers (cents/piastres) in DB
- Format on output using clinic currency setting

---

## 15. Monitoring & Observability

### 🟡 Error Tracking
- Integrate Sentry (`sentry/sentry-laravel`)
- Catches unhandled exceptions in production
- Alerts on new error types
- Free tier is sufficient for a single clinic deployment

### 🟡 Uptime Monitoring
- Use UptimeRobot (free) or BetterUptime to monitor `/api/health`
- Alert via email/SMS if site goes down
- Monitor queue worker health separately

### 🔵 Log Management
- Ship logs to a centralized service (Papertrail, Logtail — both have free tiers)
- Structured logging: use `Log::info()` with context arrays not string interpolation
- Log rotation: configure `daily` channel in `config/logging.php`

### 🔵 Performance Monitoring
- Laravel Telescope in development (already installable)
- In production: use `spatie/laravel-query-detector` to catch N+1 queries before they ship

---

## Priority Order (what to build next)

### Phase 1 — Production-ready core (do before first deployment)
1. Fix `email_verified_at` fillable + seeder split
2. Global JSON error handler
3. Mail infrastructure + transactional emails
4. Queue setup (database driver to start)
5. Invoice PDF + Prescription PDF
6. Scheduled tasks (appointment reminders, recall reminders, low stock alerts)
7. Clinic settings (name, logo, timezone, currency)
8. Nginx + Supervisor + deployment script

### Phase 2 — Commercial viability (do before charging clients)
1. 2FA / TOTP
2. SMS notifications (appointment reminders at minimum)
3. WhatsApp notifications
4. Working hours + holiday management
5. Session/token management endpoints
6. Active session list
7. All remaining web UI screens
8. Automated backups

### Phase 3 — Professional polish
1. Patient portal
2. Webhook system
3. Arabic/RTL localization
4. Report exports (PDF/Excel)
5. Docker + CI/CD
6. Sentry + uptime monitoring
7. Full test coverage audit
8. API changelog + versioned Swagger UI

---

*Last updated: 2026-08-23 | Crystalline Dental PMS v2.0*
