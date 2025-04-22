<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    /**
     * Generate a PDF receipt
     *
     * @param array $paymentData Payment details
     * @return array Contains PDF data and filename
     */
    public function generateReceipt(array $paymentData)
    {
        // Format payment data as needed
        $data = $this->formatPaymentData($paymentData);
        
        $data = [
         // Header Section
         'corporation_name' => 'RANCHI MUNICIPAL CORPORATION',
         'receipt_title' => 'SOLID WASTE USER CHARGE PAYMENT RECEIPT',
     
         // User Details
         'department' => 'Solid Waste User Charge and others',
         'account_description' => '',
         'name' => 'Rambart Garden',
         'mobile_no' => '1234567890',
         'address' => 'Belbaga Samlong, Marriage Hall',
         'category' => 'Above 3000 SqMtr',
     
         // Transaction Details
         'transaction_no' => '1231231212',
         'transaction_date' => '29 March, 2025 12:00 PM',
         'consumer_no' => '123123123',
         'ward_no' => '13',
         'holding_no' => '123123123122',
         'type' => 'Above 3000 SqMtr',
     
         // Tax Items
         'tax_items' => [
             [
                 'si_no' => 1,
                 'tax_type' => 'Solid Waste User Charge',
                 'code' => 'N.A.',
                 'bill_month' => 'January 2025 To March 2025',
                 'rate' => '5000',
                 'amount' => '5000'
             ]
         ],
     
         // Payment Details
         'total_amount' => '5000',
         'amount_in_words' => 'Five Thousand Only',
         'payment_mode' => 'Cash',
     
         // Bank Details
         'gst_no' => '123123123123',
         'pan_no' => '123123123123',
         'account_name' => 'Ranchi Municipal Corporation',
         'bank_name' => 'Indian Bank (Karachi, Ranchi)',
         'account_no' => '5343434343',
         'ifsc_code' => 'IBKL0001234',
     
         // Footer Details
         'print_date' => '2025-04-07 14:07 PM',
         'verification_contact' => '9297888512',
         'qr_code_url' => 'https://example.com/qr/1231231212', // Your generated QR code URL
     
         // Static Content
         'notes' => [
             'This is a Computer generated Demand and does not require physical signature',
             'You will receive SMS in your registered mobile no. for amount paid. If SMS is not received verify your paid amount by calling 9297888512 or visit',
         ],
     
         // Signature Section
         'issued_by' => 'Notwind Softlab Private Limited',
         'customer_remarks' => '',
         'customer_mobile' => ''
     ];


        // Generate HTML content for the PDF
        $html = $this->generateReceiptHtml($data);

       // Generate PDF from HTML string instead of a view
        $pdf = PDF::loadHTML($html);

      //   // Generate PDF
      //   $pdf = PDF::loadView('pdfs.receipt', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Generate a unique filename
        $filename = 'receipt_' . $paymentData['transaction_no'] . '_' . time() . '.pdf';
        
      //   $pdf->render();
      //   $pdf->stream("receipt.pdf");

        // Return both the PDF content and filename
        return [
            // 'pdf' => $pdf,
            'content' => $pdf->output(),
            // 'content' => $pdf->stream("receipt.pdf"),
            'filename' => $filename
        ];
    }
    
    /**
     * Save the generated PDF to storage
     *
     * @param string $content PDF content
     * @param string $filename Filename to save
     * @return string Path to the saved file
     */
    public function saveReceipt(string $content, string $filename)
    {
        $path = 'receipts/' . $filename;
        Storage::disk('public')->put($path, $content);
        
        return $path;
    }
    
    /**
     * Format the payment data for the PDF template
     *
     * @param array $paymentData Raw payment data
     * @return array Formatted data for the template
     */
    private function formatPaymentData(array $paymentData)
    {
        // Calculate the date range if needed
        $fromDate = isset($paymentData['from_date']) ? $paymentData['from_date'] : date('F Y');
        $toDate = isset($paymentData['to_date']) ? $paymentData['to_date'] : date('F Y');
        
        // Format amount in words
        $amountInWords = $this->numberToWords($paymentData['amount']);
        
        return [
            'organization' => 'RANCHI MUNICIPAL CORPORATION',
            'department' => 'Revenue Section',
            'account_description' => 'Solid Waste User Charge and others',
            'name' => $paymentData['name'] ?? '',
            'mobile' => $paymentData['mobile'] ?? '',
            'address' => $paymentData['address'] ?? '',
            'category' => $paymentData['category'] ?? '',
            'transaction_no' => $paymentData['transaction_no'] ?? '',
            'date_time' => $paymentData['date_time'] ?? date('d F, Y h:i A'),
            'consumer_no' => $paymentData['consumer_no'] ?? '',
            'ward_no' => $paymentData['ward_no'] ?? '',
            'holding_no' => $paymentData['holding_no'] ?? '',
            'type' => $paymentData['type'] ?? '',
            'bill_month' => $fromDate . ' To ' . $toDate,
            'rate_per_month' => $paymentData['rate_per_month'] ?? $paymentData['amount'],
            'amount' => $paymentData['amount'] ?? 0,
            'total' => $paymentData['amount'] ?? 0,
            'amount_in_words' => $amountInWords,
            'payment_mode' => $paymentData['payment_mode'] ?? 'Cash',
            'gst_no' => $paymentData['gst_no'] ?? '',
            'pan_no' => $paymentData['pan_no'] ?? '',
            'account_name' => 'Ranchi Municipal Corporation',
            'bank' => 'Axis Bank',
            'account_no' => '5343434343',
            'ifsc_code' => 'IBKL0001234',
            'print_date' => date('Y-m-d H:i A'),
            'customer_remarks' => $paymentData['customer_remarks'] ?? '',
            'customer_mobile' => $paymentData['mobile'] ?? '',
        ];
    }
    
    /**
     * Convert number to words
     *
     * @param float $num
     * @return string
     */
    private function numberToWords($num)
    {
        // Simple implementation - for production use a dedicated library
        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 
            18 => 'Eighteen', 19 => 'Nineteen'
        ];
        
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];
        
        $num = number_format($num, 2, '.', '');
        $num_arr = explode('.', $num);
        $whole = (int)$num_arr[0];
        $fraction = (int)$num_arr[1];
        
        if ($whole < 20) {
            $words = isset($ones[$whole]) ? $ones[$whole] : '';
        } elseif ($whole < 100) {
            $words = $tens[floor($whole / 10)];
            $words .= ($whole % 10 != 0) ? ' ' . $ones[$whole % 10] : '';
        } elseif ($whole < 1000) {
            $words = $ones[floor($whole / 100)] . ' Hundred';
            $words .= ($whole % 100 != 0) ? ' ' . $this->numberToWords($whole % 100) : '';
        } elseif ($whole < 100000) {
            $words = $this->numberToWords(floor($whole / 1000)) . ' Thousand';
            $words .= ($whole % 1000 != 0) ? ' ' . $this->numberToWords($whole % 1000) : '';
        } else {
            $words = $this->numberToWords(floor($whole / 100000)) . ' Lakh';
            $words .= ($whole % 100000 != 0) ? ' ' . $this->numberToWords($whole % 100000) : '';
        }
        
        if ($fraction > 0) {
            $words .= ' and ' . $this->numberToWords($fraction) . ' Paise';
        }
        
        return $words . ' Only';
    }

    /**
 * Generate HTML for the receipt
 * 
 * @param array $data Receipt data
 * @return string HTML content
 */
private function generateReceiptHtmlOld(array $data)
{
    // Build the HTML content
    $html = '<!DOCTYPE html>
      <html lang="en">
      <head>
         <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <title>Payment Receipt</title>
         <style>
               body {
                  font-family: Arial, sans-serif;
                  font-size: 12px;
                  line-height: 1.4;
                  color: #333;
                  margin: 0;
                  padding: 0;
               }
               .container {
                  width: 100%;
                  max-width: 800px;
                  margin: 0 auto;
                  border: 1px solid #ccc;
                  padding: 20px;
               }
               .header {
                  text-align: center;
                  border-bottom: 2px solid #333;
                  padding-bottom: 10px;
                  margin-bottom: 20px;
               }
               .header h1 {
                  font-size: 18px;
                  font-weight: bold;
                  margin: 5px 0;
               }
               .header h2 {
                  font-size: 14px;
                  font-weight: bold;
                  margin: 5px 0;
               }
               .info-row {
                  display: flex;
                  flex-wrap: wrap;
                  margin-bottom: 10px;
               }
               .info-col {
                  flex: 1;
                  min-width: 48%;
                  padding-right: 10px;
               }
               .label {
                  font-weight: bold;
                  display: inline-block;
                  width: 130px;
               }
               table {
                  width: 100%;
                  border-collapse: collapse;
                  margin: 20px 0;
               }
               table, th, td {
                  border: 1px solid #999;
               }
               th, td {
                  padding: 8px;
                  text-align: left;
               }
               th {
                  background-color: #f2f2f2;
               }
               .amount-row {
                  text-align: right;
                  margin: 10px 0;
               }
               .amount-words {
                  margin: 15px 0;
                  font-style: italic;
               }
               .footer {
                  margin-top: 30px;
                  border-top: 1px solid #ccc;
                  padding-top: 15px;
                  font-size: 11px;
               }
               .signature-area {
                  display: flex;
                  justify-content: space-between;
                  margin-top: 50px;
               }
               .signature-box {
                  width: 45%;
                  text-align: center;
               }
               .signature-line {
                  border-top: 1px solid #333;
                  margin-top: 50px;
                  padding-top: 5px;
               }
               .notes {
                  font-size: 10px;
                  font-style: italic;
                  margin: 20px 0;
               }
               .print-date {
                  font-size: 10px;
                  text-align: right;
                  margin-top: 10px;
               }
               .receipt-copy {
                  margin-top: 40px;
                  border-top: 1px dashed #999;
                  padding-top: 20px;
               }
         </style>
      </head>
      <body>
         <div class="container">
               <!-- Header Section -->
               <div class="header">
                  <h1>' . $data['organization'] . '</h1>
                  <h2>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h2>
                  <div>Department/Section: ' . $data['department'] . '</div>
                  <div>Account Description: ' . $data['account_description'] . '</div>
               </div>
               
               <!-- Customer Information -->
               <div class="info-row">
                  <div class="info-col">
                     <div><span class="label">Name:</span> ' . $data['name'] . '</div>
                     <div><span class="label">Mobile No:</span> ' . $data['mobile'] . '</div>
                     <div><span class="label">Address:</span> ' . $data['address'] . '</div>
                     <div><span class="label">Category:</span> ' . $data['category'] . '</div>
                  </div>
                  <div class="info-col">
                     <div><span class="label">Transaction No:</span> ' . $data['transaction_no'] . '</div>
                     <div><span class="label">Date & Time:</span> ' . $data['date_time'] . '</div>
                     <div><span class="label">Consumer No:</span> ' . $data['consumer_no'] . '</div>
                     <div><span class="label">Ward No:</span> ' . $data['ward_no'] . '</div>
                     <div><span class="label">Holding No:</span> ' . $data['holding_no'] . '</div>
                     <div><span class="label">Type:</span> ' . $data['type'] . '</div>
                  </div>
               </div>
               
               <!-- Payment Details Table -->
               <table>
                  <thead>
                     <tr>
                           <th>Sl No</th>
                           <th>Tax Type</th>
                           <th>HSN/SAC Code</th>
                           <th>Bill Month</th>
                           <th>Rate Per Month</th>
                           <th>Amount</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                           <td>1</td>
                           <td>Solid Waste User Charge</td>
                           <td>N.A.</td>
                           <td>' . $data['bill_month'] . '</td>
                           <td>' . number_format($data['rate_per_month'], 2) . '</td>
                           <td>' . number_format($data['amount'], 2) . '</td>
                     </tr>
                  </tbody>
                  <tfoot>
                     <tr>
                           <td colspan="5" style="text-align: right;"><strong>Total</strong></td>
                           <td>' . number_format($data['total'], 2) . '</td>
                     </tr>
                  </tfoot>
               </table>
               
               <!-- Amount Summary -->
               <div class="amount-row">
                  <strong>Amount:</strong> Rs. ' . number_format($data['amount'], 2) . '
               </div>
               <div class="amount-words">
                  <strong>Amount in Words:</strong> ' . $data['amount_in_words'] . '
               </div>
               <div>
                  <strong>Payment Mode:</strong> ' . $data['payment_mode'] . '
               </div>
               
               <!-- Notes -->
               <div class="notes">
                  Net Banking/Online Payment/Cheque/Draft/Bankers Cheque are Subject to realisation.
               </div>
               
               <div class="notes">
                  <strong>Note:</strong> This is a Computer generated Demand and does not require physical signature.
                  You will receive SMS in your registerd mobile no. for amount paid. If SMS is not received verify your paid
                  amount by calling 9297888512 or visit.
               </div>
               
               <!-- Banking Details -->
               <div style="margin-top: 15px;">
                  <div><strong>GST No:</strong> ' . $data['gst_no'] . '</div>
                  <div><strong>PAN No:</strong> ' . $data['pan_no'] . '</div>
                  <div><strong>Account Name:</strong> ' . $data['account_name'] . '</div>
                  <div><strong>Bank:</strong> ' . $data['bank'] . '</div>
                  <div><strong>Account No:</strong> ' . $data['account_no'] . '</div>
                  <div><strong>IFSC Code:</strong> ' . $data['ifsc_code'] . '</div>
               </div>
               
               <div class="print-date">
                  <strong>Print Date:</strong> ' . $data['print_date'] . '
               </div>
               
               <div style="text-align: center; margin-top: 20px;">
                  <strong>Thanking You</strong>
                  <div>' . $data['organization'] . '</div>
                  <div>In Collaboration With</div>
                  <div>Netwind Softlab Private Limited</div>
               </div>
               
               <!-- Signature Area -->
               <div class="signature-area">
                  <div class="signature-box">
                     <div class="signature-line">For ' . $data['organization'] . '</div>
                  </div>
                  <div class="signature-box">
                     <div class="signature-line">Signature of Tax Collector</div>
                  </div>
               </div>
               
               <!-- Receipt Copy -->
               <div class="receipt-copy">
                  <h3>Payment Receipt Receiving Copy</h3>
                  <p>
                     Received payment through Transaction No. ' . $data['transaction_no'] . ' at ' . $data['date_time'] . ' of Rs. ' . number_format($data['amount'], 2) . ' 
                     against demand for Waste User Charge of Consumer No. ' . $data['consumer_no'] . ' in Ward No. ' . $data['ward_no'] . '
                  </p>
                  <div>
                     <strong>Customer Remarks:</strong> ' . $data['customer_remarks'] . '
                  </div>
                  <div>
                     <strong>Customer Mobile No:</strong> ' . $data['customer_mobile'] . '
                  </div>
                  
                  <div style="margin-top: 20px;">
                     <div>Issued By Netwind Softlab Private Limited</div>
                  </div>
                  
                  <div class="signature-area">
                     <div class="signature-box">
                           <div class="signature-line">For ' . $data['organization'] . '</div>
                     </div>
                     <div class="signature-box">
                           <div class="signature-line">Signature of Customer</div>
                     </div>
                  </div>
               </div>
         </div>
      </body>
      </html>';
      
      return $html;
   }


   function generateReceiptHtml($data) {
      $html = '
      <style>
          body { font-family: Arial, sans-serif; margin: 0; padding: 15px; }
          .header { text-align: center; margin-bottom: 15px; }
          .section { margin-bottom: 15px; }
          table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
          td, th { padding: 4px; vertical-align: top; }
          .bordered td, .bordered th { border: 1px solid #000; }
          .text-right { text-align: right; }
          .text-center { text-align: center; }
          .notes { font-size: 0.9em; margin-top: 15px; }
          .signature-section { margin-top: 25px; }
      </style>
  
      <div class="header">
          <h3>RANCHI MUNICIPAL CORPORATION</h3>
          <h4>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h4>
      </div>
  
      <table>
          <tr>
              <td>Department/Section:</td>
              <td>'.$data['department'].'</td>
              <td>Transaction No:</td>
              <td>'.$data['transaction_no'].'</td>
          </tr>
          <!-- Add other fields similarly -->
      </table>
  
      <table class="bordered">
          <tr>
              <th>SI No</th>
              <th>Tax Type</th>
              <th>IISNS/ACC Code</th>
              <th>Bill Month</th>
              <th>Rate Per Month</th>
              <th>Amount</th>
          </tr>';
      
      foreach ($data['tax_items'] as $item) {
          $html .= '
          <tr>
              <td>'.$item['si_no'].'</td>
              <td>'.$item['tax_type'].'</td>
              <td>'.$item['code'].'</td>
              <td>'.$item['bill_month'].'</td>
              <td class="text-right">'.$item['rate'].'</td>
              <td class="text-right">'.$item['amount'].'</td>
          </tr>';
      }
  
      $html .= '
          <tr>
              <td colspan="5" class="text-right"><strong>Total</strong></td>
              <td class="text-right"><strong>'.$data['total_amount'].'</strong></td>
          </tr>
      </table>
  
      <div class="section">
          <p>Amount in Words: <em>'.$data['amount_in_words'].'</em></p>
          <p>Payment Mode: '.$data['payment_mode'].'</p>
      </div>
  
      <div class="notes">
          <p>Note:</p>
          <ul>
              <li>This is a Computer generated Demand and does not require physical signature</li>
              <!-- Add other notes -->
          </ul>
      </div>
  
      <table>
          <tr>
              <td>GST No: '.$data['gst_no'].'</td>
              <td>Bank: '.$data['bank_name'].'</td>
          </tr>
          <!-- Add other bank details -->
      </table>
  
      <div class="signature-section">
          <img src="'.$data['qr_code_url'].'" style="width: 100px; float: right;">
          <p>Received payment through Transaction No. '.$data['transaction_no'].'</p>
          <p>Issued By Notwind Softlab Private Limited</p>
          <p>For RANCHI MUNICIPAL CORPORATION</p>
      </div>';
  
      return $html;
  }

}

