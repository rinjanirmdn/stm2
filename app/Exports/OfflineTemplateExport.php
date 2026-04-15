<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OfflineTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            // Dummy record to show format
            [
                'unplanned', 'INBOUND', '01-04-2026 13:00', '01-04-2026 13:30', '01-04-2026 15:00',
                'POC-12345', 'B 1234 ABC', 'Budi', 'PT. Vendor A', 'CDE', 'GATE 1', 'Catatan manual',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Slot Type (planned/unplanned)',
            'Direction (inbound/outbound)',
            'Arrival Time (DD-MM-YYYY HH:mm)',
            'Start Time (DD-MM-YYYY HH:mm)',
            'Finish Time (DD-MM-YYYY HH:mm)',
            'PO Number',
            'Vehicle Number',
            'Driver Name',
            'Vendor Name',
            'Warehouse Code',
            'Gate Number',
            'Notes',
        ];
    }
}
