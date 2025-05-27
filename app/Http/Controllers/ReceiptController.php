<?php

namespace App\Http\Controllers;

use App\Services\ReceiptService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Exception;
use InvalidArgumentException;
use Throwable;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ReceiptController extends Controller
{
   /**
     * Download PDF receipt
     * GET /api/receipts/{receipt_no}/download
     */
    public function downloadPdf($receipt_no)
    {
        // Your existing code here
        return $this->generateReceipt($receipt_no);
    }

    /**
     * Stream PDF for inline viewing
     * GET /api/receipts/{receipt_no}/pdf
     */
    public function streamPdf($receipt_no)
    {
        try {
            $paymentData = $this->fetchPaymentData($receipt_no);
            $pdfContent = $this->generatePdfContent($paymentData);

            return Response::streamDownload(
                function () use ($pdfContent) {
                    echo $pdfContent;
                },
                "receipt_{$receipt_no}.pdf",
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="receipt_' . $receipt_no . '.pdf"'
                ]
            );
        } catch (Exception $e) {
            return "Error";
        }
    }

    public function generateReceipt($receipt_id)
    {
        // Input validation
        if (empty($receipt_id) || !is_string($receipt_id)) {
            return response()->json([
                'error' => 'Invalid receipt ID provided',
                'code' => 'INVALID_INPUT'
            ], 400);
        }

        try {
            // Fetch payment data with all related information
            $paymentData = $this->fetchPaymentData($receipt_id);

            // Generate PDF
            $pdfContent = $this->generatePdfContent($paymentData);

            // Return PDF response
            return $this->createPdfResponse($pdfContent, $receipt_id);

        } catch (PaymentNotFoundException $e) {
            Log::warning('Receipt not found', [
                'receipt_id' => $receipt_id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return response()->json([
                'error' => 'Receipt not found',
                'code' => 'RECEIPT_NOT_FOUND'
            ], 404);

        } catch (DatabaseException $e) {
            Log::error('Database error while fetching receipt', [
                'receipt_id' => $receipt_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Database error occurred',
                'code' => 'DATABASE_ERROR'
            ], 500);

        } catch (PdfGenerationException $e) {
            Log::error('PDF generation failed', [
                'receipt_id' => $receipt_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to generate PDF',
                'code' => 'PDF_GENERATION_ERROR'
            ], 500);

        } catch (QueryException $e) {
            Log::error('SQL query error', [
                'receipt_id' => $receipt_id,
                'sql_error' => $e->getMessage(),
                'sql_code' => $e->getCode()
            ]);

            return response()->json([
                'error' => 'Database query failed',
                'code' => 'QUERY_ERROR'
            ], 500);

        } catch (Throwable $e) {
            Log::critical('Unexpected error in receipt generation', [
                'receipt_id' => $receipt_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred',
                'code' => 'UNEXPECTED_ERROR'
            ], 500);
        }
    }

    /**
     * Fetch payment data from database
     */
    private function fetchPaymentData(string $receipt_id): array
    {
        try {
            $data = DB::table('payments as p')
                ->select([
                    'r.ratepayer_name',
                    'r.mobile_no',
                    'r.ratepayer_address',
                    'p.receipt_no',
                    'r.consumer_no',
                    'c.category as category_name',
                    'w.ward_name',
                    'r.holding_no',
                    'p.payment_from',
                    'p.payment_to',
                    's.rate',
                    'p.amount',
                    'p.payment_mode',
                    DB::raw("'' as gst_no"),
                    DB::raw("'' as pan_no"),
                    DB::raw("'' as remarks")
                ])
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->join('sub_categories as s', 'r.subcategory_id', '=', 's.id')
                ->join('categories as c', 's.category_id', '=', 'c.id')
                ->join('wards as w', 'w.id', '=', 'r.ward_id')
                ->where('p.id', $receipt_id)
                ->first();

            if (!$data) {
                throw new PaymentNotFoundException("Payment with receipt ID {$receipt_id} not found");
            }

            // Validate required fields
            $this->validatePaymentData($data);

            return $this->formatPaymentData($data);

        } catch (QueryException $e) {
            throw new DatabaseException("Failed to fetch payment data: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate that required payment data fields are present
     */
    private function validatePaymentData($data): void
    {
        $requiredFields = ['ratepayer_name', 'receipt_no', 'amount'];
        
        foreach ($requiredFields as $field) {
            if (empty($data->$field)) {
                throw new PaymentNotFoundException("Required field '{$field}' is missing or empty");
            }
        }

        // Validate amount is numeric
        if (!is_numeric($data->amount) || $data->amount <= 0) {
            throw new PaymentNotFoundException("Invalid payment amount: {$data->amount}");
        }
    }

    /**
     * Format payment data into the required array structure
     */
    private function formatPaymentData($data): array
    {
        return [
            'name'             => $data->ratepayer_name,
            'mobile'           => $data->mobile_no ?? '',
            'address'          => $data->ratepayer_address ?? '',
            'receipt_no'       => $data->receipt_no,
            'consumer_no'      => $data->consumer_no ?? '',
            'category'         => $data->category_name ?? '',
            'ward_no'          => $data->ward_name ?? '',
            'holding_no'       => $data->holding_no ?? '',
            'type'             => $data->category_name ?? '',
            'payment_from'     => $data->payment_from ?? '',
            'payment_to'       => $data->payment_to ?? '',
            'rate_per_month'   => $data->rate ?? 0,
            'amount'           => $data->amount,
            'total'            => $data->amount,
            'payment_mode'     => $data->payment_mode ?? '',
            'gst_no'           => $data->gst_no ?? '',
            'pan_no'           => $data->pan_no ?? '',
            'customer_remarks' => $data->remarks ?? '',
        ];
    }

    /**
     * Generate PDF content
     */
    private function generatePdfContent(array $paymentData): string
    {
        try {
            $receiptService = new ReceiptService();
            $receipt = $receiptService->generateReceipt($paymentData);

            if (!isset($receipt['content']) || empty($receipt['content'])) {
                throw new PdfGenerationException("PDF content is empty or invalid");
            }

            return $receipt['content'];

        } catch (Exception $e) {
            throw new PdfGenerationException("PDF generation failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create PDF response for download
     */
    private function createPdfResponse(string $pdfContent, string $receipt_id): StreamedResponse
    {
        try {
            $filename = "receipt_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $receipt_id) . ".pdf";
            return new StreamedResponse(function () use ($pdfContent) {
            echo $pdfContent;
            }, 200, [
               'Content-Type' => 'application/pdf',
               'Content-Disposition' => 'inline; filename="' . $filename . '"',
               'Cache-Control' => 'no-cache, no-store, must-revalidate',
               'Pragma' => 'no-cache',
               'Expires' => '0',
            ]);



            // return Response::streamDownload(
            //     function () use ($pdfContent) {
            //         echo $pdfContent;
            //     },
            //     $filename,
            //     [
            //         'Content-Type' => 'application/pdf',
            //         'Content-Disposition' => 'inline; filename="' . $filename . '"',
            //         'Cache-Control' => 'no-cache, no-store, must-revalidate',
            //         'Pragma' => 'no-cache',
            //         'Expires' => '0'
            //     ]
            // );

        } catch (Exception $e) {
            throw new PdfGenerationException("Failed to create PDF response: " . $e->getMessage(), 0, $e);
        }
    }
}

// Custom Exception Classes
class PaymentNotFoundException extends Exception
{
    protected $message = 'Payment not found';
}

class DatabaseException extends Exception
{
    protected $message = 'Database operation failed';
}

class PdfGenerationException extends Exception
{
    protected $message = 'PDF generation failed';
}

// Alternative: Service-based approach with exception handling
class PaymentService
{
    public function getPaymentData(string $receipt_id): array
    {
        if (empty($receipt_id)) {
            throw new InvalidArgumentException('Receipt ID cannot be empty');
        }

        try {
            $data = DB::table('payments as p')
                ->select([
                    'r.ratepayer_name',
                    'r.mobile_no',
                    'r.ratepayer_address',
                    'p.receipt_no',
                    'r.consumer_no',
                    'c.name as category_name',
                    'w.ward_name',
                    'r.holding_no',
                    'p.payment_from',
                    'p.payment_to',
                    's.rate',
                    'p.amount',
                    'p.payment_mode',
                    'r.gst_no',
                    'r.pan_no',
                    'p.remarks'
                ])
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->join('sub_categories as s', 'r.subcategory_id', '=', 's.id')
                ->join('categories as c', 's.category_id', '=', 'c.id')
                ->join('wards as w', 'w.id', '=', 'r.ward_id')
                ->where('p.receipt_no', $receipt_id)
                ->first();

            if (!$data) {
                throw new PaymentNotFoundException("Payment with receipt ID {$receipt_id} not found");
            }

            return [
                'name'             => $data->ratepayer_name,
                'mobile'           => $data->mobile_no ?? '',
                'address'          => $data->ratepayer_address ?? '',
                'receipt_no'       => $data->receipt_no,
                'consumer_no'      => $data->consumer_no ?? '',
                'category'         => $data->category_name ?? '',
                'ward_no'          => $data->ward_name ?? '',
                'holding_no'       => $data->holding_no ?? '',
                'type'             => $data->category_name ?? '',
                'payment_from'     => $data->payment_from ?? '',
                'payment_to'       => $data->payment_to ?? '',
                'rate_per_month'   => $data->rate ?? 0,
                'amount'           => $data->amount,
                'total'            => $data->amount,
                'payment_mode'     => $data->payment_mode ?? '',
                'gst_no'           => $data->gst_no ?? '',
                'pan_no'           => $data->pan_no ?? '',
                'customer_remarks' => $data->remarks ?? '',
            ];

        } catch (QueryException $e) {
            Log::error('Database query failed in PaymentService', [
                'receipt_id' => $receipt_id,
                'error' => $e->getMessage()
            ]);
            throw new DatabaseException("Failed to fetch payment data: " . $e->getMessage(), 0, $e);
        }
    }
}

// Usage example with try-catch in controller
class SimpleReceiptController extends Controller 
{
    public function download($receipt_id)
    {
        try {
            $paymentService = new PaymentService();
            $paymentData = $paymentService->getPaymentData($receipt_id);

            $receiptService = new ReceiptService();
            $receipt = $receiptService->generateReceipt($paymentData);
            $pdfContent = $receipt['content'];

            $filename = "receipt_{$receipt_id}.pdf";

            return Response::streamDownload(
                fn() => print($pdfContent),
                $filename,
                ['Content-Type' => 'application/pdf']
            );

        } catch (PaymentNotFoundException $e) {
            abort(404, 'Receipt not found');
        } catch (DatabaseException $e) {
            abort(500, 'Database error occurred');
        } catch (PdfGenerationException $e) {
            abort(500, 'Failed to generate PDF');
        } catch (Throwable $e) {
            Log::error('Unexpected error in receipt download', [
                'receipt_id' => $receipt_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'An unexpected error occurred');
        }
    }
}
