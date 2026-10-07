<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * ImportPatientsJob
 *
 * Bulk CSV patient import dispatched after file upload.
 *
 * Expected CSV columns (header row required):
 *   first_name, last_name, gender, date_of_birth, phone, email,
 *   address, emergency_contact_name, emergency_contact_phone, notes
 *
 * Rows that fail validation are skipped and logged.
 * Requesting user receives an in-app notification with import summary.
 *
 * Usage:
 *   ImportPatientsJob::dispatch($csvStoragePath, $requestingUserId);
 */
class ImportPatientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;    // no retry — partial imports are worse than clean failures
    public int $timeout = 600;

    public function __construct(
        private readonly string $csvPath,
        private readonly int    $requestedBy,
    ) {}

    public function handle(): void
    {
        $content = Storage::get($this->csvPath);

        if (! $content) {
            Log::error("ImportPatientsJob: file not found — {$this->csvPath}");
            return;
        }

        $lines   = array_filter(array_map('str_getcsv', explode("\n", trim($content))));
        $lines   = array_values($lines);
        $headers = array_map('trim', array_shift($lines));

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($lines as $i => $row) {
            if (count($row) !== count($headers)) {
                $skipped++;
                $errors[] = "Row " . ($i + 2) . ": column count mismatch";
                continue;
            }

            $data = array_combine($headers, array_map('trim', $row));

            $validator = Validator::make($data, [
                'first_name'    => ['required', 'string', 'max:100'],
                'last_name'     => ['required', 'string', 'max:100'],
                'gender'        => ['nullable', 'in:male,female,other'],
                'date_of_birth' => ['nullable', 'date'],
                'phone'         => ['nullable', 'string', 'max:30'],
                'email'         => ['nullable', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = "Row " . ($i + 2) . ": " . implode(', ', $validator->errors()->all());
                continue;
            }

            Patient::firstOrCreate(
                [
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'] ?? null,
                ],
                array_filter([
                    'first_name'              => $data['first_name'],
                    'last_name'               => $data['last_name'],
                    'gender'                  => $data['gender'] ?? null,
                    'date_of_birth'           => $data['date_of_birth'] ?? null,
                    'phone'                   => $data['phone'] ?? null,
                    'email'                   => $data['email'] ?? null,
                    'address'                 => $data['address'] ?? null,
                    'emergency_contact_name'  => $data['emergency_contact_name'] ?? null,
                    'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                    'notes'                   => $data['notes'] ?? null,
                    'is_active'               => true,
                ], fn ($v) => $v !== null && $v !== '')
            );

            $imported++;
        }

        Storage::delete($this->csvPath);

        $requester = User::find($this->requestedBy);
        $requester?->notify(new InAppNotification(
            type:  'import.completed',
            title: 'Patient import complete',
            body:  "{$imported} imported, {$skipped} skipped.",
            data:  [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => array_slice($errors, 0, 10),
            ],
            url: '/dashboard/patients',
        ));

        Log::info("ImportPatientsJob: imported={$imported}, skipped={$skipped}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ImportPatientsJob failed', [
            'csv_path' => $this->csvPath,
            'error'    => $e->getMessage(),
        ]);
    }
}
