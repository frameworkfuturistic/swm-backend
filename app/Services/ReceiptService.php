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
         'bank_name' => 'Indian Bank (Ranchi, Branch)',
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
         'issued_by' => 'Netwind Softlab Private Limited',
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
         0 => '',
         1 => 'One',
         2 => 'Two',
         3 => 'Three',
         4 => 'Four',
         5 => 'Five',
         6 => 'Six',
         7 => 'Seven',
         8 => 'Eight',
         9 => 'Nine',
         10 => 'Ten',
         11 => 'Eleven',
         12 => 'Twelve',
         13 => 'Thirteen',
         14 => 'Fourteen',
         15 => 'Fifteen',
         16 => 'Sixteen',
         17 => 'Seventeen',
         18 => 'Eighteen',
         19 => 'Nineteen'
      ];

      $tens = [
         2 => 'Twenty',
         3 => 'Thirty',
         4 => 'Forty',
         5 => 'Fifty',
         6 => 'Sixty',
         7 => 'Seventy',
         8 => 'Eighty',
         9 => 'Ninety'
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


   // function generateReceiptHtml($data)
   // {
   //    $html = '
   //    <style>
   //        body { font-family: Arial, sans-serif; margin: 0; padding: 15px; }
   //        .header { text-align: center; margin-bottom: 15px; }
   //        .header .logo {
   //          width: 150px;
   //          margin-bottom: 15px;
   //      }
   //        .section { margin-bottom: 15px; }
   //        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
   //        td, th { padding: 4px; vertical-align: top; }
   //        .bordered td, .bordered th { border: 1px solid #000; }
   //        .text-right { text-align: right; }
   //        .text-center { text-align: center; }
   //        .notes { font-size: 0.9em; margin-top: 15px; }
   //        .signature-section { margin-top: 25px; }
   //    </style>

   //    <div class="header">
   //    <img src="' . public_path('images/rmc.png') . '" alt="RMC" class="logo">
   //        <h3>RANCHI MUNICIPAL CORPORATION</h3>
   //        <h4>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h4>
   //    </div>

   //    <table>
   //        <tr>
   //            <td>Department/Section:</td>
   //            <td>' . $data['department'] . '</td>
   //            <td>Transaction No:</td>
   //            <td>' . $data['transaction_no'] . '</td>
   //        </tr>
   //        <!-- Add other fields similarly -->
   //    </table>

   //    <table class="bordered">
   //        <tr>
   //            <th>SI No</th>
   //            <th>Tax Type</th>
   //            <th>IISNS/ACC Code</th>
   //            <th>Bill Month</th>
   //            <th>Rate Per Month</th>
   //            <th>Amount</th>
   //        </tr>';

   //    foreach ($data['tax_items'] as $item) {
   //       $html .= '
   //        <tr>
   //            <td>' . $item['si_no'] . '</td>
   //            <td>' . $item['tax_type'] . '</td>
   //            <td>' . $item['code'] . '</td>
   //            <td>' . $item['bill_month'] . '</td>
   //            <td class="text-right">' . $item['rate'] . '</td>
   //            <td class="text-right">' . $item['amount'] . '</td>
   //        </tr>';
   //    }

   //    $html .= '
   //        <tr>
   //            <td colspan="5" class="text-right"><strong>Total</strong></td>
   //            <td class="text-right"><strong>' . $data['total_amount'] . '</strong></td>
   //        </tr>
   //    </table>

   //    <div class="section">
   //        <p>Amount in Words: <em>' . $data['amount_in_words'] . '</em></p>
   //        <p>Payment Mode: ' . $data['payment_mode'] . '</p>
   //    </div>

   //    <div class="notes">
   //        <p>Note:</p>
   //        <ul>
   //            <li>This is a Computer generated Demand and does not require physical signature</li>
   //            <!-- Add other notes -->
   //        </ul>
   //    </div>

   //    <table>
   //        <tr>
   //            <td>GST No: ' . $data['gst_no'] . '</td>
   //            <td>Bank: ' . $data['bank_name'] . '</td>
   //        </tr>
   //        <!-- Add other bank details -->
   //    </table>

   //    <div class="signature-section">
   //        <img src="' . $data['qr_code_url'] . '" style="width: 100px; float: right;">
   //        <p>Received payment through Transaction No. ' . $data['transaction_no'] . '</p>
   //        <p>Issued By Notwind Softlab Private Limited</p>
   //        <p>For RANCHI MUNICIPAL CORPORATION</p>
   //    </div>';

   //    return $html;
   // }




   // function generateReceiptHtml($data)
   // {
   //    $html = '
   //  <style>
   //      body {
   //          font-family: Arial, sans-serif;
   //          margin: 0;
   //          padding: 15px;
   //      }

   //      .header {
   //          text-align: center;
   //          margin-bottom: 20px;
   //          border-bottom: 2px solid #000;
   //          padding-bottom: 10px;
   //      }

   //      .header .logo {
   //          width: 120px;
   //          margin-bottom: 10px;
   //      }

   //      .section {
   //          margin-bottom: 15px;
   //      }

   //      table {
   //          width: 100%;
   //          border-collapse: collapse;
   //          margin-bottom: 15px;
   //      }

   //      td, th {
   //          padding: 6px 10px;
   //          vertical-align: top;
   //      }

   //      .bordered td, .bordered th {
   //          border: 1px solid #000;
   //      }

   //      .text-right {
   //          text-align: right;
   //      }

   //      .text-center {
   //          text-align: center;
   //      }

   //      .notes {
   //          font-size: 0.9em;
   //          margin-top: 15px;
   //      }

   //      .signature-section {
   //          margin-top: 25px;
   //      }

   //      .info-row {
   //          display: flex;
   //          justify-content: space-between;
   //          margin-bottom: 15px;
   //      }

   //      .info-col {
   //          width: 48%;
   //      }

   //      .info-col .label {
   //          font-weight: bold;
   //          display: inline-block;
   //          width: 110px;
   //      }

   //      .info-col div {
   //          margin-bottom: 6px;
   //      }
   //  </style>

   //  <div class="header">
   //      <img src="' . public_path('images/rmc.png') . '" alt="RMC" class="logo">
   //      <h3>RANCHI MUNICIPAL CORPORATION</h3>
   //      <h4>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h4>
   //  </div>

   //  <table>
   //      <tr>
   //          <td><strong>Department/Section:</strong></td>
   //          <td>' . $data['department'] . '</td>
   //          <td><strong>Transaction No:</strong></td>
   //          <td>' . $data['transaction_no'] . '</td>
   //      </tr>
   //  </table>

   //  <div class="info-row">
   //      <div class="info-col">
   //          <div><span class="label">Name:</span> ' . $data['name'] . '</div>
   //          <div><span class="label">Mobile No:</span> ' . ($data['mobile'] ?? 'N/A') . '</div>
   //          <div><span class="label">Address:</span> ' . $data['address'] . '</div>
   //          <div><span class="label">Category:</span> ' . $data['category'] . '</div>
   //      </div>
   //      <div class="info-col">
   //          <div><span class="label">Date & Time:</span> ' . ($data['date_time'] ?? 'N/A') . '</div>
   //          <div><span class="label">Consumer No:</span> ' . $data['consumer_no'] . '</div>
   //          <div><span class="label">Ward No:</span> ' . $data['ward_no'] . '</div>
   //          <div><span class="label">Holding No:</span> ' . $data['holding_no'] . '</div>
   //          <div><span class="label">Type:</span> ' . $data['type'] . '</div>
   //      </div>
   //  </div>

   //  <table class="bordered">
   //      <tr>
   //          <th>SI No</th>
   //          <th>Tax Type</th>
   //          <th>IISNS/ACC Code</th>
   //          <th>Bill Month</th>
   //          <th>Rate Per Month</th>
   //          <th>Amount</th>
   //      </tr>';

   //    foreach ($data['tax_items'] as $item) {
   //       $html .= '
   //      <tr>
   //          <td>' . $item['si_no'] . '</td>
   //          <td>' . $item['tax_type'] . '</td>
   //          <td>' . $item['code'] . '</td>
   //          <td>' . $item['bill_month'] . '</td>
   //          <td class="text-right">' . number_format($item['rate'], 2) . '</td>
   //          <td class="text-right">' . number_format($item['amount'], 2) . '</td>
   //      </tr>';
   //    }

   //    $html .= '
   //      <tr>
   //          <td colspan="5" class="text-right"><strong>Total</strong></td>
   //          <td class="text-right"><strong>' . number_format($data['total_amount'], 2) . '</strong></td>
   //      </tr>
   //  </table>

   //  <div class="section">
   //      <p><strong>Amount in Words:</strong> <em>' . $data['amount_in_words'] . '</em></p>
   //      <p><strong>Payment Mode:</strong> ' . $data['payment_mode'] . '</p>
   //  </div>

   //  <div class="notes">
   //      <p><strong>Note:</strong></p>
   //      <ul>
   //          <li>This is a computer-generated receipt and does not require a physical signature.</li>
   //      </ul>
   //  </div>

   //  <table>
   //      <tr>
   //          <td><strong>GST No:</strong> ' . $data['gst_no'] . '</td>
   //          <td><strong>Bank:</strong> ' . $data['bank_name'] . '</td>
   //      </tr>
   //  </table>

   //  <div class="signature-section">
   //      <img src="' . $data['qr_code_url'] . '" style="width: 100px; float: right;">
   //      <p>Received payment through Transaction No. ' . $data['transaction_no'] . '</p>
   //      <p>Issued By Notwind Softlab Private Limited</p>
   //      <p>For RANCHI MUNICIPAL CORPORATION</p>
   //  </div>';

   //    return $html;
   // }




   // function generateReceiptHtml($data)
   // {
   //    $html = '
   //  <style>
   //      body {
   //          font-family: Arial, sans-serif;
   //          font-size: 14px;
   //          padding: 15px;
   //      }
   //      .header {
   //          text-align: center;
   //          margin-bottom: 20px;
   //      }
   //      .header img {
   //          height: 100px;
   //      }
   //      .header h3 {
   //          margin: 10px 0 5px;
   //      }
   //      .header h4 {
   //          margin: 5px 0;
   //          text-decoration: underline;
   //      }
   //      .info-table td {
   //          padding: 4px 8px;
   //      }
   //      .flex-container {
   //          display: flex;
   //          justify-content: space-between;
   //          margin-top: 15px;
   //      }
   //      .flex-box {
   //          width: 48%;
   //      }
   //      .label {
   //          font-weight: bold;
   //      }
   //      table.bordered {
   //          width: 100%;
   //          border-collapse: collapse;
   //          margin-top: 15px;
   //      }
   //      table.bordered th, table.bordered td {
   //          border: 1px solid black;
   //          padding: 6px 10px;
   //          text-align: center;
   //      }
   //      .text-right {
   //          text-align: right;
   //      }
   //      .section {
   //          margin-top: 15px;
   //      }
   //      .notes {
   //          font-size: 12px;
   //      }
   //      .qr {
   //          float: right;
   //          margin-top: -100px;
   //      }
   //  </style>

   //  <div class="header">
   //      <img src="' . public_path('images/rmc.png') . '" alt="Logo">
   //      <h3>RANCHI MUNICIPAL CORPORATION</h3>
   //      <h4>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h4>
   //  </div>

   //  <table class="info-table">
   //      <tr>
   //          <td><span class="label">Department/Section:</span> ' . $data['department'] . '</td>
   //          <td><span class="label">Transaction No:</span> ' . $data['transaction_no'] . '</td>
   //      </tr>
   //      <tr>
   //          <td><strong>Account Description:</strong> ' . ($data['account_desc'] ?? 'N/A') . '</td>
   //          <td><strong>Date & Time:</strong> ' . ($data['date_time'] ?? 'N/A') . '</td>

   //      </tr>
   //  </table>

   //  <div class="flex-container">
   //      <div class="flex-box">
   //          <p><span class="label">Name:</span> ' . $data['name'] . '</p>
   //          <p><span class="label">Mobile No:</span> ' . ($data['mobile'] ?? 'N/A') . '</p>
   //          <p><span class="label">Address:</span> ' . $data['address'] . '</p>
   //          <p><span class="label">Category:</span> ' . $data['category'] . '</p>
   //      </div>
   //      <div class="flex-box">
   //          <p><span class="label">Consumer No:</span> ' . $data['consumer_no'] . '</p>
   //          <p><span class="label">Ward No:</span> ' . $data['ward_no'] . '</p>
   //          <p><span class="label">Holding No:</span> ' . $data['holding_no'] . '</p>
   //          <p><span class="label">Type:</span> ' . $data['type'] . '</p>
   //      </div>
   //  </div>

   //  <table class="bordered">
   //      <thead>
   //          <tr>
   //              <th>SI No</th>
   //              <th>Tax Type</th>
   //              <th>HSN/SAC Code</th>
   //              <th>Bill Month</th>
   //              <th>Rate Per Month</th>
   //              <th>Amount</th>
   //          </tr>
   //      </thead>
   //      <tbody>';

   //    foreach ($data['tax_items'] as $item) {
   //       $html .= '
   //          <tr>
   //              <td>' . $item['si_no'] . '</td>
   //              <td>' . $item['tax_type'] . '</td>
   //              <td>' . $item['code'] . '</td>
   //              <td>' . $item['bill_month'] . '</td>
   //              <td>' . number_format($item['rate'], 2) . '</td>
   //              <td>' . number_format($item['amount'], 2) . '</td>
   //          </tr>';
   //    }

   //    $html .= '
   //          <tr>
   //              <td colspan="5" class="text-right"><strong>Total</strong></td>
   //              <td><strong>' . number_format($data['total_amount'], 2) . '</strong></td>
   //          </tr>
   //      </tbody>
   //  </table>

   //  <div class="section">
   //      <p><strong>Amount in Words:</strong> <em>' . $data['amount_in_words'] . '</em></p>
   //      <p><strong>Payment Mode:</strong> ' . $data['payment_mode'] . '</p>
   //      <p><strong>Net Banking/Online Payment/Cheque/Draft/Bankers Cheque</strong> are Subject to realisation.</p>
   //  </div>

   //  <div class="notes">
   //      <p><strong>Note:</strong></p>
   //      <ul>
   //          <li>This is a Computer generated Document and does not require physical signature</li>
   //          <li>You will receive SMS on your registered mobile no. for amount paid.</li>
   //          <li><strong>GST No:</strong> ' . $data['gst_no'] . '</li>
   //          <li><strong>PAN No:</strong> ' . $data['pan_no'] . '</li>
   //          <li><strong>Account Name:</strong> Ranchi Municipal Corporation</li>
   //          <li><strong>Bank:</strong> ' . ($data['bank_name'] ?? 'N/A') . ' (' . ($data['branch'] ?? 'N/A') . ')</li>
   //          <li><strong>Account No:</strong> ' . $data['account_no'] . '</li>
   //          <li><strong>IFSC Code:</strong> ' . ($data['ifsc'] ?? 'N/A') . '</li>
   //      </ul>
   //  </div>

   //  <div class="qr">
   //      <img src="' . $data['qr_code_url'] . '" width="100">
   //  </div>

   //  <p>Received payment through Transaction No. ' . $data['transaction_no'] . '</p>
   //  <p>Issued By Notwind Softlab Private Limited</p>
   //  <p>For RANCHI MUNICIPAL CORPORATION</p>';


   //    return $html;
   // }


   //    function generateReceiptHtml($data)
   //    {
   //       $html = '
   //     <style>
   //         body {
   //             font-family: Arial, sans-serif;
   //             font-size: 14px;
   //             padding: 15px;
   //         }
   //         .header {
   //             text-align: center;
   //             margin-bottom: 20px;
   //         }
   //         .header img {
   //             height: 100px;
   //             margin-left:-600px;
   //         }

   //         .header h3 {
   //             margin: 10px 0 5px;
   //             margin-top: -30px;
   //         }
   //         .header h4 {
   //             margin: 5px 0;
   //             text-decoration: underline;
   //         }
   //         .info-table td {
   //             padding: 4px 8px;
   //         }
   //          .flex-container {
   //         display: flex;
   //         flex-direction: row; /* ensure horizontal layout */
   //         justify-content: space-between;
   //         align-items: flex-start;
   //         gap: 20px;
   //         flex-wrap: wrap; /* optional if screen size is small */
   //       }
   //       .flex-box {
   //         width: 48%; /* ensure they share row evenly */
   //         box-sizing: border-box;
   //       }
   //         .label {
   //             font-weight: bold;
   //         }
   //         table.bordered {
   //             width: 100%;
   //             border-collapse: collapse;
   //             margin-top: 15px;
   //         }
   //         table.bordered th, table.bordered td {
   //             border: 1px solid black;
   //             padding: 6px 10px;
   //             text-align: center;
   //         }
   //         .text-right {
   //             text-align: right;
   //         }
   //         .section {
   //             margin-top: 15px;
   //         }
   //         .notes {
   //             font-size: 12px;
   //         }
   //         .qr {
   //             float: right;
   //             margin-top: -100px;
   //         }
   //     </style>

   //     <div class="header">
   //         <img src="' . public_path('images/rmc.png') . '" alt="Logo">
   //          <img src="' . public_path('images/netwind.jpeg') . '" alt="Logo" . style="height: 90px; margin-left: 1060px; width: 100px; margin-top: 1px;">
   //         <h3>RANCHI MUNICIPAL CORPORATION</h3>
   //         <h4>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h4>
   //     </div>

   //     <table class="info-table">
   //         <tr>
   //             <td><span class="label">Department/Section:</span> ' . ($data['department'] ?? 'N/A') . '</td>
   //             <td><span class="label">Transaction No:</span> ' . ($data['transaction_no'] ?? 'N/A') . '</td>
   //         </tr>
   //         <tr>
   //             <td><strong>Account Description:</strong> ' . ($data['account_desc'] ?? 'N/A') . '</td>
   //             <td><strong>Date & Time:</strong> ' . ($data['date_time'] ?? 'N/A') . '</td>
   //         </tr>
   //     </table>

   //     <div class="flex-container">
   //     <!-- Left box: Name, Mobile, Address, Category -->
   //     <div class="flex-box">
   //         <p><span class="label">Name:</span> ' . ($data['name'] ?? 'N/A') . '</p>
   //         <p><span class="label">Mobile No:</span> ' . ($data['mobile'] ?? 'N/A') . '</p>
   //         <p><span class="label">Address:</span> ' . ($data['address'] ?? 'N/A') . '</p>
   //         <p><span class="label">Category:</span> ' . ($data['category'] ?? 'N/A') . '</p>
   //     </div>

   //     <!-- Right box: Consumer, Ward, Holding, Type -->
   //     <div class="flex-box">
   //         <p><span class="label">Consumer No:</span> ' . ($data['consumer_no'] ?? 'N/A') . '</p>
   //         <p><span class="label">Ward No:</span> ' . ($data['ward_no'] ?? 'N/A') . '</p>
   //         <p><span class="label">Holding No:</span> ' . ($data['holding_no'] ?? 'N/A') . '</p>
   //         <p><span class="label">Type:</span> ' . ($data['type'] ?? 'N/A') . '</p>
   //     </div>
   // </div>


   //     <table class="bordered">
   //         <thead>
   //             <tr>
   //                 <th>SI No</th>
   //                 <th>Tax Type</th>
   //                 <th>HSN/SAC Code</th>
   //                 <th>Bill Month</th>
   //                 <th>Rate Per Month</th>
   //                 <th>Amount</th>
   //             </tr>
   //         </thead>
   //         <tbody>';

   //       foreach ($data['tax_items'] as $item) {
   //          $html .= '
   //             <tr>
   //                 <td>' . $item['si_no'] . '</td>
   //                 <td>' . $item['tax_type'] . '</td>
   //                 <td>' . $item['code'] . '</td>
   //                 <td>' . $item['bill_month'] . '</td>
   //                 <td>' . number_format($item['rate'], 2) . '</td>
   //                 <td>' . number_format($item['amount'], 2) . '</td>
   //             </tr>';
   //       }

   //       $html .= '
   //             <tr>
   //                 <td colspan="5" class="text-right"><strong>Total</strong></td>
   //                 <td><strong>' . number_format($data['total_amount'], 2) . '</strong></td>
   //             </tr>
   //         </tbody>
   //     </table>

   //     <div class="section">
   //         <p><strong>Amount in Words:</strong> <em>' . ($data['amount_in_words'] ?? 'N/A') . '</em></p>
   //         <p><strong>Payment Mode:</strong> ' . ($data['payment_mode'] ?? 'N/A') . '</p>
   //         <p><strong>Net Banking/Online Payment/Cheque/Draft/Bankers Cheque</strong> are Subject to realisation.</p>
   //     </div>

   //     <div class="notes">
   //         <p><strong>Note:</strong></p>
   //         <ul>
   //             <li>This is a Computer generated Document and does not require physical signature</li>
   //             <li>You will receive SMS on your registered mobile no. for amount paid.</li>
   //             <li><strong>GST No:</strong> ' . ($data['gst_no'] ?? 'N/A') . '</li>
   //             <li><strong>PAN No:</strong> ' . ($data['pan_no'] ?? 'N/A') . '</li>
   //             <li><strong>Account Name:</strong> Ranchi Municipal Corporation</li>
   //             <li><strong>Bank:</strong> ' . ($data['bank_name'] ?? 'N/A') . ' (' . ($data['branch'] ?? 'N/A') . ')</li>
   //             <li><strong>Account No:</strong> ' . ($data['account_no'] ?? 'N/A') . '</li>
   //             <li><strong>IFSC Code:</strong> ' . ($data['ifsc'] ?? 'N/A') . '</li>
   //         </ul>
   //     </div>

   //     <div class="qr">
   //         <div id="qrcode"></div>
   //     </div>

   //     <p>Received payment through Transaction No. ' . ($data['transaction_no'] ?? 'N/A') . '</p>
   //     <p>Issued By Notwind Softlab Private Limited</p>
   //     <p>For RANCHI MUNICIPAL CORPORATION</p>

   //     <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
   //     <script>
   //         var qrText = "Consumer No: ' . ($data['consumer_no'] ?? 'N/A') . '\\nTransaction: ' . ($data['transaction_no'] ?? 'N/A') . '\\nAmount: ₹' . number_format($data['total_amount'], 2) . '";
   //         new QRCode(document.getElementById("qrcode"), {
   //             text: qrText,
   //             width: 100,
   //             height: 100
   //         });
   //     </script>';

   //       return $html;
   //    }

   function generateReceiptHtml($data)
   {
      $logo1Path = public_path('images/rmc.png');
      $logo1Type = pathinfo($logo1Path, PATHINFO_EXTENSION);
      $logo1Data = file_get_contents($logo1Path);
      $logo1Base64 = 'data:image/' . $logo1Type . ';base64,' . base64_encode($logo1Data);

      $logo2Path = public_path('images/netwind.jpeg');
      $logo2Type = pathinfo($logo2Path, PATHINFO_EXTENSION);
      $logo2Data = file_get_contents($logo2Path);
      $logo2Base64 = 'data:image/' . $logo2Type . ';base64,' . base64_encode($logo2Data);

      $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            padding: 20px;
        }
        .logo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-left img {
            height: 85px;
            margin-left: -18px;
        }
       .logo-right img {
           margin-left: 600px;
           margin-top: -40px;
           height: 30px
        }
      
        .header-text {
            text-align: center;
            margin: 10px 0 20px;
        }
        .header-text h3 {
            margin: 5px 0;
            margin-top: -65px;
        }
        .header-text h4 {
            margin: 5px 0;
            text-decoration: underline;
        }
        .info-table, .info-table td {
            width: 100%;
            padding: 5px;
            vertical-align: top;
        }
        .info-table td {
            padding-bottom: 6px;
        }
        .flex-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .flex-box {
            width: 48%;
        }
        .bordered {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .bordered th, .bordered td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        .section {
            margin-top: 15px;
        }
        .section p, .section ul {
            margin: 5px 0;
        }
        .notes {
            font-size: 12px;
        }
        .qr {
            float: right;
            margin-top: -90px;
        }
    </style>

    <div class="logo-row">
        <div class="logo-left"><img src="' . $logo1Base64 . '" alt="RMC Logo"></div>
        <div class="logo-right"><img src="' . $logo2Base64 . '" alt="NSPL Logo"></div>
    </div>

    <div class="header-text">
        <h3>RANCHI MUNICIPAL CORPORATION</h3>
        <h4>SOLID WASTE USER CHARGE PAYMENT RECEIPT</h4>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Department/Section:</strong> ' . ($data['department'] ?? 'N/A') . '</td>
            <td><strong>Transaction No:</strong> ' . ($data['transaction_no'] ?? 'N/A') . '</td>
            <td><strong>Name:</strong> ' . ($data['name'] ?? 'N/A') . '</td>
            <td><strong>Mobile No:</strong> ' . ($data['mobile'] ?? 'N/A') . '</td>
            <td><strong>Address:</strong> ' . ($data['address'] ?? 'N/A') . '</td>
            <td><strong>Category:</strong> ' . ($data['category'] ?? 'N/A') . '</td>
        </tr>
        <tr>
            <td><strong>Account Description:</strong> ' . ($data['account_desc'] ?? 'N/A') . '</td>
            <td><strong>Date & Time:</strong> ' . ($data['date_time'] ?? 'N/A') . '</td>
            <td><strong>Consumer No:</strong> ' . ($data['consumer_no'] ?? 'N/A') . '</td>
            <td><strong>Ward No:</strong> ' . ($data['ward_no'] ?? 'N/A') . '</td>
            <td><strong>Holding No:</strong> ' . ($data['holding_no'] ?? 'N/A') . '</td>
            <td><strong>Type:</strong> ' . ($data['type'] ?? 'N/A') . '</td>
        </tr>
    </table>


    <table class="bordered">
        <thead>
            <tr>
                <th>SI No</th>
                <th>Tax Type</th>
                <th>HSN/SAC Code</th>
                <th>Bill Month</th>
                <th>Rate Per Month</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>';

      foreach ($data['tax_items'] as $item) {
         $html .= '
            <tr>
                <td>' . $item['si_no'] . '</td>
                <td>' . $item['tax_type'] . '</td>
                <td>' . $item['code'] . '</td>
                <td>' . $item['bill_month'] . '</td>
                <td>' . number_format($item['rate'], 2) . '</td>
                <td>' . number_format($item['amount'], 2) . '</td>
            </tr>';
      }

      $html .= '
            <tr>
                <td colspan="5" style="text-align:right;"><strong>Total</strong></td>
                <td><strong>' . number_format($data['total_amount'], 2) . '</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section">
        <p><strong>Amount in Words:</strong> <em>' . ($data['amount_in_words'] ?? 'N/A') . '</em></p>
        <p><strong>Payment Mode:</strong> ' . ($data['payment_mode'] ?? 'N/A') . '</p>
        <p><strong>Net Banking/Online Payment/Cheque/Draft/Bankers Cheque</strong> are Subject to realisation.</p>
    </div>

    <div class="notes">
        <p><strong>Note:</strong></p>
        <ul>
            <li>This is a Computer generated Document and does not require physical signature</li>
            <li>You will receive SMS on your registered mobile no. for amount paid.</li>
            <li><strong>GST No:</strong> ' . ($data['gst_no'] ?? 'N/A') . '</li>
            <li><strong>PAN No:</strong> ' . ($data['pan_no'] ?? 'N/A') . '</li>
            <li><strong>Account Name:</strong> Ranchi Municipal Corporation</li>
            <li><strong>Bank:</strong> ' . ($data['bank_name'] ?? 'N/A') . ' (' . ($data['branch'] ?? 'N/A') . ')</li>
            <li><strong>Account No:</strong> ' . ($data['account_no'] ?? 'N/A') . '</li>
            <li><strong>IFSC Code:</strong> ' . ($data['ifsc'] ?? 'N/A') . '</li>
        </ul>
    </div>

    <div class="qr">
        <div id="qrcode"></div>
    </div>

    <p>Received payment through Transaction No. ' . ($data['transaction_no'] ?? 'N/A') . '</p>
    <p>Issued By Netwind Softlab Private Limited</p>
    <p>For RANCHI MUNICIPAL CORPORATION</p>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        var qrText = "Consumer No: ' . ($data['consumer_no'] ?? 'N/A') . '\\nTransaction: ' . ($data['transaction_no'] ?? 'N/A') . '\\nAmount: ₹' . number_format($data['total_amount'], 2) . '";
        new QRCode(document.getElementById("qrcode"), {
            text: qrText,
            width: 100,
            height: 100
        });
    </script>';

      return $html;
   }
}
