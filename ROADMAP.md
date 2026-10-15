# Crystalline Dental PMS — Implementation Roadmap

> **Option 1 — Per-clinic deployment model.**
> Each clinic gets their own server instance and database.

---

## Legend
- ✅ Implemented and integrated
- 🟡 Missing — planned next
- 🔵 Enhancement — future polish

---

## 1. Authentication & Security ✅ Complete

- ✅ Login / logout / logout-all (Sanctum tokens)
- ✅ Register (owner-bootstrap + owner-creates-staff)
- ✅ Role-scoped token abilities (owner: `*`, provider/receptionist/assistant: scoped)
- ✅ Role-scoped token expiry (owner: no expiry, provider: 30d, receptionist: 14d, assistant: 7d)
- ✅ Email verification — signed URL, 60min expiry, resend endpoint
- ✅ 2FA / TOTP — enable, confirm, disable, recovery codes (8), login challenge flow
- ✅ Active session / token management — list + revoke individual tokens
- ✅ Password history — last 5 hashes enforced on reset + change
- ✅ `password_changed_at` + configurable forced expiry (`AUTH_PASSWORD_EXPIRY_DAYS`)
- ✅ IP allowlist middleware — exact IPs + CIDR ranges (`IP_ALLOWLIST`)
- ✅ `ForcePasswordChange` middleware on all authenticated routes
- ✅ Global JSON error handling — 401/403/404/405/422/429/500
- ✅ Rate limiting per role + strict throttle on auth endpoints
- ✅ 28 authorization policies across all resources
- ✅ Pessimistic locking on appointment booking
- ✅ Full audit logging via AuditObserver (14 models)

### 🟡 Missing
- Account lockout after N failed login attempts
- Trusted device persistence (skip 2FA for known devices)

---

## 2. Notifications ✅ Complete

- ✅ Mail infrastructure — `BaseMail`, queue, 3 retries, exponential backoff, branded templates
- ✅ In-app notifications — database channel, CRUD endpoints
- ✅ Laravel Reverb — WebSocket broadcasting, private channel `App.Models.User.{id}`
- ✅ Events — `AppointmentBooked`, `AppointmentStatusChanged`, `AiResultReady`, `RecallOverdue`, `LowStockAlert`
- ✅ Listeners — all queued, all dispatch to registered webhooks via `WebhookDispatcher`
- ✅ 11 transactional email templates — Welcome, Appointment Confirmation, Appointment Reminder, Appointment Cancelled, Invoice Finalized (with PDF), Payment Receipt, Recall Reminder, Low Stock Alert, AI Result Pending, Password Changed, New Device Login
- ✅ OTP email for patient portal login

### 🟡 Missing
- SMS notifications (Twilio or local gateway)
- WhatsApp notifications (Meta Business API)
- Notification preferences per patient (channel + type opt-in/out)

---

## 3. Clinic Settings ✅ Complete

- ✅ Clinic settings API — `GET /settings`, `PATCH /settings` (owner only)
- ✅ 27 structured columns: identity, locale, branding, billing, appointments, reminders, security
- ✅ `ClinicSetting::instance()` singleton — used throughout app
- ✅ Working hours — `GET/PUT /working-hours`
- ✅ Clinic closures — `GET/POST /closures`, `DELETE /closures/{id}`
- ✅ Default settings seeded from env values in `CatalogueSeeder`

---

## 4. PDF Generation ✅ Complete

- ✅ Invoice PDF — letterhead, items, payment history, balance due
- ✅ Prescription PDF — Rx symbol, medications, signatures
- ✅ Treatment Plan PDF — procedure table, priority colours, history timeline
- ✅ Referral Letter PDF — specialist letter, urgency badge, clinical notes
- ✅ Report PDF — all 5 reports via `?format=pdf` — A4 landscape
- ✅ Report Excel — all 5 reports via `?format=xlsx` — PhpSpreadsheet

---

## 5. Patient Portal ✅ Complete

- ✅ Passwordless OTP login — 6-digit code, 10-minute expiry, sent via email
- ✅ Patient-scoped Sanctum tokens — `portal:read` ability only
- ✅ `POST /portal/auth/request-otp` — always 200 (prevents enumeration)
- ✅ `POST /portal/auth/verify-otp` — issues 30-day portal token
- ✅ `GET /portal/me` — profile + allergies + conditions
- ✅ `GET /portal/appointments` — all + upcoming shortlist
- ✅ `GET /portal/invoices` — finalized invoices
- ✅ `GET /portal/invoices/{id}/pdf` — own invoice PDF
- ✅ `GET /portal/prescriptions` — prescriptions with items

### 🟡 Missing
- Online consent signing via portal
- Appointment request flow
- Portal OTP via SMS

---

## 6. Scheduling & Automation ✅ Complete

- ✅ `PruneExpiredTokensJob` — daily 03:00
- ✅ `SendRecallRemindersJob` — daily 07:00
- ✅ `SendAppointmentRemindersJob` — daily 08:00
- ✅ `CheckLowStockJob` — daily 08:30
- ✅ `GenerateWeeklyReportJob` — Monday 07:00
- ✅ `ArchiveOldAuditLogsJob` — monthly 1st 02:00
- ✅ `ProcessAiAnalysisJob` — async AI calls off HTTP thread
- ✅ `ImportPatientsJob` — bulk CSV patient import
- ✅ Supervisor config — queue worker + mail queue + scheduler

---

## 7. File Storage ✅ Core complete

- ✅ Patient medical document upload + auth-protected download

### 🟡 Missing
- Production storage driver (S3 / MinIO)

---

## 8. API Completions & Polish ✅ Complete

- ✅ `X-API-Version` + `X-Request-ID` headers on every response
- ✅ `GET /api/v1/health` — DB + cache + queue + storage status
- ✅ `GET /api/docs` — Swagger UI (public, branded)
- ✅ `?sort_by=` + `?sort_dir=` on collection endpoints
- ✅ `SetClinicTimezone` middleware — timezone-aware output
- ✅ Arabic + English localization — `lang/en/` + `lang/ar/`
- ✅ Webhook system — 8 events, HMAC-signed, delivery history
- ✅ OpenAPI spec v3.0.0 — 7,729+ lines, all schemas documented

---

## 9. Referrals ✅ Complete

- ✅ `referrals` table — urgency levels (routine/urgent/emergency)
- ✅ `GET/POST /referrals`, `GET/DELETE /referrals/{id}`
- ✅ `GET /referrals/{id}/pdf` — specialist letter

---

## 10. Seeder & Factories ✅ Complete

- ✅ `CatalogueSeeder` — production-safe
- ✅ `DevelopmentSeeder` — full realistic dataset
- ✅ All factory column name fixes
- ✅ All factory states

---

## 11. Testing ✅ Complete

**30 test suites** (22 pre-existing + 8 new):
- `EmailVerificationTest` — 6 cases
- `TwoFactorTest` — 7 cases
- `PasswordHistoryTest` — 5 cases
- `TokenManagementTest` — 4 cases
- `NotificationTest` — 5 cases
- `WebhookTest` — 6 cases
- `PatientPortalTest` — 7 cases
- `MailDispatchTest` — 5 cases

### 🟡 Missing
- Policy test coverage per role
- Webhook delivery integration tests

---

## 12. Infrastructure ✅ Complete

- ✅ `.env.example` — fully documented
- ✅ Supervisor config
- ✅ Sentry integration (`SENTRY_LARAVEL_DSN`)
- ✅ `phpoffice/phpspreadsheet` for Excel

### 🟡 Missing
- Production storage driver (S3 / MinIO)
- Deployment script
- Automated backups (`spatie/laravel-backup`)
- Docker + CI/CD

---

## 13. Compliance ✅ Complete

- ✅ `GET /patients/{id}/export` — full JSON export
- ✅ `DELETE /patients/{id}/purge` — two-step hard delete
- ✅ `scheduled_deletion_at`, `purge_requested_at`, `purge_requested_by`
- ✅ Purge audit trail in `audit_logs`

---

## 14. Localization ✅ Complete

- ✅ `lang/en/` — auth, passwords, pagination, validation
- ✅ `lang/ar/` — full Arabic translations with attribute names
- ✅ `SetClinicTimezone` middleware — timezone + locale per request

### 🟡 Missing
- RTL CSS for Arabic UI
- Hijri calendar option

---

## 15. Monitoring ✅ Complete

- ✅ Sentry — all unhandled exceptions reported
- ✅ `GET /api/v1/health` — 200/503 with subsystem detail
- ✅ `X-Request-ID` tracing header

### 🟡 Missing
- Uptime monitoring
- Centralized log shipping

---

## What's Next

### Phase 1 — Production deployment
1. S3 / MinIO storage driver
2. Deployment script
3. Automated backups

### Phase 2 — Commercial features
1. SMS / WhatsApp notifications
2. Patient portal consent signing + appointment requests
3. Arabic RTL UI
4. Docker + CI/CD

### Phase 3 — Polish
1. Account lockout
2. Trusted device (skip 2FA)
3. Policy + seeder test coverage

---

*Last updated: 2026-10-14 · Crystalline Dental PMS v3.0 · API fully complete*
