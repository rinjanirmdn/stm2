<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
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
            // Row 2: Required / Optional indicator
            [
                'Required', // A: Slot Type
                'Required', // B: Direction
                'Required', // C: Truck Type
                'Required', // D: Arrival
                'Required', // E: Start
                'Required', // F: Finish
                'Required', // G: PO
                'Required', // H: Vehicle
                'Required', // I: Driver
                'Required', // J: Vendor
                'Required', // K: WH
                'Required', // L: Gate
                'Optional', // M: Notes
            ],
            // Row 3: Example data
            [
                'unplanned',
                'inbound',
                'CDD/CDE',
                '15-04-2026 08:00',
                '15-04-2026 08:30',
                '15-04-2026 10:00',
                'POC-12345',
                'B 1234 ABC',
                'Budi Santoso',
                'PT. Vendor Example',
                'WH1',
                'A',
                'Recorded during power outage',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Slot Type',
            'Direction',
            'Truck Type',
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
            'A' => 20,
            'B' => 18,
            'C' => 28,
            'D' => 24,
            'E' => 24,
            'F' => 24,
            'G' => 18,
            'H' => 20,
            'I' => 20,
            'J' => 28,
            'K' => 22,
            'L' => 18,
            'M' => 36,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'M';
        $requiredCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        $optionalCols = ['M'];

        // --- Row 1: Column Headers ---
        // Required columns header (dark blue)
        foreach ($requiredCols as $col) {
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B6B93']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
        }

        // Optional columns header (lighter teal)
        foreach ($optionalCols as $col) {
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C9EAD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
        }

        // --- Row 2: Required/Optional indicator row ---
        foreach ($requiredCols as $col) {
            $sheet->getStyle("{$col}2")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        foreach ($optionalCols as $col) {
            $sheet->getStyle("{$col}2")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '333333']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8E8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // --- Row 3: Example data (italic, light gray) ---
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
        ]);

        // Row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // --- Dropdown Validations (from row 3 onwards) ---
        $this->applyDropdown($sheet, 'A', '"planned,unplanned"', 'Select: planned or unplanned');
        $this->applyDropdown($sheet, 'B', '"inbound,outbound"', 'Select: inbound or outbound');

        $truckTypes = DB::table('md_truck')->pluck('truck_type')->implode(',');
        if ($truckTypes) {
            $this->applyDropdown($sheet, 'C', '"'.$truckTypes.'"', 'Select truck type');
        }

        $whCodes = DB::table('md_warehouse')->pluck('wh_code')->implode(',');
        if ($whCodes) {
            $this->applyDropdown($sheet, 'K', '"'.$whCodes.'"', 'Select warehouse code');
        }

        $gateNumbers = DB::table('md_gates')->pluck('gate_number')->unique()->implode(',');
        if ($gateNumbers) {
            $this->applyDropdown($sheet, 'L', '"'.$gateNumbers.'"', 'Select gate number');
        }

        // --- Cell Comments ---
        $sheet->getComment('A1')->getText()->createTextRun("REQUIRED\nSelect: planned or unplanned");
        $sheet->getComment('B1')->getText()->createTextRun("REQUIRED\nSelect: inbound or outbound");
        $sheet->getComment('C1')->getText()->createTextRun("REQUIRED\nSelect truck type from dropdown");
        $sheet->getComment('D1')->getText()->createTextRun("REQUIRED\nFormat: DD-MM-YYYY HH:mm");
        $sheet->getComment('E1')->getText()->createTextRun("REQUIRED\nFormat: DD-MM-YYYY HH:mm");
        $sheet->getComment('F1')->getText()->createTextRun("REQUIRED\nFormat: DD-MM-YYYY HH:mm");
        $sheet->getComment('G1')->getText()->createTextRun("REQUIRED\nPO/DO Number");
        $sheet->getComment('H1')->getText()->createTextRun("REQUIRED\nTruck plate number");
        $sheet->getComment('I1')->getText()->createTextRun("REQUIRED\nDriver name");
        $sheet->getComment('J1')->getText()->createTextRun("REQUIRED\nVendor / Customer name");
        $sheet->getComment('K1')->getText()->createTextRun("REQUIRED\nValues: {$whCodes}");
        $sheet->getComment('L1')->getText()->createTextRun("REQUIRED\nValues: {$gateNumbers}");
        $sheet->getComment('M1')->getText()->createTextRun("OPTIONAL\nAny notes");

        // Freeze top 2 rows
        $sheet->freezePane('A3');

        return [];
    }

    private function applyDropdown(Worksheet $sheet, string $col, string $formula, string $errorMsg): void
    {
        for ($i = 3; $i <= 100; $i++) {
            $validation = $sheet->getCell("{$col}{$i}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setFormula1($formula);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Invalid');
            $validation->setError($errorMsg);
        }
    }
}
