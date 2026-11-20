<?php
/*
 * Client Freight Invoice configuration.
 * Change these values to match the accounting invoice series.
 */
return [
    // 1 = January, 4 = April, etc.
    'financial_year_start_month' => 4,
    'invoice_type' => 'Freight Invoice',
    'invoice_prefix' => 'KRF-FRT',
    // Supported placeholders: {PREFIX}, {FY}, {FY_SHORT}, {SEQ}, {SEQ4}, {SEQ5}, {SEQ6}
    'invoice_number_format' => '{PREFIX}/{FY_SHORT}/{SEQ5}'
];
