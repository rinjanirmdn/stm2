<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sap_po' => [
        'base_url' => env('SAP_PO_BASE_URL', ''),
        'service_path' => env('SAP_PO_SERVICE_PATH', '/sap/opu/odata4/sap/zmm_oji_po_bind/srvd/sap/zmm_oji_po/0001'),
        'token' => env('SAP_PO_TOKEN', ''),
        'username' => env('SAP_PO_USERNAME', ''),
        'password' => env('SAP_PO_PASSWORD', ''),
        'search_endpoint' => env('SAP_PO_SEARCH_ENDPOINT', '/po/search'),
        'detail_endpoint' => env('SAP_PO_DETAIL_ENDPOINT', '/po/{po}'),
        'sap_client' => env('SAP_PO_SAP_CLIENT', '210'),
        'verify_ssl' => env('SAP_PO_VERIFY_SSL', false),

        // OData v4 support (when SAP exposes PO items via entity path)
        // Example: /ZPOA_DTL_LIST(po_no='4170005027')/Set
        'odata_detail_endpoint' => env('SAP_PO_ODATA_DETAIL_ENDPOINT', "/ZPOA_DTL_LIST(po_no='{po}')/Set"),
        'timeout' => (int) env('SAP_PO_TIMEOUT', 15),
    ],

    'sap_vendor' => [
        'base_url' => env('SAP_VENDOR_BASE_URL', ''),
        'service_path' => env('SAP_VENDOR_SERVICE_PATH', ''),
        'sap_client' => env('SAP_VENDOR_SAP_CLIENT', '210'),
        'token' => env('SAP_VENDOR_TOKEN', ''),
        'username' => env('SAP_VENDOR_USERNAME', ''),
        'password' => env('SAP_VENDOR_PASSWORD', ''),
        'verify_ssl' => env('SAP_VENDOR_VERIFY_SSL', false),
        'timeout' => (int) env('SAP_VENDOR_TIMEOUT', 15),
    ],

];
