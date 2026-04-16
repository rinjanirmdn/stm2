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
            // Row 2: Example data
            [
                'unplanned',
                'inbound',
                'CDD/CDE',
                '15-04-2026 08:00',
                '15-04-2026 08:30',
                '15-04-2026 10:00',
                'POC-12345',
                'SJ-6829316',
                'B 1234 ABC',
                'Budi Santoso',
                '081234567890',
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
            'Slot Type *',
            'Direction *',
            'Truck Type *',
            'Arrival Time *',
            'Start Time *',
            'Finish Time *',
            'PO Number *',
            'SJ Number',
            'Vehicle Number *',
            'Driver Name',
            'Driver Number',
            'Vendor Name *',
            'Warehouse Code *',
            'Gate Number *',
            'Notes',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 18,
            'C' => 25,
            'D' => 24,
            'E' => 24,
            'F' => 24,
            'G' => 18,
            'H' => 18,
            'I' => 20,
            'J' => 20,
            'K' => 20,
            'L' => 28,
            'M' => 22,
            'N' => 18,
            'O' => 36,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'O';
        $requiredCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'I', 'L', 'M', 'N'];
        $optionalCols = ['H', 'J', 'K', 'O'];

        // --- Row 1: Column Headers ---
        // Required columns header (soft blue)
        foreach ($requiredCols as $col) {
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A90D9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B0B0']]],
            ]);
        }

        // Optional columns header (lighter blue)
        foreach ($optionalCols as $col) {
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '333333'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E8F7']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B0B0']]],
            ]);
        }

        // --- Row 2: Example data (italic, light gray) ---
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '999999'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']]],
        ]);

        // Row heights
        $sheet->getRowDimension(1)->setRowHeight(32);

        // --- Dropdown Validations (from row 2 onwards) ---
        $this->applyDropdown($sheet, 'A', '"planned,unplanned"', 'Select: planned or unplanned');
        $this->applyDropdown($sheet, 'B', '"inbound,outbound"', 'Select: inbound or outbound');

        $truckTypes = DB::table('md_truck')->pluck('truck_type')->implode(',');
        if ($truckTypes) {
            $this->applyDropdown($sheet, 'C', '"'.$truckTypes.'"', 'Select truck type');
        }

        $whCodes = DB::table('md_warehouse')->pluck('wh_code')->implode(',');
        if ($whCodes) {
            $this->applyDropdown($sheet, 'M', '"'.$whCodes.'"', 'Select warehouse code');
        }

        $gateNumbers = DB::table('md_gates')->pluck('gate_number')->unique()->implode(',');
        if ($gateNumbers) {
            $this->applyDropdown($sheet, 'N', '"'.$gateNumbers.'"', 'Select gate number');
        }

        // --- Cell Comments ---
        $sheet->getComment('A1')->getText()->createTextRun("Wajib diisi\nPilih: planned atau unplanned");
        $sheet->getComment('B1')->getText()->createTextRun("Wajib diisi\nPilih: inbound atau outbound");
        $sheet->getComment('C1')->getText()->createTextRun("Wajib diisi\nPilih jenis truk dari dropdown");
        $sheet->getComment('D1')->getText()->createTextRun("Wajib diisi\nFormat: DD-MM-YYYY HH:mm");
        $sheet->getComment('E1')->getText()->createTextRun("Wajib diisi\nFormat: DD-MM-YYYY HH:mm");
        $sheet->getComment('F1')->getText()->createTextRun("Wajib diisi\nFormat: DD-MM-YYYY HH:mm");
        $sheet->getComment('G1')->getText()->createTextRun("Wajib diisi\nNomor PO/DO");
        $sheet->getComment('H1')->getText()->createTextRun("Opsional\nNomor SJ (Surat Jalan)");
        $sheet->getComment('I1')->getText()->createTextRun("Wajib diisi\nNomor plat kendaraan");
        $sheet->getComment('J1')->getText()->createTextRun("Opsional\nNama pengemudi");
        $sheet->getComment('K1')->getText()->createTextRun("Opsional\nNomor telepon pengemudi (contoh: 0812...)");
        $sheet->getComment('L1')->getText()->createTextRun("Wajib diisi\nNama vendor / customer");
        $sheet->getComment('M1')->getText()->createTextRun("Wajib diisi\nPilihan: {$whCodes}");
        $sheet->getComment('N1')->getText()->createTextRun("Wajib diisi\nPilihan: {$gateNumbers}");
        $sheet->getComment('O1')->getText()->createTextRun("Opsional\nCatatan tambahan");

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }

    private function applyDropdown(Worksheet $sheet, string $col, string $formula, string $errorMsg): void
    {
        for ($i = 2; $i <= 100; $i++) {
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
