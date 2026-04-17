<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromView, WithColumnWidths, WithStyles
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
        $this->calculateMetrics();
    }

    private function calculateMetrics()
    {
        foreach ($this->transactions as $transaction) {
            // Calculate lead time
            $transaction->lead_time = $this->calculateLeadTime($transaction);

            // Calculate target status
            $transaction->target_status = $this->getTargetStatus($transaction);
        }
    }

    private function calculateLeadTime($transaction): string
    {
        try {
            $end = ! empty($transaction->actual_finish) ? new \DateTime($transaction->actual_finish) : null;
            $startStr = ! empty($transaction->actual_start) ? $transaction->actual_start : (! empty($transaction->arrival_time) ? $transaction->arrival_time : '');
            $start = $startStr !== '' ? new \DateTime($startStr) : null;

            if ($start && $end) {
                $diff = $start->diff($end);
                $minutes = (int) $diff->days * 24 * 60 + (int) $diff->h * 60 + (int) $diff->i;

                return $this->formatDuration($minutes);
            }
        } catch (\Throwable $e) {
            // Return empty on error
        }

        return '-';
    }

    private function getTargetStatus($transaction): string
    {
        $targetMinutes = isset($transaction->target_duration_minutes) ? (int) $transaction->target_duration_minutes : 0;
        $leadTimeMinutes = $this->getLeadTimeMinutes($transaction);

        if ($targetMinutes > 0 && $leadTimeMinutes > 0) {
            return $leadTimeMinutes <= ($targetMinutes + 15) ? 'achieve' : 'not_achieve';
        }

        return '-';
    }

    private function getLeadTimeMinutes($transaction): int
    {
        try {
            $end = ! empty($transaction->actual_finish) ? new \DateTime($transaction->actual_finish) : null;
            $startStr = ! empty($transaction->actual_start) ? $transaction->actual_start : (! empty($transaction->arrival_time) ? $transaction->arrival_time : '');
            $start = $startStr !== '' ? new \DateTime($startStr) : null;

            if ($start && $end) {
                $diff = $start->diff($end);

                return (int) $diff->days * 24 * 60 + (int) $diff->h * 60 + (int) $diff->i;
            }
        } catch (\Throwable $e) {
            // Return 0 on error
        }

        return 0;
    }

    private function formatDuration(?int $minutes): string
    {
        if ($minutes === null || $minutes === 0) {
            return '-';
        }

        $m = $minutes;
        $h = $m / 60;
        $out = $m.' min';

        if ($h >= 1) {
            $out .= ' ('.rtrim(rtrim(number_format($h, 2), '0'), '.').' h)';
        }

        return $out;
    }

    public function view(): View
    {
        return view('exports.transactions', [
            'transactions' => $this->transactions,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Type
            'B' => 18,  // PO/DO Number
            'C' => 15,  // Ticket
            'D' => 16,  // SJ
            'E' => 28,  // Vendor
            'F' => 14,  // Truck Type
            'G' => 16,  // Vehicle Number
            'H' => 14,  // Warehouse
            'I' => 12,  // Gate
            'J' => 12,  // Direction
            'K' => 19,  // Arrival
            'L' => 19,  // Actual Start
            'M' => 19,  // Actual Finish
            'N' => 14,  // Lead Time
            'O' => 14,  // Target Status
            'P' => 14,  // Arrival Status
            'Q' => 12,  // Status
            'R' => 16,  // User
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'R';
        $rowCount = $this->transactions->count() + 1; // +1 for header

        // --- Row 1: Header ---
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B7DD8']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B0B0']],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // --- Data rows: borders + alignment ---
        if ($rowCount > 1) {
            // All data cells border
            $sheet->getStyle("A2:{$lastCol}{$rowCount}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center alignment for specific columns (Type, Direction, Gate, Status, Target, Late)
            $centerCols = ['A', 'I', 'J', 'O', 'P', 'Q'];
            foreach ($centerCols as $col) {
                $sheet->getStyle("{$col}2:{$col}{$rowCount}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Zebra striping (alternating rows)
            for ($r = 2; $r <= $rowCount; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F8FC']],
                    ]);
                }
            }
        }

        // Freeze header
        $sheet->freezePane('A2');

        return [];
    }
}
