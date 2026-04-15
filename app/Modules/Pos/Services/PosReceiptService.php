<?php

namespace App\Modules\Pos\Services;

use App\Models\Transaction;
use App\Modules\Pos\Models\PosReceipt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PosReceiptService
{
    protected string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('pos.receipt_storage_path', storage_path('app/receipts'));

        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function generateThermalReceipt(Transaction $transaction): string
    {
        $receiptData = $this->buildReceiptData($transaction);

        PosReceipt::create([
            'transaction_id' => $transaction->id,
            'receipt_number' => $this->generateReceiptNumber(),
            'receipt_type' => 'thermal',
            'template_type' => 'standard',
            'receipt_data' => $receiptData,
            'printed_at' => now(),
            'printed_by' => auth()->id(),
        ]);

        Log::info('POS thermal receipt generated', [
            'transaction_id' => $transaction->id,
            'user_id' => auth()->id(),
        ]);

        return $this->renderThermalReceipt($receiptData);
    }

    public function generatePdfReceipt(Transaction $transaction): string
    {
        $receiptData = $this->buildReceiptData($transaction);

        PosReceipt::create([
            'transaction_id' => $transaction->id,
            'receipt_number' => $this->generateReceiptNumber(),
            'receipt_type' => 'pdf',
            'template_type' => 'standard',
            'receipt_data' => $receiptData,
            'printed_at' => now(),
            'printed_by' => auth()->id(),
        ]);

        Log::info('POS PDF receipt generated', [
            'transaction_id' => $transaction->id,
            'user_id' => auth()->id(),
        ]);

        return $this->renderPdfReceipt($receiptData);
    }

    protected function buildReceiptData(Transaction $transaction): array
    {
        return [
            'receipt_number' => $this->generateReceiptNumber(),
            'transaction_id' => $transaction->id,
            'transaction_type' => $transaction->type,
            'currency_code' => $transaction->currency_code,
            'amount_foreign' => $transaction->amount_foreign,
            'amount_local' => $transaction->amount_local,
            'rate' => $transaction->rate,
            'customer_name' => $transaction->customer->name ?? 'N/A',
            'customer_id_masked' => $transaction->customer->id_number_masked ?? 'N/A',
            'counter_name' => $transaction->counter->name ?? 'N/A',
            'counter_code' => $transaction->counter->code ?? 'N/A',
            'processed_by' => $transaction->createdBy->name ?? 'N/A',
            'processed_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            'disclaimer' => $this->getBnmDisclaimer(),
        ];
    }

    protected function generateReceiptNumber(): string
    {
        return 'RCP-'.date('Ymd').'-'.Str::upper(Str::random(6));
    }

    protected function renderThermalReceipt(array $data): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Receipt</title>
    <style>
        body { font-family: monospace; font-size: 12px; width: 58mm; margin: 0; padding: 5px; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    <div class='center'><h3>CURRENCY EXCHANGE</h3></div>
    <div class='line'></div>
    <div><strong>Receipt #:</strong> {$data['receipt_number']}</div>
    <div><strong>Date:</strong> {$data['processed_at']}</div>
    <div><strong>Type:</strong> {$data['transaction_type']}</div>
    <div><strong>Currency:</strong> {$data['currency_code']}</div>
    <div><strong>Amount:</strong> {$data['amount_foreign']} {$data['currency_code']}</div>
    <div><strong>Rate:</strong> {$data['rate']}</div>
    <div><strong>Total (MYR):</strong> RM {$data['amount_local']}</div>
    <div class='line'></div>
    <div><strong>Customer:</strong> ".htmlspecialchars($data['customer_name']).'</div>
    <div><strong>ID:</strong> '.htmlspecialchars($data['customer_id_masked'])."</div>
    <div class='line'></div>
    <div><strong>Counter:</strong> {$data['counter_name']} ({$data['counter_code']})</div>
    <div><strong>Processed By:</strong> ".htmlspecialchars($data['processed_by'])."</div>
    <div class='line'></div>
    <div class='small'>".nl2br(htmlspecialchars($data['disclaimer'])).'</div>
</body>
</html>';
    }

    protected function renderPdfReceipt(array $data): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Receipt - {$data['receipt_number']}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 24px; }
        h3 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px; border-bottom: 1px solid #eee; }
        td:first-child { font-weight: bold; width: 30%; }
        .total { font-size: 16px; font-weight: bold; background: #f5f5f5; padding: 10px; margin-top: 20px; }
        .disclaimer { font-size: 10px; color: #666; margin-top: 30px; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>CURRENCY EXCHANGE RECEIPT</h1>
        <p>Official Transaction Record</p>
    </div>
    <h3>Receipt Information</h3>
    <table>
        <tr><td>Receipt Number:</td><td>{$data['receipt_number']}</td></tr>
        <tr><td>Date & Time:</td><td>{$data['processed_at']}</td></tr>
        <tr><td>Transaction ID:</td><td>{$data['transaction_id']}</td></tr>
    </table>
    <h3>Transaction Details</h3>
    <table>
        <tr><td>Type:</td><td>{$data['transaction_type']}</td></tr>
        <tr><td>Currency:</td><td>{$data['currency_code']}</td></tr>
        <tr><td>Foreign Amount:</td><td>{$data['amount_foreign']} {$data['currency_code']}</td></tr>
        <tr><td>Exchange Rate:</td><td>{$data['rate']}</td></tr>
        <tr><td>Local Amount (MYR):</td><td>RM {$data['amount_local']}</td></tr>
    </table>
    <div class='total'>Total Amount: RM {$data['amount_local']}</div>
    <h3>Customer Information</h3>
    <table>
        <tr><td>Name:</td><td>".htmlspecialchars($data['customer_name']).'</td></tr>
        <tr><td>ID:</td><td>'.htmlspecialchars($data['customer_id_masked'])."</td></tr>
    </table>
    <h3>Counter Information</h3>
    <table>
        <tr><td>Counter:</td><td>{$data['counter_name']} ({$data['counter_code']})</td></tr>
        <tr><td>Processed By:</td><td>".htmlspecialchars($data['processed_by'])."</td></tr>
    </table>
    <div class='disclaimer'>
        <strong>BNM Required Disclosures:</strong><br>
        ".nl2br(htmlspecialchars($data['disclaimer'])).'
    </div>
</body>
</html>';
    }

    protected function getBnmDisclaimer(): string
    {
        return "This transaction is conducted in accordance with Bank Negara Malaysia regulations.\n".
               "All transactions above RM 3,000 require customer due diligence.\n".
               "Transactions above RM 50,000 require enhanced due diligence.\n".
               'Please retain this receipt for your records.';
    }

    public function getReceiptByTransaction(int $transactionId, string $type): ?PosReceipt
    {
        return PosReceipt::where('transaction_id', $transactionId)
            ->where('receipt_type', $type)
            ->latest()
            ->first();
    }
}
