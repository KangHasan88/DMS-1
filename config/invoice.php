<?php

return [
    'company' => [
        'code' => env('INVOICE_COMPANY_CODE', 'KMG'),
        'display_name' => env('INVOICE_COMPANY_DISPLAY_NAME', config('app.name', 'Kurmigo DMS')),
        'legal_name' => env('INVOICE_COMPANY_LEGAL_NAME', 'PT Kurmigo Distribusi Indonesia'),
        'npwp' => env('INVOICE_COMPANY_NPWP', '-'),
        'phone' => env('INVOICE_COMPANY_PHONE', '-'),
        'email' => env('INVOICE_COMPANY_EMAIL', '-'),
    ],

    'branch' => [
        'name' => env('INVOICE_BRANCH_NAME', 'Cabang Tangerang'),
        'code' => env('INVOICE_BRANCH_CODE', 'TNG'),
        'address' => env('INVOICE_BRANCH_ADDRESS', '-'),
        'phone' => env('INVOICE_BRANCH_PHONE', '-'),
    ],

    'document' => [
        'title' => env('INVOICE_DOCUMENT_TITLE', 'Invoice Order'),
        'subtitle' => env('INVOICE_DOCUMENT_SUBTITLE', 'Dokumen Invoice Order'),
    ],
];
