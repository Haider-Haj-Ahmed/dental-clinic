<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ClinicSetting;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    private function authorizeReports(Request $request): void
    {
        abort_if(
            ! $request->user()->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST]),
            403
        );
    }

    private function dateFilters(Request $request): array
    {
        $request->validate([
            'date_from'   => ['sometimes', 'date'],
            'date_to'     => ['sometimes', 'date', 'after_or_equal:date_from'],
            'provider_id' => ['sometimes', 'integer', 'exists:providers,id'],
            'category'    => ['sometimes', 'string'],
        ]);

        return $request->only(['date_from', 'date_to', 'provider_id', 'category']);
    }

    public function appointments(Request $request): JsonResponse|Response
    {
        $this->authorizeReports($request);
        $data = $this->reports->appointmentsReport($this->dateFilters($request));
        return $this->formatResponse($request, $data, 'appointments-report');
    }

    public function production(Request $request): JsonResponse|Response
    {
        $this->authorizeReports($request);
        $data = $this->reports->productionReport($this->dateFilters($request));
        return $this->formatResponse($request, $data, 'production-report');
    }

    public function collections(Request $request): JsonResponse|Response
    {
        $this->authorizeReports($request);
        $data = $this->reports->collectionsReport($this->dateFilters($request));
        return $this->formatResponse($request, $data, 'collections-report');
    }

    public function recallPerformance(Request $request): JsonResponse|Response
    {
        $this->authorizeReports($request);
        $data = $this->reports->recallPerformanceReport($this->dateFilters($request));
        return $this->formatResponse($request, $data, 'recall-performance-report');
    }

    public function recallPerformance_OLD(Request $request): JsonResponse
    {
        $this->authorizeReports($request);
        return response()->json([
            'data' => $this->reports->recallPerformanceReport($this->dateFilters($request)),
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $this->authorizeReports($request);

        return response()->json([
            'data' => $this->reports->inventoryReport($this->dateFilters($request)),
        ]);
    }

    /**
     * Return report data as JSON, PDF, or Excel based on ?format= query param.
     * ?format=json  (default) — JSON response
     * ?format=pdf   — A4 PDF download via DomPDF
     * ?format=xlsx  — Excel download via PhpSpreadsheet
     */
    private function formatResponse(Request $request, array $data, string $filename): JsonResponse|Response
    {
        $format = $request->query('format', 'json');

        if ($format === 'pdf') {
            $settings = ClinicSetting::instance();
            $title    = ucwords(str_replace('-', ' ', $filename));
            $filters  = $this->dateFilters($request);

            $pdf = Pdf::loadView('pdf.report', compact('data', 'settings', 'title', 'filters'))
                ->setPaper('a4', 'landscape')
                ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);

            return $pdf->stream($filename . '.pdf');
        }

        if ($format === 'xlsx') {
            return $this->exportExcel($data, $filename);
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Export report data as Excel using PhpSpreadsheet.
     */
    private function exportExcel(array $data, string $filename): Response
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucwords(str_replace('-', ' ', $filename)));

        if (empty($data)) {
            $sheet->setCellValue('A1', 'No data available for the selected filters.');
        } else {
            // Headers from first row keys
            $headers = array_keys(is_array($data[0] ?? null) ? ($data[0] ?? []) : []);
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', ucwords(str_replace('_', ' ', $header)));
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $col++;
            }

            // Data rows
            $row = 2;
            foreach ($data as $rowData) {
                $col = 'A';
                foreach ((array) $rowData as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Auto-size columns
            foreach (range('A', $col) as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
        ]);
    }
}
