# Dental Clinic PMS — Architecture & Implementation Plan

**Stack:** Laravel 12 · PHP 8.2+ · Sanctum 4 · MySQL · S3-compatible storage  
**Base repo:** `Haider-Haj-Ahmed/dental-clinic` (keep Phase 1 work, rebuild around it)

---

## What we keep from the existing code

| File | Decision | Reason |
|---|---|---|
| `User` model + migration | **Keep, extend** | Role constants, `isOwner/isReceptionist/isProvider` helpers are solid |
| `Provider` model + migration | **Keep, extend** | Add `operatory_id`, `bio`, `signature_path` columns |
| `Patient` model + migration | **Keep, extend** | Add DOB index, drop `medical_alerts` (moves to structured `conditions` table) |
| `Appointment` model + migration | **Keep, extend** | Add `appointment_type_id`, `operatory_id`, `color`, `reminder_sent_at` |
| `AuthController` | **Keep, extend** | Add forgot-password + push-token endpoints |
| `AppServiceProvider` + `Gate::before` owner bypass | **Keep** | Correct pattern |
| `UpdateProviderRequest` | **Keep** | Already uses `Rule::unique()->ignore()` correctly |
| All existing Form Requests | **Keep as base** | Extend with new fields |
| Feature tests (Auth, Patient, Appointment) | **Keep, grow** | Good foundation |

**What we discard / rewrite:**
- The flat `medical_alerts` text column on `patients` → replaced by relational sub-tables
- The missing token expiry → fixed in `config/sanctum.php` immediately
- The unguarded `per_page` → fixed in a base controller trait
- The soft-delete patient bookable bug → fixed in `StoreAppointmentRequest`

---

## Roles & permissions

| Role | What they can do |
|---|---|
| `owner` | Everything. Gate bypass already coded. |
| `receptionist` | Patients CRUD, appointments CRUD, recalls, billing read, communication logs |
| `provider` (dentist) | Read patients + their own appointments, write clinical records (encounters, notes, odontogram, perio, treatment plans, prescriptions), upload medical documents for patients they treated |
| `assistant` *(new)* | Read-only on patients/appointments, upload X-rays/docs on behalf of a provider |

Token abilities issued at login map to these roles so mobile clients carry minimal scope.

---

## Database — full schema

### Keeping & extending

```
users                   (existing + email_verified_at already there)
providers               (existing + operatory_id FK, bio text, signature_path)
patients                (existing — drop medical_alerts text, keep everything else)
appointments            (existing + appointment_type_id FK, operatory_id FK,
                         color varchar(7), reminder_sent_at timestamp)
personal_access_tokens  (existing)
```

### New lookup / config tables

```
operatories             id, name, color, is_active
appointment_types       id, name, default_duration_minutes, color, is_active
procedure_codes         id, code, description, fee_default, is_active
diagnosis_codes         id, code, system (ICD-10/SNOMED), description
recall_types            id, name, interval_months, default_message_template
notification_templates  id, type (email/sms/whatsapp), event, subject, body
payment_methods         id, name, is_active
clinic_settings         key, value (JSON blob store)
```

### Patient sub-tables (structured medical history)

```
patient_contacts        id, patient_id, label, name, phone, relationship, is_emergency
patient_allergies       id, patient_id, allergen, reaction, severity, noted_by (provider_id), noted_at
patient_conditions      id, patient_id, condition, icd_code, status(active/resolved), onset_date,
                        noted_by, noted_at, notes
patient_medications     id, patient_id, drug_name, dose, frequency, start_date, end_date,
                        prescribed_by (provider_id), notes
patient_consents        id, patient_id, consent_type, signed_at, signed_by_patient (bool),
                        witness_provider_id, file_id
```

### Medical history & documents (your new requirement)

This is the heart of the "know the full patient history" feature.

```
patient_medical_cases
    id
    patient_id          FK → patients
    provider_id         FK → providers  ← the dentist who recorded/owns this case
    previous_clinic     varchar(255) nullable  ← name of external clinic if case is imported
    previous_dentist    varchar(255) nullable  ← name of external dentist
    case_date           date
    case_type           enum(examination, extraction, filling, root_canal, crown,
                             implant, orthodontics, periodontal, surgery,
                             consultation, other)
    chief_complaint     varchar(500)
    diagnosis           text
    treatment_performed text
    outcome             text nullable
    is_external         boolean default false  ← true = imported from another clinic
    notes               text nullable
    created_by          FK → users
    created_at / updated_at

patient_medical_documents
    id
    patient_id          FK → patients
    medical_case_id     FK → patient_medical_cases nullable  ← link doc to a case
    provider_id         FK → providers nullable  ← who uploaded it
    document_type       enum(xray, panoramic, cephalometric, intraoral_photo,
                             lab_result, referral_letter, consent_form,
                             old_treatment_record, prescription_scan, other)
    title               varchar(255)
    description         text nullable
    file_path           varchar(500)   ← stored on S3/local disk
    file_size_kb        integer
    mime_type           varchar(100)
    taken_at            date nullable  ← when the X-ray/photo was taken (may differ from upload)
    external_source     varchar(255) nullable  ← "Dr. Khalil - City Hospital 2021"
    is_visible_to_patient boolean default false
    uploaded_by         FK → users
    created_at / updated_at
```

**Why two tables?** A case is a clinical event (a root canal done in 2019 by Dr. X at Clinic Y). Documents are files that may or may not be tied to a specific case — a patient might bring a 5-year-old panoramic X-ray with no associated case record. Both can exist independently.

### Scheduling extensions

```
schedule_blocks         id, provider_id, operatory_id nullable, start_at, end_at,
                        reason, created_by
recalls                 id, patient_id, recall_type_id, due_date, status(pending/sent/booked/dismissed),
                        last_reminder_sent_at, notes
communication_logs      id, patient_id, channel(email/sms/whatsapp), direction(out/in),
                        subject, body_preview, status, sent_at, provider_id nullable
```

### Clinical records

```
encounters              id, appointment_id FK (nullable), patient_id, provider_id,
                        encounter_date, subjective, objective, assessment, plan (SOAP),
                        is_locked boolean, locked_at, locked_by

odontogram_entries      id, encounter_id nullable, patient_id, provider_id,
                        tooth_number (FDI notation 11-48), surface(M/D/O/B/L/all),
                        entry_type(condition/procedure), code varchar(20),
                        color_hex, notes, recorded_at

perio_exams             id, patient_id, provider_id, exam_date, notes
perio_measures          id, perio_exam_id, tooth_number, site(MB/B/DB/ML/L/DL),
                        probing_depth, recession, bleeding_on_probe(bool),
                        furcation(0-3), mobility(0-3), suppuration(bool)

treatment_plans         id, patient_id, provider_id, title, status(draft/presented/accepted/rejected),
                        total_fee, notes, presented_at, accepted_at, rejected_at, created_by
treatment_plan_items    id, treatment_plan_id, procedure_code_id, tooth_number nullable,
                        surface nullable, description, fee, sort_order, status(pending/completed/cancelled)

prescriptions           id, patient_id, provider_id, encounter_id nullable,
                        issued_at, notes, is_printed, printed_at
prescription_items      id, prescription_id, drug_name, dose, frequency, duration, instructions, quantity

lab_cases               id, patient_id, provider_id, lab_name, case_type,
                        sent_at, due_at, received_at, cost, notes, status
```

### Billing

```
invoices                id, patient_id, appointment_id nullable, provider_id nullable,
                        issued_at, due_at, status(draft/finalized/paid/void/partial),
                        subtotal, discount, tax, total, notes, finalized_by, voided_by
invoice_items           id, invoice_id, description, procedure_code_id nullable,
                        tooth_number nullable, qty, unit_price, total
payments                id, invoice_id, patient_id, amount, payment_method_id,
                        paid_at, reference, notes, recorded_by
payment_plans           id, patient_id, invoice_id nullable, total_amount, installments,
                        start_date, notes, status
payment_plan_items      id, payment_plan_id, due_date, amount, paid_at, payment_id nullable
```

### Inventory

```
inventory_items         id, name, sku, category, unit, current_stock, reorder_level,
                        unit_cost, supplier_id nullable, is_active
suppliers               id, name, contact_name, phone, email, address, notes
stock_movements         id, inventory_item_id, movement_type(in/out/adjustment/expired),
                        quantity, reason, reference, performed_by, performed_at
purchase_orders         id, supplier_id, status(draft/sent/received/cancelled),
                        ordered_at, expected_at, received_at, notes, created_by
purchase_order_items    id, purchase_order_id, inventory_item_id, quantity_ordered,
                        quantity_received, unit_cost
```

### System

```
audit_logs              id, user_id, action, model_type, model_id, old_values(JSON),
                        new_values(JSON), ip_address, user_agent, created_at
files                   id, disk, path, original_name, mime_type, size_kb, uploaded_by, created_at
```

---

## Routes blueprint — `routes/api.php`

All routes under `/api/v1` behind `auth:sanctum` except login.

### Auth & devices
```
POST   /auth/login
POST   /auth/logout
POST   /auth/logout-all
GET    /auth/me
POST   /auth/password/forgot
POST   /auth/password/reset
POST   /auth/password/change
POST   /auth/tokens                      create named device token
DELETE /auth/tokens/{tokenId}
POST   /devices/push-tokens
DELETE /devices/push-tokens/{id}
```

### Staff & config (owner only)
```
apiResource /users
apiResource /roles
apiResource /operatories
apiResource /appointment-types
apiResource /procedure-codes
apiResource /diagnosis-codes
apiResource /recall-types
apiResource /notification-templates
apiResource /payment-methods
GET|PUT     /clinic-settings
```

### Providers
```
apiResource /providers
GET    /providers/{id}/appointments
GET    /providers/{id}/schedule          availability view
```

### Patients
```
apiResource /patients                    DELETE = soft-archive, not hard delete
POST   /patients/{id}/archive
POST   /patients/{id}/restore
GET    /patients/{id}/timeline           unified chronological feed

# Structured medical history sub-resources
apiResource /patients/{id}/contacts
apiResource /patients/{id}/allergies
apiResource /patients/{id}/conditions
apiResource /patients/{id}/medications
apiResource /patients/{id}/consents

# Medical cases (your requirement — cross-clinic history)
GET    /patients/{id}/medical-cases
POST   /patients/{id}/medical-cases
GET    /patients/{id}/medical-cases/{caseId}
PUT    /patients/{id}/medical-cases/{caseId}
DELETE /patients/{id}/medical-cases/{caseId}

# Medical documents (X-rays, old records, lab results)
GET    /patients/{id}/documents
POST   /patients/{id}/documents          multipart upload
GET    /patients/{id}/documents/{docId}
PUT    /patients/{id}/documents/{docId}  update metadata
DELETE /patients/{id}/documents/{docId}
GET    /patients/{id}/documents/{docId}/download

# Shortcut: all docs attached to a specific case
GET    /patients/{id}/medical-cases/{caseId}/documents
POST   /patients/{id}/medical-cases/{caseId}/documents
```

### Scheduling
```
apiResource /appointments
GET    /appointments/slots               availability search ?provider_id&date&duration
GET    /appointments/asap                waitlist / priority list
POST   /appointments/{id}/confirm
POST   /appointments/{id}/check-in
POST   /appointments/{id}/check-out
POST   /appointments/{id}/reschedule
POST   /appointments/{id}/cancel
POST   /appointments/{id}/no-show
apiResource /schedule-blocks
```

### Recalls & communication
```
apiResource /recalls
GET    /recalls/due
PATCH  /recalls/{id}/status
POST   /recalls/{id}/send-reminder
GET    /communication-logs
```

### Clinical (provider-accessible)
```
apiResource /encounters
apiResource /encounters/{id}/odontogram-entries
apiResource /patients/{id}/odontogram               full mouth view
apiResource /perio-exams
GET|POST   /perio-exams/{id}/measures
apiResource /treatment-plans
POST   /treatment-plans/{id}/present
POST   /treatment-plans/{id}/accept
POST   /treatment-plans/{id}/reject
apiResource /treatment-plans/{id}/items
apiResource /prescriptions
apiResource /lab-cases
```

### Billing
```
apiResource /invoices
POST   /invoices/{id}/finalize
POST   /invoices/{id}/void
POST   /invoices/{id}/send
apiResource /invoices/{id}/items
apiResource /payments
apiResource /payment-plans
GET    /patients/{id}/ledger
```

### Inventory
```
apiResource /inventory-items
POST   /inventory-items/{id}/adjust-stock
GET    /inventory-items/low-stock
apiResource /suppliers
apiResource /purchase-orders
```

### Reports & dashboard
```
GET    /dashboard/kpis
GET    /reports/appointments
GET    /reports/production
GET    /reports/collections
GET    /reports/recall-performance
GET    /reports/inventory
```

### System
```
POST   /files                            generic upload
DELETE /files/{id}
GET    /audit-logs                       owner only
GET    /webhooks/events
apiResource /integrations
```

---

## File & class structure

```
app/
  Models/
    User, Provider, Patient, Appointment          ← existing, extended
    Operatory, AppointmentType, ScheduleBlock
    PatientContact, PatientAllergy, PatientCondition
    PatientMedication, PatientConsent
    PatientMedicalCase, PatientMedicalDocument    ← new core requirement
    Recall, CommunicationLog, RecallType
    Encounter, OdontogramEntry
    PerioExam, PerioMeasure
    TreatmentPlan, TreatmentPlanItem
    ProcedureCode, DiagnosisCode
    Prescription, PrescriptionItem
    LabCase
    Invoice, InvoiceItem, Payment
    PaymentPlan, PaymentPlanItem, PaymentMethod
    InventoryItem, Supplier, StockMovement
    PurchaseOrder, PurchaseOrderItem
    NotificationTemplate, ClinicSetting
    File, AuditLog

  Http/
    Controllers/Api/
      AuthController                             ← extend existing
      UserController
      ProviderController                         ← extend existing
      PatientController                          ← extend existing
      PatientMedicalCaseController               ← new
      PatientMedicalDocumentController           ← new
      AppointmentController                      ← extend existing (fix race + per_page)
      ScheduleBlockController
      RecallController
      CommunicationLogController
      EncounterController
      OdontogramController
      PerioExamController
      TreatmentPlanController
      PrescriptionController
      LabCaseController
      InvoiceController
      PaymentController
      InventoryController
      ReportController
      DashboardController
      FileController
      AuditLogController
      ClinicSettingController

    Requests/                                    ← one Store + one Update per resource
    Resources/                                   ← one Resource class per model
    Middleware/
      EnforceTokenAbilities                      ← wire up ability checks

  Policies/
    PatientPolicy, ProviderPolicy, AppointmentPolicy  ← extend existing
    PatientMedicalCasePolicy                     ← provider writes own cases only
    PatientMedicalDocumentPolicy                 ← provider/assistant upload; owner deletes
    EncounterPolicy, TreatmentPlanPolicy         ← provider-scoped
    InvoicePolicy, PaymentPolicy                 ← owner/receptionist
    InventoryPolicy                              ← owner only

  Services/
    AppointmentSlotService                       ← availability calculation
    RecallReminderService                        ← queued reminder dispatch
    FileStorageService                           ← upload/download/signed-URL wrapper
    AuditService                                 ← model observer → audit_logs
    ReportService                                ← heavy queries

  Observers/
    PatientObserver    ← writes audit_logs on create/update/delete
    AppointmentObserver
    MedicalCaseObserver

  Notifications/
    AppointmentReminder
    RecallDueReminder
    InvoiceSent

database/
  migrations/          ← one file per table, ordered by FK dependency
  factories/           ← one per model, for tests
  seeders/
    DatabaseSeeder     ← existing owner seed + new lookup data
    ProcedureCodeSeeder
    RecallTypeSeeder
```

---

## Phased delivery plan

### Phase 1 — Foundation (what already exists + fixes) ✅ partial

**Goal:** Solid auth + patient + provider + appointment base with all bugs fixed.

Tasks:
1. Fix `config/sanctum.php` → set `'expiration' => 10080` (7 days)
2. Fix `AppointmentController::index()` → clamp `per_page` to 100 max
3. Fix `StoreAppointmentRequest` → soft-delete-aware patient exists rule
4. Fix `assertNoTimeConflict()` → wrap in `DB::transaction()` + `lockForUpdate()`
5. Wire up token ability checks via `EnforceTokenAbilities` middleware
6. Narrow provider update policy (providers may only update `notes` on their appointments)
7. Add `operatories`, `appointment_types` migrations + models + controllers
8. Extend `Appointment` migration + model with `appointment_type_id`, `operatory_id`, `reminder_sent_at`
9. Add `schedule_blocks` table + controller
10. Extend seeder with default operatory + appointment types

**Deliverable:** A clean, bug-free v1 core ready for clinical layers.

---

### Phase 2 — Patient medical history & documents ⭐

**Goal:** Any provider can see a patient's complete history — conditions, cases done elsewhere, uploaded X-rays — and add their own cases with attached documents.

Tables created: `patient_contacts`, `patient_allergies`, `patient_conditions`, `patient_medications`, `patient_consents`, `patient_medical_cases`, `patient_medical_documents`, `files`

Key behaviours:
- `POST /patients/{id}/medical-cases` — provider records a case. `provider_id` is set from the authenticated user's provider profile. `is_external=true` unlocks `previous_clinic` + `previous_dentist` fields (for importing old records a patient brings on paper).
- `POST /patients/{id}/documents` — multipart upload. File goes to S3/local disk via `FileStorageService`. Metadata row written to `patient_medical_documents`. Can be linked to a `medical_case_id` or stand alone.
- `GET /patients/{id}/timeline` — returns a unified chronological list merging: appointments, medical cases, encounters, perio exams, prescriptions, lab cases, uploaded documents. Paginated, filterable by `type` and `date_from/to`.
- `GET /patients/{id}/documents/{docId}/download` — returns a short-lived signed URL (S3) or streams the file. Logs the access in `audit_logs`.
- Policy: a provider can only **edit/delete** cases they themselves created (`patient_medical_cases.provider_id = auth provider`). They can **view** all cases for any patient they have an appointment with (or all cases if owner/receptionist).

---

### Phase 3 — Clinical core

**Goal:** Dentists can document visits fully.

Tables: `encounters`, `odontogram_entries`, `perio_exams`, `perio_measures`, `treatment_plans`, `treatment_plan_items`, `prescriptions`, `prescription_items`, `lab_cases`

Key behaviours:
- Encounters are locked after 24h or manual lock — `is_locked` prevents edits.
- Odontogram uses FDI tooth numbering (11–48). Each entry is a tooth+surface+code combination.
- Treatment plan status actions (`/present`, `/accept`, `/reject`) are dedicated endpoints so state transitions are explicit and auditable.
- Prescriptions auto-number and are printable via a PDF endpoint added in Phase 4.

---

### Phase 4 — Business core

**Goal:** Billing, recalls, reminders, inventory.

Tables: `invoices`, `invoice_items`, `payments`, `payment_plans`, `payment_plan_items`, `recalls`, `communication_logs`, `inventory_items`, `suppliers`, `stock_movements`, `purchase_orders`, `purchase_order_items`

Key behaviours:
- Invoice `finalize` action freezes line items and triggers `InvoiceSent` notification.
- Recall jobs run via Laravel scheduler: daily query for `recalls.due_date <= today` → dispatch `RecallReminderService`.
- Inventory low-stock check runs nightly → notifies owner.

---

### Phase 5 — Hardening & reporting

**Goal:** Production-ready.

Tasks:
- `AuditObserver` on all write operations → `audit_logs`
- Named rate limiters per role (owner: unlimited, provider: 300/min, receptionist: 200/min)
- `GET /reports/*` endpoints backed by `ReportService` with date range filters, grouped by provider/type
- `GET /dashboard/kpis` → today's appointments, monthly revenue, active patients, pending recalls
- PDF generation for prescriptions, invoices, treatment plans (via `barryvdh/laravel-dompdf`)
- Push notification infrastructure (`POST /devices/push-tokens` → FCM/APNs)
- Full test coverage target: 80%+ on critical paths (auth, medical cases, billing state machine)
- OpenAPI spec generated from route + request annotations (`dedoc/scramble`)

---
