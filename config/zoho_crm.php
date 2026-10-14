<?php

return [
    'enabled' => (bool) env('ZOHO_CRM_ENABLED', false),
    'accounts_url' => env('ZOHO_CRM_ACCOUNTS_URL', env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com')),
    'api_base' => env('ZOHO_CRM_API_BASE', 'https://www.zohoapis.com/crm/v2'),
    'client_id' => env('ZOHO_CRM_CLIENT_ID'),
    'client_secret' => env('ZOHO_CRM_CLIENT_SECRET'),
    'refresh_token' => env('ZOHO_CRM_REFRESH_TOKEN'),

    // Module API names in Zoho CRM (customize to match your org)
    'modules' => [
        'contact' => env('ZOHO_CRM_MODULE_CONTACT', 'Contacts'),
        'deal' => env('ZOHO_CRM_MODULE_DEAL', 'Deals'),
        'invoice' => env('ZOHO_CRM_MODULE_INVOICE', 'Invoices'),
        'receipt' => env('ZOHO_CRM_MODULE_RECEIPT', 'Receipts'),
    ],

    // Custom field API names on Invoice / Receipt / Deal modules
    'fields' => [
        'website_invoice_id' => env('ZOHO_CRM_FIELD_INVOICE_ID', 'Website_Invoice_ID'),
        'website_receipt_id' => env('ZOHO_CRM_FIELD_RECEIPT_ID', 'Website_Receipt_ID'),
        'installment_no' => env('ZOHO_CRM_FIELD_INSTALLMENT_NO', 'Installment_No'),
        'balance_due' => env('ZOHO_CRM_FIELD_BALANCE_DUE', 'Balance_Due'),
        'package_name' => env('ZOHO_CRM_FIELD_PACKAGE_NAME', 'Package_Name'),
        'gateway' => env('ZOHO_CRM_FIELD_GATEWAY', 'Gateway'),
        'invoice_status' => env('ZOHO_CRM_FIELD_INVOICE_STATUS', 'Status'),
        'linked_invoice' => env('ZOHO_CRM_FIELD_LINKED_INVOICE', 'Website_Invoice_ID'),
    ],
];
