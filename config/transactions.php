<?php

return [
    'batch' => [
        // Maximum number of data rows accepted per batch-import upload. The
        // import runs synchronously inside the request, so this cap keeps the
        // job within the request timeout and prevents stuck 'Processing' runs.
        'max_rows' => (int) env('TRANSACTION_BATCH_MAX_ROWS', 5000),
    ],

    'import' => [
        'max_amount_foreign' => env('TRANSACTION_IMPORT_MAX_AMOUNT_FOREIGN', '1000000'),
        'max_rate' => env('TRANSACTION_IMPORT_MAX_RATE', '100'),
    ],
];
