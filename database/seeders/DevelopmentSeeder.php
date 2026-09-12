<?php

namespace Database\Seeders;

use App\Models\AiAnalysisResult;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Operatory;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedicalCase;
use App\Models\PatientMedicalDocument;
use App\Models\PatientMedication;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Recall;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════════════════════════
        // 1. USERS + PROVIDERS
        // ══════════════════════════════════════════════════════════

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@clinic.local'],
            [
                'name'              => 'Dr. Haider Ahmed',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_OWNER,
                'email_verified_at' => now(),
            ]
        );

        $providerUser = User::query()->updateOrCreate(
            ['email' => 'provider@clinic.local'],
            [
                'name'              => 'Dr. Sarah Mansour',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_PROVIDER,
                'email_verified_at' => now(),
            ]
        );

        $provider = Provider::query()->updateOrCreate(
            ['user_id' => $providerUser->id],
            [
                'name'           => $providerUser->name,
                'specialty'      => 'General Dentistry',
                'license_number' => 'SY-GD-00421',
                'phone'          => '+963-11-555-0001',
                'is_active'      => true,
            ]
        );

        $providerUser2 = User::query()->updateOrCreate(
            ['email' => 'specialist@clinic.local'],
            [
                'name'              => 'Dr. Rami Hassan',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_PROVIDER,
                'email_verified_at' => now(),
            ]
        );

        $provider2 = Provider::query()->updateOrCreate(
            ['user_id' => $providerUser2->id],
            [
                'name'           => $providerUser2->name,
                'specialty'      => 'Endodontics',
                'license_number' => 'SY-EN-00187',
                'phone'          => '+963-11-555-0002',
                'is_active'      => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'reception@clinic.local'],
            [
                'name'              => 'Lina Aziz',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_RECEPTIONIST,
                'email_verified_at' => now(),
            ]
        );

        // ══════════════════════════════════════════════════════════
        // 2. PULL CATALOGUE (seeded by CatalogueSeeder)
        // ══════════════════════════════════════════════════════════

        $chair1      = Operatory::firstWhere('name', 'Chair 1');
        $chair2      = Operatory::firstWhere('name', 'Chair 2');

        $typeExam      = AppointmentType::firstWhere('name', 'New Patient Exam');
        $typeRecall    = AppointmentType::firstWhere('name', 'Recall / Cleaning');
        $typeFilling   = AppointmentType::firstWhere('name', 'Filling');
        $typeRootCanal = AppointmentType::firstWhere('name', 'Root Canal');
        $typeEmergency = AppointmentType::firstWhere('name', 'Emergency');

        // ══════════════════════════════════════════════════════════
        // 3. PATIENTS
        // ══════════════════════════════════════════════════════════

        $pAhmad = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-001'],
            [
                'first_name'    => 'Ahmad',
                'last_name'     => 'Khalil',
                'date_of_birth' => '1985-03-14',
                'gender'        => 'male',
                'phone'         => '+963-933-111-001',
                'email'         => 'ahmad.khalil@email.com',
                'address'       => 'Al-Hamra, Homs, Syria',
            ]
        );
        PatientAllergy::firstOrCreate(['patient_id' => $pAhmad->id, 'allergen' => 'Penicillin'], ['patient_id' => $pAhmad->id, 'allergen' => 'Penicillin', 'reaction' => 'Anaphylaxis', 'severity' => 'severe']);
        PatientCondition::firstOrCreate(['patient_id' => $pAhmad->id, 'condition' => 'Type 2 Diabetes'], ['patient_id' => $pAhmad->id, 'condition' => 'Type 2 Diabetes', 'notes' => 'Managed with Metformin']);
        PatientMedication::firstOrCreate(['patient_id' => $pAhmad->id, 'drug_name' => 'Metformin'], ['patient_id' => $pAhmad->id, 'drug_name' => 'Metformin', 'dose' => '500mg', 'frequency' => 'twice daily']);

        $pSara = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-002'],
            [
                'first_name'    => 'Sara',
                'last_name'     => 'Mansour',
                'date_of_birth' => '1992-07-22',
                'gender'        => 'female',
                'phone'         => '+963-933-111-002',
                'email'         => 'sara.mansour@email.com',
                'address'       => 'Wadi Al-Dahab, Homs, Syria',
            ]
        );

        $pRami = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-003'],
            [
                'first_name'    => 'Rami',
                'last_name'     => 'Hassan',
                'date_of_birth' => '1978-11-05',
                'gender'        => 'male',
                'phone'         => '+963-933-111-003',
                'email'         => 'rami.hassan@email.com',
                'address'       => 'Bab Hood, Homs, Syria',
            ]
        );
        PatientAllergy::firstOrCreate(['patient_id' => $pRami->id, 'allergen' => 'Amoxicillin'], ['patient_id' => $pRami->id, 'allergen' => 'Amoxicillin', 'reaction' => 'Rash', 'severity' => 'moderate']);
        PatientCondition::firstOrCreate(['patient_id' => $pRami->id, 'condition' => 'Hypertension'], ['patient_id' => $pRami->id, 'condition' => 'Hypertension', 'notes' => 'Controlled with Amlodipine']);
        PatientMedication::firstOrCreate(['patient_id' => $pRami->id, 'drug_name' => 'Amlodipine'], ['patient_id' => $pRami->id, 'drug_name' => 'Amlodipine', 'dose' => '5mg', 'frequency' => 'once daily']);

        $pLina = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-004'],
            [
                'first_name'    => 'Lina',
                'last_name'     => 'Aziz',
                'date_of_birth' => '1995-01-30',
                'gender'        => 'female',
                'phone'         => '+963-933-111-004',
                'email'         => 'lina.aziz@email.com',
                'address'       => 'Akrama, Homs, Syria',
            ]
        );

        $pKhaled = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-005'],
            [
                'first_name'    => 'Khaled',
                'last_name'     => 'Nasser',
                'date_of_birth' => '1969-08-18',
                'gender'        => 'male',
                'phone'         => '+963-933-111-005',
                'email'         => null,
                'address'       => 'Al-Zahraa, Homs, Syria',
            ]
        );
        PatientAllergy::firstOrCreate(['patient_id' => $pKhaled->id, 'allergen' => 'NSAIDs'], ['patient_id' => $pKhaled->id, 'allergen' => 'NSAIDs', 'reaction' => 'GI bleeding', 'severity' => 'severe']);
        PatientCondition::firstOrCreate(['patient_id' => $pKhaled->id, 'condition' => 'Warfarin therapy'], ['patient_id' => $pKhaled->id, 'condition' => 'Warfarin therapy', 'notes' => 'INR must be checked before extractions']);
        PatientCondition::firstOrCreate(['patient_id' => $pKhaled->id, 'condition' => 'Atrial Fibrillation'], ['patient_id' => $pKhaled->id, 'condition' => 'Atrial Fibrillation', 'notes' => 'Cardiology clearance required for surgery']);
        PatientMedication::firstOrCreate(['patient_id' => $pKhaled->id, 'drug_name' => 'Warfarin'], ['patient_id' => $pKhaled->id, 'drug_name' => 'Warfarin', 'dose' => '5mg', 'frequency' => 'once daily']);

        $pMaya = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-006'],
            ['first_name' => 'Maya', 'last_name' => 'Ibrahim', 'date_of_birth' => '2001-05-12', 'gender' => 'female', 'phone' => '+963-933-111-006', 'email' => 'maya.ibrahim@email.com', 'address' => 'Al-Ghouta, Homs, Syria']
        );

        $pOmar = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-007'],
            ['first_name' => 'Omar', 'last_name' => 'Saleh', 'date_of_birth' => '1988-12-03', 'gender' => 'male', 'phone' => '+963-933-111-007', 'email' => 'omar.saleh@email.com', 'address' => 'Inshaat, Homs, Syria']
        );
        PatientCondition::firstOrCreate(['patient_id' => $pOmar->id, 'condition' => 'Dental Anxiety'], ['patient_id' => $pOmar->id, 'condition' => 'Dental Anxiety', 'notes' => 'May need sedation protocol']);

        $pNour = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-008'],
            ['first_name' => 'Nour', 'last_name' => 'Fayyad', 'date_of_birth' => '1975-09-25', 'gender' => 'female', 'phone' => '+963-933-111-008', 'email' => 'nour.fayyad@email.com', 'address' => 'Karm Al-Zeitoun, Homs, Syria']
        );
        PatientAllergy::firstOrCreate(['patient_id' => $pNour->id, 'allergen' => 'Latex'], ['patient_id' => $pNour->id, 'allergen' => 'Latex', 'reaction' => 'Contact dermatitis', 'severity' => 'mild']);

        $pTarek = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-009'],
            ['first_name' => 'Tarek', 'last_name' => 'Barakat', 'date_of_birth' => '1961-04-07', 'gender' => 'male', 'phone' => '+963-933-111-009', 'email' => null, 'address' => 'Al-Mreijeh, Homs, Syria']
        );
        PatientCondition::firstOrCreate(['patient_id' => $pTarek->id, 'condition' => 'Osteoporosis'], ['patient_id' => $pTarek->id, 'condition' => 'Osteoporosis', 'notes' => 'On bisphosphonates — risk of MRONJ']);
        PatientCondition::firstOrCreate(['patient_id' => $pTarek->id, 'condition' => 'Type 2 Diabetes'], ['patient_id' => $pTarek->id, 'condition' => 'Type 2 Diabetes', 'notes' => 'Poorly controlled, HbA1c 9.2%']);
        PatientMedication::firstOrCreate(['patient_id' => $pTarek->id, 'drug_name' => 'Alendronate'], ['patient_id' => $pTarek->id, 'drug_name' => 'Alendronate', 'dose' => '70mg', 'frequency' => 'once weekly']);
        PatientMedication::firstOrCreate(['patient_id' => $pTarek->id, 'drug_name' => 'Insulin glargine'], ['patient_id' => $pTarek->id, 'drug_name' => 'Insulin glargine', 'dose' => '20 units', 'frequency' => 'bedtime']);

        $pHala = Patient::query()->updateOrCreate(
            ['phone' => '+963-933-111-010'],
            ['first_name' => 'Hala', 'last_name' => 'Qassem', 'date_of_birth' => '1999-02-14', 'gender' => 'female', 'phone' => '+963-933-111-010', 'email' => 'hala.qassem@email.com', 'address' => 'Old City, Homs, Syria']
        );

        // ══════════════════════════════════════════════════════════
        // 4. APPOINTMENTS
        // patient + provider + appointment_type + operatory + created_by
        // ══════════════════════════════════════════════════════════

        $apptAhmadExam = Appointment::firstOrCreate(
            ['patient_id' => $pAhmad->id, 'provider_id' => $provider->id, 'start_at' => now()->subDays(30)->setTime(9, 0)],
            ['patient_id' => $pAhmad->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeExam->id, 'operatory_id' => $chair1->id, 'start_at' => now()->subDays(30)->setTime(9, 0), 'end_at' => now()->subDays(30)->setTime(10, 0), 'status' => 'completed', 'created_by' => $owner->id, 'notes' => 'New patient exam. Penicillin allergy documented.']
        );

        $apptRamiRCT = Appointment::firstOrCreate(
            ['patient_id' => $pRami->id, 'provider_id' => $provider2->id, 'start_at' => now()->subDays(14)->setTime(10, 30)],
            ['patient_id' => $pRami->id, 'provider_id' => $provider2->id, 'appointment_type_id' => $typeRootCanal->id, 'operatory_id' => $chair2->id, 'start_at' => now()->subDays(14)->setTime(10, 30), 'end_at' => now()->subDays(14)->setTime(12, 0), 'status' => 'completed', 'created_by' => $owner->id, 'notes' => 'Root canal #36.']
        );

        $apptSaraRecall = Appointment::firstOrCreate(
            ['patient_id' => $pSara->id, 'provider_id' => $provider->id, 'start_at' => now()->subDays(7)->setTime(9, 0)],
            ['patient_id' => $pSara->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeRecall->id, 'operatory_id' => $chair1->id, 'start_at' => now()->subDays(7)->setTime(9, 0), 'end_at' => now()->subDays(7)->setTime(9, 45), 'status' => 'completed', 'created_by' => $owner->id, 'notes' => null]
        );

        $apptLinaFilling = Appointment::firstOrCreate(
            ['patient_id' => $pLina->id, 'provider_id' => $provider->id, 'start_at' => now()->subDays(3)->setTime(11, 0)],
            ['patient_id' => $pLina->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeFilling->id, 'operatory_id' => $chair2->id, 'start_at' => now()->subDays(3)->setTime(11, 0), 'end_at' => now()->subDays(3)->setTime(12, 0), 'status' => 'completed', 'created_by' => $owner->id, 'notes' => 'Composite filling #23.']
        );

        $apptSaraEmergency = Appointment::firstOrCreate(
            ['patient_id' => $pSara->id, 'provider_id' => $provider->id, 'start_at' => now()->subDays(20)->setTime(16, 30)],
            ['patient_id' => $pSara->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeEmergency->id, 'operatory_id' => $chair1->id, 'start_at' => now()->subDays(20)->setTime(16, 30), 'end_at' => now()->subDays(20)->setTime(17, 0), 'status' => 'completed', 'created_by' => $owner->id, 'notes' => 'Acute pain upper right quadrant.']
        );

        // Today / upcoming
        Appointment::firstOrCreate(
            ['patient_id' => $pAhmad->id, 'provider_id' => $provider->id, 'start_at' => now()->setTime(9, 0)],
            ['patient_id' => $pAhmad->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeRecall->id, 'operatory_id' => $chair1->id, 'start_at' => now()->setTime(9, 0), 'end_at' => now()->setTime(9, 45), 'status' => 'confirmed', 'created_by' => $owner->id]
        );
        Appointment::firstOrCreate(
            ['patient_id' => $pKhaled->id, 'provider_id' => $provider2->id, 'start_at' => now()->setTime(10, 30)],
            ['patient_id' => $pKhaled->id, 'provider_id' => $provider2->id, 'appointment_type_id' => $typeRootCanal->id, 'operatory_id' => $chair2->id, 'start_at' => now()->setTime(10, 30), 'end_at' => now()->setTime(12, 0), 'status' => 'in_chair', 'created_by' => $owner->id, 'notes' => 'Warfarin INR 2.1 — safe to proceed.']
        );
        Appointment::firstOrCreate(
            ['patient_id' => $pMaya->id, 'provider_id' => $provider->id, 'start_at' => now()->setTime(14, 0)],
            ['patient_id' => $pMaya->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeExam->id, 'operatory_id' => $chair1->id, 'start_at' => now()->setTime(14, 0), 'end_at' => now()->setTime(15, 0), 'status' => 'scheduled', 'created_by' => $owner->id]
        );
        Appointment::firstOrCreate(
            ['patient_id' => $pOmar->id, 'provider_id' => $provider->id, 'start_at' => now()->addDays(2)->setTime(10, 0)],
            ['patient_id' => $pOmar->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeFilling->id, 'operatory_id' => $chair1->id, 'start_at' => now()->addDays(2)->setTime(10, 0), 'end_at' => now()->addDays(2)->setTime(11, 0), 'status' => 'scheduled', 'created_by' => $owner->id, 'notes' => 'Dental anxiety noted.']
        );
        Appointment::firstOrCreate(
            ['patient_id' => $pNour->id, 'provider_id' => $provider->id, 'start_at' => now()->addDays(3)->setTime(9, 0)],
            ['patient_id' => $pNour->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeRecall->id, 'operatory_id' => $chair2->id, 'start_at' => now()->addDays(3)->setTime(9, 0), 'end_at' => now()->addDays(3)->setTime(9, 45), 'status' => 'scheduled', 'created_by' => $owner->id, 'notes' => 'Latex allergy — use non-latex gloves.']
        );
        Appointment::firstOrCreate(
            ['patient_id' => $pTarek->id, 'provider_id' => $provider2->id, 'start_at' => now()->addDays(5)->setTime(11, 0)],
            ['patient_id' => $pTarek->id, 'provider_id' => $provider2->id, 'appointment_type_id' => $typeExam->id, 'operatory_id' => $chair2->id, 'start_at' => now()->addDays(5)->setTime(11, 0), 'end_at' => now()->addDays(5)->setTime(12, 0), 'status' => 'scheduled', 'created_by' => $owner->id, 'notes' => 'Bisphosphonate therapy — no extractions without specialist consult.']
        );
        Appointment::firstOrCreate(
            ['patient_id' => $pHala->id, 'provider_id' => $provider->id, 'start_at' => now()->addDays(7)->setTime(9, 0)],
            ['patient_id' => $pHala->id, 'provider_id' => $provider->id, 'appointment_type_id' => $typeRecall->id, 'operatory_id' => $chair1->id, 'start_at' => now()->addDays(7)->setTime(9, 0), 'end_at' => now()->addDays(7)->setTime(9, 45), 'status' => 'scheduled', 'created_by' => $owner->id]
        );

        // ══════════════════════════════════════════════════════════
        // 5. ENCOUNTERS (for completed appointments)
        // patient_id + provider_id + appointment_id must be consistent
        // locked_by references a valid user_id (not provider_id)
        // ══════════════════════════════════════════════════════════

        $enc1 = Encounter::firstOrCreate(
            ['appointment_id' => $apptAhmadExam->id],
            [
                'patient_id'     => $pAhmad->id,
                'provider_id'    => $provider->id,
                'appointment_id' => $apptAhmadExam->id,
                'encounter_date' => now()->subDays(30)->toDateString(),
                'subjective'     => 'Patient presents with intermittent pain in lower left quadrant, onset 2 weeks. Pain 6/10 with hot stimuli.',
                'objective'      => 'Tooth #36 TTP (++). Cold test: prolonged response. Periapical radiograph shows widened PDL space.',
                'assessment'     => 'Irreversible pulpitis #36 with symptomatic apical periodontitis.',
                'plan'           => 'Root canal therapy #36. Prescribe Metronidazole 500mg TID x5d (Penicillin allergy). Refer to endodontist.',
                'is_locked'      => true,
                'locked_by'      => $providerUser->id,
                'locked_at'      => now()->subDays(30)->addHour(),
            ]
        );

        $enc2 = Encounter::firstOrCreate(
            ['appointment_id' => $apptRamiRCT->id],
            [
                'patient_id'     => $pRami->id,
                'provider_id'    => $provider2->id,
                'appointment_id' => $apptRamiRCT->id,
                'encounter_date' => now()->subDays(14)->toDateString(),
                'subjective'     => 'Spontaneous throbbing pain tooth 36, worsening with hot stimuli. Pain 8/10. Onset 4 days.',
                'objective'      => 'Tooth 36 TTP (+++). Palpation (+). Cold test: no response (necrotic). PA radiolucency ~3mm.',
                'assessment'     => 'Pulp necrosis #36 with symptomatic apical periodontitis.',
                'plan'           => 'RCT completed. Metronidazole 500mg + Ibuprofen PRN (avoid Amoxicillin — allergy). Recall 6 months.',
                'is_locked'      => true,
                'locked_by'      => $providerUser2->id,
                'locked_at'      => now()->subDays(14)->addHours(2),
            ]
        );

        $enc3 = Encounter::firstOrCreate(
            ['appointment_id' => $apptSaraRecall->id],
            [
                'patient_id'     => $pSara->id,
                'provider_id'    => $provider->id,
                'appointment_id' => $apptSaraRecall->id,
                'encounter_date' => now()->subDays(7)->toDateString(),
                'subjective'     => 'Routine recall. No complaints.',
                'objective'      => 'Mild generalised gingivitis. BPE 1-1-1/1-1-1. No caries detected.',
                'assessment'     => 'Generalised mild gingivitis. No active caries.',
                'plan'           => 'Scaling and polishing performed. OHI reinforced. Recall in 6 months.',
                'is_locked'      => true,
                'locked_by'      => $providerUser->id,
                'locked_at'      => now()->subDays(7)->addMinutes(50),
            ]
        );

        $enc4 = Encounter::firstOrCreate(
            ['appointment_id' => $apptLinaFilling->id],
            [
                'patient_id'     => $pLina->id,
                'provider_id'    => $provider->id,
                'appointment_id' => $apptLinaFilling->id,
                'encounter_date' => now()->subDays(3)->toDateString(),
                'subjective'     => 'Cold sensitivity on #23 for past month. No spontaneous pain.',
                'objective'      => 'Caries #23 mesial, Class III. Cold test slightly exaggerated. No TTP.',
                'assessment'     => 'Caries #23 mesial, reversible pulpitis.',
                'plan'           => 'Composite restoration #23 completed. Calcium hydroxide liner. Recall 12 months.',
                'is_locked'      => true,
                'locked_by'      => $providerUser->id,
                'locked_at'      => now()->subDays(3)->addHour(),
            ]
        );

        Encounter::firstOrCreate(
            ['appointment_id' => $apptSaraEmergency->id],
            [
                'patient_id'     => $pSara->id,
                'provider_id'    => $provider->id,
                'appointment_id' => $apptSaraEmergency->id,
                'encounter_date' => now()->subDays(20)->toDateString(),
                'subjective'     => 'Severe acute pain upper right. Cannot sleep. Pain 9/10.',
                'objective'      => 'Tooth #16 TTP (+++). Buccal swelling. Temp 37.8°C.',
                'assessment'     => 'Acute dental abscess #16.',
                'plan'           => 'I&D performed. Augmentin 875mg BD x7d. Ibuprofen 400mg TDS PRN. Follow-up 3 days.',
                'is_locked'      => true,
                'locked_by'      => $providerUser->id,
                'locked_at'      => now()->subDays(20)->addMinutes(35),
            ]
        );

        // ══════════════════════════════════════════════════════════
        // 6. RECALLS
        // patient_id + created_by
        // ══════════════════════════════════════════════════════════

        $recallsData = [
            [$pAhmad,  now()->subDays(15),  Recall::STATUS_OVERDUE, 'routine',   '6-month recall overdue'],
            [$pRami,   now()->addDays(14),  Recall::STATUS_PENDING, 'follow_up', 'Root canal 6-month review'],
            [$pSara,   now()->addMonths(6), Recall::STATUS_PENDING, 'routine',   '6-month recall'],
            [$pLina,   now()->addYear(),    Recall::STATUS_PENDING, 'routine',   '12-month recall'],
            [$pKhaled, now()->subDays(45),  Recall::STATUS_OVERDUE, 'routine',   'High-risk patient overdue'],
            [$pMaya,   now()->addMonths(6), Recall::STATUS_PENDING, 'routine',   null],
            [$pOmar,   now()->addMonths(3), Recall::STATUS_PENDING, 'routine',   null],
            [$pNour,   now()->subDays(5),   Recall::STATUS_SENT,    'routine',   'Reminder sent via SMS'],
            [$pTarek,  now()->subDays(60),  Recall::STATUS_OVERDUE, 'routine',   'Bisphosphonate patient — priority recall'],
            [$pHala,   now()->addMonths(6), Recall::STATUS_PENDING, 'routine',   null],
        ];

        foreach ($recallsData as [$patient, $dueDate, $status, $type, $notes]) {
            Recall::firstOrCreate(
                ['patient_id' => $patient->id, 'due_date' => $dueDate->toDateString()],
                ['patient_id' => $patient->id, 'due_date' => $dueDate, 'status' => $status, 'type' => $type, 'notes' => $notes, 'created_by' => $owner->id]
            );
        }

        // ══════════════════════════════════════════════════════════
        // 7. INVOICES + ITEMS + PAYMENTS
        // payment.patient_id MUST equal invoice.patient_id
        // ══════════════════════════════════════════════════════════

        // Ahmad exam — paid
        $inv1 = Invoice::firstOrCreate(
            ['appointment_id' => $apptAhmadExam->id],
            ['patient_id' => $pAhmad->id, 'appointment_id' => $apptAhmadExam->id, 'status' => 'finalized', 'finalized_by' => $owner->id, 'finalized_at' => now()->subDays(30)->addHours(2)]
        );
        InvoiceItem::firstOrCreate(['invoice_id' => $inv1->id, 'description' => 'New Patient Exam'],       ['invoice_id' => $inv1->id, 'description' => 'New Patient Exam',   'quantity' => 1, 'unit_price' => 5000,  'total' => 5000]);
        InvoiceItem::firstOrCreate(['invoice_id' => $inv1->id, 'description' => 'Periapical X-Ray (x2)'], ['invoice_id' => $inv1->id, 'description' => 'Periapical X-Ray (x2)', 'quantity' => 2, 'unit_price' => 1500, 'total' => 3000]);
        Payment::firstOrCreate(
            ['invoice_id' => $inv1->id, 'patient_id' => $pAhmad->id],
            ['invoice_id' => $inv1->id, 'patient_id' => $pAhmad->id, 'amount' => 8000, 'method' => 'cash', 'paid_at' => now()->subDays(30)->addHours(2), 'recorded_by' => $owner->id]
        );

        // Rami RCT — partial payment
        $inv2 = Invoice::firstOrCreate(
            ['appointment_id' => $apptRamiRCT->id],
            ['patient_id' => $pRami->id, 'appointment_id' => $apptRamiRCT->id, 'status' => 'finalized', 'finalized_by' => $owner->id, 'finalized_at' => now()->subDays(14)->addHours(2), 'notes' => 'Balance due next visit.']
        );
        InvoiceItem::firstOrCreate(['invoice_id' => $inv2->id, 'description' => 'Root Canal Therapy #36'], ['invoice_id' => $inv2->id, 'description' => 'Root Canal Therapy #36', 'quantity' => 1, 'unit_price' => 35000, 'total' => 35000]);
        InvoiceItem::firstOrCreate(['invoice_id' => $inv2->id, 'description' => 'Periapical X-Ray'],       ['invoice_id' => $inv2->id, 'description' => 'Periapical X-Ray',       'quantity' => 1, 'unit_price' => 1500,  'total' => 1500]);
        Payment::firstOrCreate(
            ['invoice_id' => $inv2->id, 'patient_id' => $pRami->id],
            ['invoice_id' => $inv2->id, 'patient_id' => $pRami->id, 'amount' => 20000, 'method' => 'cash', 'paid_at' => now()->subDays(14)->addHours(2), 'notes' => 'Partial. Balance 16,500.', 'recorded_by' => $owner->id]
        );

        // Sara recall — paid
        $inv3 = Invoice::firstOrCreate(
            ['appointment_id' => $apptSaraRecall->id],
            ['patient_id' => $pSara->id, 'appointment_id' => $apptSaraRecall->id, 'status' => 'finalized', 'finalized_by' => $owner->id, 'finalized_at' => now()->subDays(7)->addHour()]
        );
        InvoiceItem::firstOrCreate(['invoice_id' => $inv3->id, 'description' => 'Scaling & Polishing'], ['invoice_id' => $inv3->id, 'description' => 'Scaling & Polishing', 'quantity' => 1, 'unit_price' => 8000, 'total' => 8000]);
        Payment::firstOrCreate(
            ['invoice_id' => $inv3->id, 'patient_id' => $pSara->id],
            ['invoice_id' => $inv3->id, 'patient_id' => $pSara->id, 'amount' => 8000, 'method' => 'cash', 'paid_at' => now()->subDays(7)->addHour(), 'recorded_by' => $owner->id]
        );

        // Lina filling — draft, unpaid
        $inv4 = Invoice::firstOrCreate(
            ['appointment_id' => $apptLinaFilling->id],
            ['patient_id' => $pLina->id, 'appointment_id' => $apptLinaFilling->id, 'status' => 'draft']
        );
        InvoiceItem::firstOrCreate(['invoice_id' => $inv4->id, 'description' => 'Composite Restoration #23'], ['invoice_id' => $inv4->id, 'description' => 'Composite Restoration #23', 'quantity' => 1, 'unit_price' => 12000, 'total' => 12000]);

        // ══════════════════════════════════════════════════════════
        // 8. MEDICAL CASES + DOCUMENTS
        // patient_id consistent throughout
        // uploaded_by is a User id (not Provider id)
        // ══════════════════════════════════════════════════════════

        $case1 = PatientMedicalCase::firstOrCreate(
            ['patient_id' => $pAhmad->id, 'title' => 'Root Canal Therapy #36'],
            ['patient_id' => $pAhmad->id, 'title' => 'Root Canal Therapy #36', 'description' => 'Irreversible pulpitis, apical periodontitis. Penicillin allergy documented.', 'status' => 'active', 'opened_at' => now()->subDays(30)]
        );

        $doc = PatientMedicalDocument::firstOrCreate(
            ['patient_id' => $pAhmad->id, 'title' => 'Periapical X-Ray #36'],
            [
                'patient_id'      => $pAhmad->id,
                'medical_case_id' => $case1->id,
                'title'           => 'Periapical X-Ray #36',
                'document_type'   => 'xray',
                'file_path'       => 'documents/sample/xray_placeholder.jpg',
                'mime_type'       => 'image/jpeg',
                'uploaded_by'     => $providerUser->id,
                'uploaded_at'     => now()->subDays(30),
            ]
        );

        PatientMedicalCase::firstOrCreate(
            ['patient_id' => $pRami->id, 'title' => 'Crown Preparation #16'],
            ['patient_id' => $pRami->id, 'title' => 'Crown Preparation #16', 'description' => 'Full coverage crown. Warfarin therapy — INR monitored.', 'status' => 'active', 'opened_at' => now()->subDays(14)]
        );

        // ══════════════════════════════════════════════════════════
        // 9. AI ANALYSIS RESULTS
        // patient_id MUST match source encounter/document patient
        // requested_by / reviewed_by = User id (not Provider id)
        // ai_provider = 'google', ai_model = 'gemini-2.0-flash'
        // ══════════════════════════════════════════════════════════

        // SOAP suggestion for Ahmad — pending review
        AiAnalysisResult::firstOrCreate(
            ['patient_id' => $pAhmad->id, 'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION, 'source_type' => 'encounter', 'source_id' => $enc1->id],
            [
                'patient_id'    => $pAhmad->id,
                'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
                'source_type'   => 'encounter',
                'source_id'     => $enc1->id,
                'status'        => AiAnalysisResult::STATUS_PENDING,
                'requested_by'  => $providerUser->id,
                'ai_provider'   => 'google',
                'ai_model'      => 'gemini-2.0-flash',
                'input_summary' => "Patient #{$pAhmad->id} — encounter #{$enc1->id}",
                'result'        => [
                    'soap' => [
                        'S' => 'Intermittent pain lower left quadrant, 2 weeks. Pain 6/10 with hot stimuli.',
                        'O' => 'Tooth #36 TTP (++). Cold test: prolonged response. Widened PDL space on PA radiograph.',
                        'A' => 'Irreversible pulpitis #36 with symptomatic apical periodontitis.',
                        'P' => 'RCT #36. Metronidazole 500mg TID x5d — Penicillin allergy, avoid beta-lactams. Review 1 week.',
                    ],
                    'drug_interactions' => [
                        ['message' => 'Avoid Amoxicillin — documented Penicillin allergy. Consider Metronidazole as alternative.', 'severity' => 'HIGH'],
                    ],
                    'clinical_nuances' => 'Type 2 Diabetes — monitor healing. Ensure adequate pain management post-procedure.',
                    'tags' => ['diabetic', 'penicillin-allergy'],
                ],
            ]
        );

        // X-ray analysis for Ahmad's document — accepted
        AiAnalysisResult::firstOrCreate(
            ['patient_id' => $pAhmad->id, 'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS, 'source_type' => 'document', 'source_id' => $doc->id],
            [
                'patient_id'    => $pAhmad->id,
                'analysis_type' => AiAnalysisResult::TYPE_XRAY_ANALYSIS,
                'source_type'   => 'document',
                'source_id'     => $doc->id,
                'status'        => AiAnalysisResult::STATUS_ACCEPTED,
                'requested_by'  => $providerUser->id,
                'reviewed_by'   => $providerUser->id,
                'reviewed_at'   => now()->subDays(29),
                'ai_provider'   => 'google',
                'ai_model'      => 'gemini-2.0-flash',
                'input_summary' => "Periapical X-Ray — Patient #{$pAhmad->id} Tooth #36",
                'result'        => [
                    'findings'  => 'Widened PDL space at apex #36. Periapical radiolucency ~3mm. No root fracture. Single straight root.',
                    'urgency'   => 'high',
                    'fdi_teeth' => ['36'],
                    'summary'   => 'Periapical pathology consistent with apical periodontitis at #36. Endodontic treatment recommended.',
                ],
            ]
        );

        // SOAP suggestion for Rami — dismissed
        AiAnalysisResult::firstOrCreate(
            ['patient_id' => $pRami->id, 'analysis_type' => AiAnalysisResult::TYPE_SOAP_SUGGESTION, 'source_type' => 'encounter', 'source_id' => $enc2->id],
            [
                'patient_id'     => $pRami->id,
                'analysis_type'  => AiAnalysisResult::TYPE_SOAP_SUGGESTION,
                'source_type'    => 'encounter',
                'source_id'      => $enc2->id,
                'status'         => AiAnalysisResult::STATUS_DISMISSED,
                'requested_by'   => $providerUser2->id,
                'reviewed_by'    => $providerUser2->id,
                'reviewed_at'    => now()->subDays(13),
                'reviewer_notes' => 'Already documented manually.',
                'ai_provider'    => 'google',
                'ai_model'       => 'gemini-2.0-flash',
                'input_summary'  => "Patient #{$pRami->id} — encounter #{$enc2->id}",
                'result'         => [
                    'soap' => [
                        'S' => 'Spontaneous throbbing pain tooth 36. Hot stimuli worsen. Pain 8/10.',
                        'O' => 'TTP (+++), palpation (+), no cold response. PA radiolucency present.',
                        'A' => 'Pulp necrosis #36, symptomatic apical periodontitis.',
                        'P' => 'RCT initiated. Avoid Amoxicillin (allergy). Metronidazole + analgesics.',
                    ],
                    'drug_interactions' => [
                        ['message' => 'Amoxicillin contraindicated — documented allergy. Use Metronidazole.', 'severity' => 'HIGH'],
                        ['message' => 'Avoid NSAIDs — patient on Warfarin. Use Paracetamol for post-op pain.', 'severity' => 'MODERATE'],
                    ],
                    'clinical_nuances' => 'Warfarin therapy — avoid NSAIDs. Use Paracetamol.',
                    'tags' => ['warfarin', 'amoxicillin-allergy'],
                ],
            ]
        );

        // Prescription suggestion for Lina — pending
        AiAnalysisResult::firstOrCreate(
            ['patient_id' => $pLina->id, 'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION, 'source_type' => 'encounter', 'source_id' => $enc4->id],
            [
                'patient_id'    => $pLina->id,
                'analysis_type' => AiAnalysisResult::TYPE_PRESCRIPTION_SUGGESTION,
                'source_type'   => 'encounter',
                'source_id'     => $enc4->id,
                'status'        => AiAnalysisResult::STATUS_PENDING,
                'requested_by'  => $providerUser->id,
                'ai_provider'   => 'google',
                'ai_model'      => 'gemini-2.0-flash',
                'input_summary' => "Patient #{$pLina->id} — post composite restoration #23",
                'result'        => [
                    'items' => [
                        ['drug_name' => 'Ibuprofen 400mg', 'dose' => '400mg', 'frequency' => 'every 8 hours', 'duration' => '3 days', 'instructions' => 'Take with food. PRN for pain.'],
                        ['drug_name' => 'Chlorhexidine mouthwash 0.12%', 'dose' => '15ml', 'frequency' => 'twice daily', 'duration' => '7 days', 'instructions' => 'Rinse 30 seconds after meals.'],
                    ],
                    'drug_interactions' => [],
                    'clinical_nuances'  => 'No known allergies. No contraindications identified.',
                    'tags' => [],
                ],
            ]
        );

        // Perio risk for Sara — accepted
        AiAnalysisResult::firstOrCreate(
            ['patient_id' => $pSara->id, 'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK, 'source_type' => 'encounter', 'source_id' => $enc3->id],
            [
                'patient_id'    => $pSara->id,
                'analysis_type' => AiAnalysisResult::TYPE_PERIO_RISK,
                'source_type'   => 'encounter',
                'source_id'     => $enc3->id,
                'status'        => AiAnalysisResult::STATUS_ACCEPTED,
                'requested_by'  => $providerUser->id,
                'reviewed_by'   => $providerUser->id,
                'reviewed_at'   => now()->subDays(7)->addHour(),
                'ai_provider'   => 'google',
                'ai_model'      => 'gemini-2.0-flash',
                'input_summary' => "Patient #{$pSara->id} — perio assessment recall",
                'result'        => [
                    'risk_score'      => 2,
                    'risk_level'      => 'low',
                    'findings'        => 'Mild generalised gingivitis. BPE 1 throughout. No bone loss. Good oral hygiene.',
                    'recommendations' => '6-month recall appropriate. Continue OHI reinforcement.',
                    'tags' => ['gingivitis', 'low-risk'],
                ],
            ]
        );

        // ══════════════════════════════════════════════════════════
        // 10. INVENTORY + SUPPLIER
        // ══════════════════════════════════════════════════════════

        Supplier::firstOrCreate(
            ['name' => 'Syrian Dental Supplies Co.'],
            ['name' => 'Syrian Dental Supplies Co.', 'phone' => '+963-11-444-5555', 'email' => 'orders@syriandental.sy', 'address' => 'Industrial Zone, Damascus, Syria']
        );

        $inventoryItems = [
            ['name' => 'Composite Resin A2',        'unit' => 'syringe', 'quantity' => 12, 'reorder_level' => 5,  'unit_cost' => 4500],
            ['name' => 'Composite Resin A3',        'unit' => 'syringe', 'quantity' => 8,  'reorder_level' => 5,  'unit_cost' => 4500],
            ['name' => 'Latex Gloves M (box/100)',   'unit' => 'box',     'quantity' => 6,  'reorder_level' => 3,  'unit_cost' => 8000],
            ['name' => 'Non-Latex Gloves M (box/100)','unit' => 'box',   'quantity' => 2,  'reorder_level' => 3,  'unit_cost' => 9000],
            ['name' => 'Surgical Masks (box/50)',    'unit' => 'box',     'quantity' => 10, 'reorder_level' => 4,  'unit_cost' => 6000],
            ['name' => 'Lidocaine 2% cartridge',    'unit' => 'box',     'quantity' => 3,  'reorder_level' => 5,  'unit_cost' => 15000],
            ['name' => 'Metronidazole 500mg',        'unit' => 'strip',   'quantity' => 20, 'reorder_level' => 10, 'unit_cost' => 2000],
            ['name' => 'Ibuprofen 400mg',            'unit' => 'strip',   'quantity' => 15, 'reorder_level' => 10, 'unit_cost' => 1500],
            ['name' => 'Calcium Hydroxide paste',    'unit' => 'tube',    'quantity' => 4,  'reorder_level' => 2,  'unit_cost' => 12000],
            ['name' => 'Gutta Percha Points #30',    'unit' => 'box',     'quantity' => 1,  'reorder_level' => 2,  'unit_cost' => 7000],
        ];

        foreach ($inventoryItems as $data) {
            InventoryItem::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
