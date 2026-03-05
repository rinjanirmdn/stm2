<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as SAP integrations. Unused default Laravel service configs have been
    | removed per audit #46.
    |
    */

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
        // Example: /ZPO_HDR_VNAME(po_no='4110002539')/Set
        'odata_detail_endpoint' => env('SAP_PO_ODATA_DETAIL_ENDPOINT', "/ZPO_HDR_VNAME(po_no='{po}')/Set"),
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
