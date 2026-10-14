# Crystalline Dental PMS — SaaS Completion Roadmap

> **Option 1 — Per-clinic deployment model.**
> Each clinic gets their own server instance and database.
> This document tracks what has been built and what remains.

---

## Legend
- ✅ Implemented
- 🔴 Missing — critical (blocks production)
- 🟡 Missing — important (needed for commercial viability)
- 🔵 Missing — enhancement (professional polish)

---

## 1. Authentication & Security

### ✅ Implemented
- Login / logout / logout-all (Sanctum tokens)
- Register (owner-bootstrap + owner-creates-staff)
- Role-scoped token abilities (owner, provider, receptionist, assistant)
- 28 authorization policies
- Throttle on auth routes (10/min per IP)
- Pessimistic locking on appointment booking
- Full audit logging via AuditObserver (14 models)
- **Email verification** — `VerifyEmailMail`, signed URL, 60-minute expiry, resend endpoint
- **2FA / TOTP** — enable, confirm, disable, recovery codes, regenerate, login challenge flow
- **Active session / token management** — `GET /auth/tokens`, `DELETE /auth/tokens/{id}`
- **Password history** — last 5 hashes stored, enforced on reset + change password
- **Change password endpoint** — `POST /auth/change-password` (keeps current session, revokes others)
- `email_verified_at` in `User::$fillable`
- Global JSON error handler — 401/403/404/405/422/429/500 all return JSON

### 🟡 Missing
- `password_changed_at` field + force password change after X days
- Sanctum token expiry configurable per role (currently fixed 7 days)
- IP allowlist middleware per clinic
- Account lockout after N failed attempts

---

## 2. Notifications

### ✅ Implemented
- **Mail infrastructure** — `BaseMail` (abstract, queued, 3 retries, branded), `SendMailJob`
- **Email templates** — `VerifyEmailMail`, `TwoFactorEnabledMail` (enable + disable security alerts)
- **Queue setup** — `QUEUE_CONNECTION=database`, jobs table, Supervisor config (`config/supervisor/dental-clinic.conf`)
- **In-app notifications** — `InAppNotification` (database channel), `GET /notifications`, mark read, mark all read, delete
- **Real-time broadcasting** — Laravel Reverb, private channel `App.Models.User.{id}`, `routes/channels.php`
- **Events** — `AppointmentBooked`, `AppointmentStatusChanged`, `AiResultReady`, `RecallOverdue`, `LowStockAlert`
- **Listeners** — `NotifyOnAppointmentBooked`, `NotifyOnAppointmentStatusChanged`, `NotifyOnAiResultReady`, `NotifyOnRecallOverdue`, `NotifyOnLowStock` — all queued, all also call `WebhookDispatcher`

### 🔴 Missing email templates
| Trigger | Recipient |
|---|---|
| New staff account created | New staff member (welcome email) |
| Appointment confirmation | Patient |
| Appointment reminder 24h | Patient |
| Appointment reminder 2h | Patient |
| Appointment cancelled | Patient |
| Invoice finalized | Patient (with PDF attachment) |
| Payment received | Patient (receipt) |
| Recall due | Patient |
| Low stock alert | Owner |
| AI result pending review | Provider |
| Password changed | User (security alert) |
| New device login | User (security alert) |

### 🟡 Missing
- SMS notifications (Twilio or local gateway)
- WhatsApp notifications (Meta Business API)
- Notification preferences per patient (channel + type)
- Notification templates (customizable by owner)

---

## 3. Clinic Settings

### ✅ Implemented
- **Clinic settings API** — `GET /settings`, `PATCH /settings` (owner only)
- 27 structured columns: identity, locale, branding, billing, appointments, reminders, security
- `ClinicSetting::instance()` singleton used throughout app (PDF, emails, jobs)
- **Working hours** — `GET /working-hours`, `PUT /working-hours`, seeded Mon–Sat 09:00–18:00
- **Clinic closures** — `GET /closures`, `POST /closures`, `DELETE /closures/{id}`
- Default settings row seeded from env values in `CatalogueSeeder`

### 🟡 Missing
- `POST /settings/logo` — clinic logo upload
- Branding letterhead customization

---

## 4. PDF Generation

### ✅ Implemented
- **Invoice PDF** — `GET /invoices/{invoice}/pdf` — A4, letterhead, itemized table, payment history, balance due
- **Prescription PDF** — `GET /prescriptions/{prescription}/pdf` — A4, Rx symbol, medication list, signature lines, marks `is_printed=true`
- Both pull branding from `ClinicSetting::instance()`
- Package: `barryvdh/laravel-dompdf ^3.0`

### 🟡 Missing
- Treatment plan PDF — `GET /treatment-plans/{plan}/pdf`
- Report PDF / Excel export (`?format=pdf`, `?format=xlsx`)
- Referral letter PDF

---

## 5. Patient Portal

### 🟡 Missing (entirely)
- Patient authentication (separate guard, OTP/magic link)
- `/portal/me`, `/portal/appointments`, `/portal/invoices`, `/portal/prescriptions`
- Online consent signing
- Appointment request flow

---

## 6. Scheduling & Automation

### ✅ Implemented
- **Scheduler** — `routes/console.php` with 5 scheduled jobs
- `PruneExpiredTokensJob` — daily 03:00
- `SendRecallRemindersJob` — daily 07:00, fires `RecallOverdue` event
- `SendAppointmentRemindersJob` — daily 08:00, marks `reminder_sent_at`
- `CheckLowStockJob` — daily 08:30, fires `LowStockAlert` event
- `GenerateWeeklyReportJob` — Monday 07:00, in-app report to owner
- Supervisor config for queue worker + scheduler process

### 🟡 Missing
- `ArchiveOldAuditLogsJob` — monthly, move logs older than 1 year
- `ProcessAiAnalysisJob` — move AI calls off HTTP request (currently synchronous)
- `ImportPatientsJob` — bulk CSV patient import
- `GeneratePdfJob` — async PDF for large reports

---

## 7. File Storage

### ✅ Implemented
- `FileStorageService` — document upload
- Patient medical document upload + download (auth-protected)

### 🔴 Missing
- Production storage driver (S3 / MinIO) — currently `local` disk
- File size limits per type (X-ray: 20MB, document: 10MB, logo: 2MB)
- MIME type validation via `finfo`

---

## 8. API Completions & Polish

### ✅ Implemented
- Global JSON error handling (401/403/404/405/422/429/500)
- `/api/v1/` versioning
- **Webhook system** — register, list, toggle, delete, delivery history, ping
- HMAC-SHA256 signed payloads, 3 retries with exponential backoff
- 8 supported webhook events
- Health check at `/up` (Laravel built-in)

### 🟡 Missing
- `X-API-Version` response header on every response
- `X-Request-ID` tracing header
- Enhanced health check at `/api/health` (DB + queue + storage status)
- Swagger UI at `/api/docs`
- Pagination consistency (`?search=`, `?sort_by=`, `?sort_dir=`) across all collection endpoints
- `CHANGELOG.md`

---

## 9. Web UI — Remaining Screens

> API-only sessions are excluded from this section per current scope.

### 🔴 Missing
- Dashboard (KPI cards, today's schedule, activity feed)
- Appointments screen (calendar, booking modal, drag-reschedule)
- Patients screen (list + full profile with all tabs)
- Encounters / clinical screens (SOAP editor, odontogram, perio grid)
- Billing screens (invoice list, payment recording, payment plans)
- Reports screen (charts, date range, export)
- Settings screen (clinic profile, working hours, staff, 2FA wizard)
- Recalls, inventory, prescriptions, audit logs, providers (all remaining screens)

---

## 10. Seeder & Factory Corrections

### ✅ Implemented
- `email_verified_at` in `User::$fillable`
- Seeder split: `CatalogueSeeder` (production-safe) + `DevelopmentSeeder` (dev only)
- `InvoiceFactory` lazy state fix (no more `->create()` inside state closures)
- All factory states: `UserFactory` (owner/provider/receptionist/assistant), `AppointmentFactory` (states), `EncounterFactory` (locked), `AiAnalysisResultFactory` (all types + statuses)
- Column name fixes: `condition`, `drug_name`, `current_stock`, `issued_at`, `qty`
- Seeder runs clean with zero exceptions

### 🔵 Missing
- `PatientFactory::withAllergies()`, `::withActiveMedications()`, `::archived()`
- `InvoiceFactory::paid()`, `::overdue()`

---

## 11. Testing

### Already built ✅
- 21 PHPUnit feature test suites (pre-existing)

### 🔴 Missing
- Auth test coverage for new flows (2FA, verification, password history, change-password)
- Notification tests (`Mail::fake()`, assert correct mailable dispatched per trigger)
- Webhook delivery tests

### 🟡 Missing
- Policy test coverage per role (receptionist blocked from clinical write, etc.)
- Factory / seeder tests (assert clean run on fresh DB)

---

## 12. Infrastructure & Deployment

### ✅ Implemented
- `.env.example` — fully documented with all variables
- `config/supervisor/dental-clinic.conf` — queue worker (default + mail queues) + scheduler
- Nginx config documented in roadmap
- Queue connection configured

### 🔴 Missing
- Production storage driver (S3 / MinIO)
- Deployment script (`git pull`, `composer install --no-dev`, cache commands, `queue:restart`)

### 🟡 Missing
- Automated backups (`spatie/laravel-backup`)
- Docker + `docker-compose.yml` for local dev

### 🔵 Missing
- CI/CD pipeline (GitHub Actions)

---

## 13. Compliance & Data Protection

### 🟡 Missing
- `GET /patients/{patient}/export` — full record as JSON or PDF
- `DELETE /patients/{patient}/purge` — hard delete with owner confirmation
- `scheduled_deletion_at` on archived patients

### 🔵 Missing
- Digital consent versioning + re-consent on form change
- Terms of Service + Privacy Policy acceptance tracking

---

## 14. Localization

### 🟡 Missing
- `lang/ar/` translation files (Arabic UI strings)
- RTL CSS (`dir="rtl"`)
- Hijri calendar option
- Timezone conversion on API output (currently stored UTC, displayed UTC)

---

## 15. Monitoring & Observability

### 🟡 Missing
- Sentry integration (`sentry/sentry-laravel`)
- Uptime monitoring (UptimeRobot or BetterUptime watching `/api/health`)

### 🔵 Missing
- Centralized log shipping (Papertrail / Logtail)
- N+1 query detection (`spatie/laravel-query-detector`)

---

## Priority Order — What to build next

### Phase 1 — Production-ready (remaining)
1. Missing email templates (13 transactional emails)
2. Production storage driver (S3 / MinIO)
3. Deployment script
4. Enhanced health check endpoint

### Phase 2 — Commercial viability
1. SMS notifications
2. WhatsApp notifications
3. Treatment plan + report PDFs
4. Patient portal
5. All remaining web UI screens
6. Automated backups

### Phase 3 — Professional polish
1. Arabic / RTL localization
2. Report exports (PDF / Excel)
3. Docker + CI/CD
4. Sentry + uptime monitoring
5. Full test coverage audit
6. Swagger UI at `/api/docs`
7. `X-API-Version` + `X-Request-ID` headers

---

*Last updated: 2026-10-05 | Crystalline Dental PMS v3.0 | Steps 1–12 complete*
