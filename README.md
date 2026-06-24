<p align="center">
  <img src="https://raw.githubusercontent.com/Haider-Haj-Ahmed/dental-clinic/main/.github/social-preview.png" alt="Dental Clinic PMS" width="100%"/>
</p>

<h1 align="center">dental-clinic</h1>

<p align="center">
  A production-ready <strong>Laravel 13 REST API</strong> for full dental practice management —<br/>
  from patient records and clinical workflows to AI-assisted diagnosis and prescription suggestions.
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white"/></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white"/></a>
  <a href="https://laravel.com/docs/sanctum"><img src="https://img.shields.io/badge/Sanctum-4.x-FF2D20?style=flat-square&logo=laravel&logoColor=white"/></a>
  <a href="https://www.mysql.com"><img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white"/></a>
  <a href="https://aistudio.google.com"><img src="https://img.shields.io/badge/Gemini-Flash-4285F4?style=flat-square&logo=google&logoColor=white"/></a>
  <img src="https://img.shields.io/badge/Tests-21%20suites-22C55E?style=flat-square"/>
  <img src="https://img.shields.io/badge/DB%20Tables-46-0EA5E9?style=flat-square"/>
  <img src="https://img.shields.io/badge/License-MIT-6366F1?style=flat-square"/>
</p>

---

## Overview

**dental-clinic** is a complete backend API for managing a dental practice. It covers every layer of clinic operations — patient intake, clinical charting, billing, inventory, and AI-powered clinical decision support — all secured with role-based token authentication.

Built with clean architecture: thin controllers, Form Request validation, API Resources, model observers for audit logging, and a policy-per-model authorization layer.

---

## Features

### 🦷 Patient Management
- Full patient CRUD with soft-archive (no hard deletes)
- Structured medical history: contacts, allergies, conditions, medications, consents
- Cross-clinic medical cases — import records from previous dentists with clinic/dentist attribution
- Document uploads: X-rays, panoramics, consent forms, old treatment records
- Unified chronological **timeline** per patient across all clinical events

### 📅 Scheduling
- Appointments with conflict detection and race-condition-safe booking
- Appointment types, operatories, and schedule blocks
- Recall management with due-date tracking and reminder dispatch

### 🩺 Clinical
- Encounter records with SOAP notes and locking after sign-off
- Odontogram entries (FDI tooth notation)
- Periodontal exams with per-site probing depth measurements
- Treatment plans with present/accept/reject workflow
- Prescriptions and lab cases

### 💰 Billing
- Invoice lifecycle: draft → finalized → paid/partial/void
- Auto-recalculating totals on item changes
- Payment recording with automatic invoice status promotion
- Payment plans with auto-generated installment schedules
- Per-patient ledger with billed/paid/outstanding summary

### 📦 Inventory
- Stock items with reorder-level tracking
- Stock movement audit trail (in/out/adjustment/expired)
- Purchase orders with receive workflow that auto-increments stock
- Low-stock alerts endpoint

### 🤖 AI Integration (Gemini Flash)
- **X-ray analysis** — upload a dental image, receive structured findings with FDI tooth notation, confidence scores, and urgency levels
- **SOAP note suggestions** — AI drafts a full SOAP note from partial encounter notes, flagging drug interactions with the patient's known medications
- **Prescription suggestions** — AI recommends drugs based on diagnosis, allergies, and current medications; provider must explicitly accept before any prescription is written
- **Periodontal risk scoring** — AI analyses perio measurements and returns risk level (low/moderate/high/severe), teeth of concern, recommended recall interval, and prognosis
- **Recall prioritisation** — AI ranks pending recalls by urgency for the receptionist's outreach queue

> All AI results are stored with model version and input summary for audit. Nothing is auto-applied — provider review is always required before clinical data is written.

### 📊 Reporting & Dashboard
- KPI dashboard: today's appointments, monthly revenue, active patients, overdue recalls, low-stock count
- Reports: appointment breakdown, production by provider, collections vs billed, recall conversion rate, inventory stock value

### 🔒 Security & Hardening
- Sanctum token auth with role-scoped abilities (`patients:read`, `billing:write`, `clinical:write`, etc.)
- Per-role rate limiting: owner unlimited · receptionist 200/min · provider 120/min · assistant 60/min
- Full audit logging on 14 models via a single `AuditObserver`
- Race-condition-safe appointment booking via `DB::transaction` + `lockForUpdate`

---

## Roles

| Role | Access |
|---|---|
| `owner` | Full access — bypasses all policy checks |
| `receptionist` | Patients, appointments, billing, recalls, inventory |
| `provider` | Own appointments, all clinical records, AI endpoints, patient read |
| `assistant` | Patient read, appointment read, document upload |

---

## Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 |
| Auth | Laravel Sanctum 4 — scoped token abilities per role |
| Database | MySQL 8 — 46 tables, FK-safe migration order |
| AI | Google Gemini Flash (vision + text) |
| File storage | Local disk / S3-compatible via `FileStorageService` |
| Testing | PHPUnit 12 — 21 feature test suites |
| Audit | Model observers → `audit_logs` table |

---

## Getting Started

```bash
git clone https://github.com/Haider-Haj-Ahmed/dental-clinic.git
cd dental-clinic
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database and AI key in `.env`:

```env
DB_DATABASE=dental_clinic
DB_USERNAME=root
DB_PASSWORD=

AI_PROVIDER=gemini
AI_API_KEY=your_gemini_key_here   # free at aistudio.google.com
AI_VISION_MODEL=gemini-2.0-flash
AI_TEXT_MODEL=gemini-2.0-flash
```

Run migrations and seed default data:

```bash
php artisan migrate --seed
```

**Default owner credentials:**
```
email:    owner@clinic.local
password: password
```

---

## API Reference

All routes are prefixed with `/api/v1` and require a Sanctum bearer token except login.

```
# Auth
POST   /auth/login
GET    /auth/me
POST   /auth/logout

# Patients
GET    /patients
POST   /patients
GET    /patients/{id}/timeline
GET    /patients/{id}/medical-cases
POST   /patients/{id}/medical-cases
GET    /patients/{id}/documents
GET    /patients/{id}/ledger
GET    /patients/{id}/ai-insights

# Appointments & scheduling
GET    /appointments
POST   /appointments
GET    /recalls/due
POST   /recalls/{id}/send-reminder

# Clinical
POST   /encounters/{id}/suggest-soap
POST   /ai-results/{id}/apply-soap
POST   /perio-exams/{id}/risk-score

# Billing
GET    /invoices
POST   /invoices/{id}/finalize
POST   /invoices/{id}/void
GET    /payments

# Inventory
GET    /inventory-items/low-stock
POST   /inventory-items/{id}/adjust-stock
POST   /purchase-orders/{id}/receive

# AI
POST   /patients/{id}/documents/{id}/analyze
POST   /patients/{id}/prescription-suggestions
POST   /ai-results/{id}/create-prescription
POST   /recalls/ai-prioritize

# Dashboard & reports
GET    /dashboard/kpis
GET    /reports/production
GET    /reports/collections
GET    /reports/recall-performance

# System
GET    /audit-logs
```

---

## Running Tests

```bash
php artisan test
```

21 feature test suites covering auth, patient workflows, medical history, billing state machine, inventory, AI analysis, audit logging, and token ability enforcement.

---

## Project Stats

| Metric | Count |
|---|---|
| Database tables | 46 |
| Controllers | 31 |
| Policies | 28 |
| Feature test suites | 21 |
| AI endpoints | 5 |
| Migrations | 18 |

---

## License

MIT

---

<p align="center">
  Built by <a href="https://github.com/Haider-Haj-Ahmed">Haider Al-Haj Ahmed</a>
</p>
