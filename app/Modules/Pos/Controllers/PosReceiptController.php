<?php

namespace App\Modules\Pos\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Modules\Pos\Services\PosReceiptService;
use Illuminate\Http\Response;

class PosReceiptController extends Controller
{
    protected PosReceiptService $receiptService;

    public function __construct(PosReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    public function thermal(Transaction $transaction): Response
    {
        $html = $this->receiptService->generateThermalReceipt($transaction);

        return new Response($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    public function pdf(Transaction $transaction): Response
    {
        $html = $this->receiptService->generatePdfReceipt($transaction);

        return new Response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="receipt-'.$transaction->id.'.html"',
        ]);
    }
}
