<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $transaction->id }} - CEMS-MY</title>
    <style>
        /* Base styles for both thermal (80mm) and A4 printing */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }

        /* Thermal receipt (80mm) - default */
        .receipt-container {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 5mm;
        }

        /* A4 print mode */
        .a4-mode .receipt-container {
            width: 210mm;
            max-width: 210mm;
            padding: 20mm;
        }

        /* Header Section */
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .company-info {
            font-size: 8pt;
            margin-bottom: 1mm;
        }

        .receipt-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 2mm 0;
        }

        .receipt-number {
            font-size: 10pt;
            font-weight: bold;
        }

        /* Transaction Details */
        .section {
            border-bottom: 1px dashed #000;
            padding: 2mm 0;
            margin-bottom: 2mm;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1mm;
            font-size: 9pt;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
            font-size: 9pt;
        }

        .detail-label {
            flex: 1;
        }

        .detail-value {
            flex: 1.5;
            text-align: right;
            font-weight: bold;
        }

        /* Amount Display */
        .amount-section {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 3mm;
            margin: 3mm 0;
            text-align: center;
        }

        .amount-foreign {
            font-size: 12pt;
            font-weight: bold;
        }

        .amount-local {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 2mm;
        }

        .exchange-rate {
            font-size: 8pt;
            margin-top: 1mm;
        }

        /* QR Code Section */
        .qr-section {
            text-align: center;
            padding: 3mm 0;
            margin: 2mm 0;
        }

        .qr-code {
            width: 25mm;
            height: 25mm;
            margin: 0 auto;
        }

        .qr-text {
            font-size: 7pt;
            margin-top: 1mm;
        }

        /* Barcode Section */
        .barcode-section {
            text-align: center;
            padding: 2mm 0;
        }

        .barcode-code {
            height: 10mm;
            margin: 0 auto;
        }

        .barcode-text {
            font-size: 8pt;
            letter-spacing: 2px;
            margin-top: 1mm;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 3mm;
            margin-top: 2mm;
            border-top: 1px dashed #000;
        }

        .footer-notice {
            font-size: 7pt;
            font-style: italic;
            margin-bottom: 2mm;
        }

        .bnm-notice {
            font-size: 6pt;
            color: #333;
            margin-top: 2mm;
            padding: 2mm;
            border: 1px solid #000;
            background: #f9f9f9;
        }

        .cut-line {
            text-align: center;
            font-size: 8pt;
            margin-top: 3mm;
            letter-spacing: 3px;
        }

        /* Print-specific styles */
        @media print {
            body {
                width: 80mm;
                margin: 0;
            }

            .receipt-container {
                width: 80mm;
                max-width: 80mm;
                margin: 0;
                padding: 2mm;
            }

            .no-print {
                display: none !important;
            }
        }

        /* A4 specific styles */
        .a4-mode body {
            width: 210mm;
        }

        .a4-mode .receipt-container {
            width: 210mm;
            max-width: 210mm;
            padding: 15mm;
        }

        .a4-mode .header {
            border-bottom: 2px solid #000;
        }

        .a4-mode .section {
            border-bottom: 1px solid #000;
        }

        .a4-mode .footer {
            border-top: 2px solid #000;
        }
    </style>
</head>
<body class="{{ $mode ?? 'thermal' }}">
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">CEMS-MY</div>
            <div class="company-info">Licensed Money Services Business</div>
            <div class="company-info">BNM Licensed MSB</div>
            <div class="receipt-title">TRANSACTION RECEIPT</div>
            <div class="receipt-number">Receipt #: {{ str_pad($transaction->id, 8, '0', STR_PAD_LEFT) }}</div>
        </div>

        <!-- Date & Time -->
        <div class="section">
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value">{{ $transaction->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value">{{ $transaction->created_at->format('H:i:s') }}</span>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="section">
            <div class="section-title">Customer Information</div>
            <div class="detail-row">
                <span class="detail-label">Customer ID:</span>
                <span class="detail-value">{{ str_pad($transaction->customer_id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value">{{ $masked_customer_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">ID Type:</span>
                <span class="detail-value">{{ $transaction->customer->id_type ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Risk Rating:</span>
                <span class="detail-value">{{ $transaction->customer->risk_rating ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="section">
            <div class="section-title">Transaction Details</div>
            <div class="detail-row">
                <span class="detail-label">Type:</span>
                <span class="detail-value">{{ $transaction->type }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Currency:</span>
                <span class="detail-value">{{ $transaction->currency_code }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purpose:</span>
                <span class="detail-value">{{ $transaction->purpose }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Source:</span>
                <span class="detail-value">{{ $transaction->source_of_funds }}</span>
            </div>
        </div>

        <!-- Amount Section -->
        <div class="amount-section">
            <div class="amount-foreign">
                {{ $transaction->type === 'Buy' ? '+' : '-' }}{{ number_format($transaction->amount_foreign, 4) }} {{ $transaction->currency_code }}
            </div>
            <div class="exchange-rate">
                @ {{ number_format($transaction->rate, 6) }} MYR/{{ $transaction->currency_code }}
            </div>
            <div class="amount-local">
                RM {{ number_format($transaction->amount_local, 2) }}
            </div>
        </div>

        <!-- Processing Details -->
        <div class="section">
            <div class="section-title">Processing Details</div>
            <div class="detail-row">
                <span class="detail-label">Teller ID:</span>
                <span class="detail-value">{{ str_pad($transaction->user_id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">CDD Level:</span>
                <span class="detail-value">{{ $transaction->cdd_level }}</span>
            </div>
            @if($transaction->approved_by)
            <div class="detail-row">
                <span class="detail-label">Approved By:</span>
                <span class="detail-value">ID: {{ str_pad($transaction->approved_by, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Approved At:</span>
                <span class="detail-value">{{ $transaction->approved_at->format('d/m/Y H:i') }}</span>
            </div>
            @endif
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            @if($qrCodeImage)
                <img src="{{ $qrCodeImage }}" alt="Transaction QR Code" class="qr-code">
            @else
                <div class="qr-placeholder" style="width: 25mm; height: 25mm; border: 1px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 6pt; text-align: center;">
                    QR Code unavailable
                </div>
            @endif
            <div class="qr-text">Scan to verify transaction</div>
        </div>

        <!-- Barcode Section -->
        <div class="barcode-section">
            @if($barcodeImage)
                <img src="{{ $barcodeImage }}" alt="Transaction Barcode" class="barcode-code">
            @else
                <div class="barcode-placeholder" style="height: 10mm; background: repeating-linear-gradient(90deg, #000 0px, #000 1px, #fff 1px, #fff 2px); margin: 0 auto; width: 60mm;"></div>
            @endif
            <div class="barcode-text">{{ $barcodeText ?? str_pad($transaction->id, 10, '0', STR_PAD_LEFT) }}</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-notice">
                This receipt is computer-generated and<br>
                requires no signature
            </div>
            <div class="bnm-notice">
                <strong>BNM COMPLIANCE NOTICE</strong><br>
                This transaction is recorded in accordance with<br>
                Bank Negara Malaysia AML/CFT regulations.<br>
                Transaction ID: {{ $transaction->id }}<br>
                CDD Level: {{ $transaction->cdd_level }}
            </div>
            <div style="font-size: 7pt; margin-top: 2mm;">
                CEMS-MY v1.0 - MSB Management System
            </div>
        </div>

        <div class="cut-line">---------- CUT HERE ----------</div>
    </div>

    <!-- Print Button (only visible on screen) -->
    <div class="no-print" style="text-align: center; margin: 20px; font-family: sans-serif;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
