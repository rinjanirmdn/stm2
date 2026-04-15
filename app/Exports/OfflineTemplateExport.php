<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfflineTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'unplanned',
                'inbound',
                '15-04-2026 08:00',
                '15-04-2026 08:30',
                '15-04-2026 10:00',
                'POC-12345',
                'B 1234 ABC',
                'Budi Santoso',
                'PT. Vendor Example',
                'CDE',
                'GATE 1',
                'Example: recorded during power outage',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Slot Type',
            'Direction',
            'Arrival Time',
            'Start Time',
            'Finish Time',
            'PO Number',
            'Vehicle Number',
            'Driver Name',
            'Vendor Name',
            'Warehouse Code',
            'Gate Number',
            'Notes',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Slot Type
            'B' => 16,  // Direction
            'C' => 22,  // Arrival Time
            'D' => 22,  // Start Time
            'E' => 22,  // Finish Time
            'F' => 18,  // PO Number
            'G' => 18,  // Vehicle Number
            'H' => 20,  // Driver Name
            'I' => 28,  // Vendor Name
            'J' => 20,  // Warehouse Code
            'K' => 16,  // Gate Number
            'L' => 40,  // Notes
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row with background color
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B6B93'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Example row styling (light gray italic)
        $sheet->getStyle('A2:L2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '666666'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2'],
            ],
        ]);

        // Add borders to header
        $sheet->getStyle('A1:L1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Add data validation dropdowns
        // Slot Type: planned / unplanned
        $validationSlotType = $sheet->getCell('A2')->getDataValidation();
        $validationSlotType->setType(DataValidation::TYPE_LIST);
        $validationSlotType->setFormula1('"planned,unplanned"');
        $validationSlotType->setAllowBlank(false);
        $validationSlotType->setShowDropDown(true);
        $validationSlotType->setShowErrorMessage(true);
        $validationSlotType->setErrorTitle('Invalid');
        $validationSlotType->setError('Please select: planned or unplanned');
        // Apply to rows 2-100
        for ($i = 3; $i <= 100; $i++) {
            $sheet->getCell("A{$i}")->setDataValidation(clone $validationSlotType);
        }

        // Direction: inbound / outbound
        $validationDirection = $sheet->getCell('B2')->getDataValidation();
        $validationDirection->setType(DataValidation::TYPE_LIST);
        $validationDirection->setFormula1('"inbound,outbound"');
        $validationDirection->setAllowBlank(false);
        $validationDirection->setShowDropDown(true);
        $validationDirection->setShowErrorMessage(true);
        $validationDirection->setErrorTitle('Invalid');
        $validationDirection->setError('Please select: inbound or outbound');
        for ($i = 3; $i <= 100; $i++) {
            $sheet->getCell("B{$i}")->setDataValidation(clone $validationDirection);
        }

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Add instructions comment on A1
        $sheet->getComment('A1')->getText()->createTextRun(
            'Fill: planned or unplanned'
        );
        $sheet->getComment('B1')->getText()->createTextRun(
            'Fill: inbound or outbound'
        );
        $sheet->getComment('C1')->getText()->createTextRun(
            "Format: DD-MM-YYYY HH:mm\nExample: 15-04-2026 08:00"
        );
        $sheet->getComment('D1')->getText()->createTextRun(
            "Format: DD-MM-YYYY HH:mm\nExample: 15-04-2026 08:30"
        );
        $sheet->getComment('E1')->getText()->createTextRun(
            "Format: DD-MM-YYYY HH:mm\nExample: 15-04-2026 10:00"
        );
        $sheet->getComment('J1')->getText()->createTextRun(
            'Use the warehouse code (e.g. CDE, FGH)'
        );
        $sheet->getComment('K1')->getText()->createTextRun(
            'Use the gate number (e.g. GATE 1, GATE A)'
        );

        return [];
    }
}
