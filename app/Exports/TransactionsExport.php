<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
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
            $end = !empty($transaction->actual_finish) ? new \DateTime($transaction->actual_finish) : null;
            $startStr = !empty($transaction->actual_start) ? $transaction->actual_start : (!empty($transaction->arrival_time) ? $transaction->arrival_time : '');
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
            $end = !empty($transaction->actual_finish) ? new \DateTime($transaction->actual_finish) : null;
            $startStr = !empty($transaction->actual_start) ? $transaction->actual_start : (!empty($transaction->arrival_time) ? $transaction->arrival_time : '');
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
        $out = $m . ' min';

        if ($h >= 1) {
            $out .= ' (' . rtrim(rtrim(number_format($h, 2), '0'), '.') . ' h)';
        }

        return $out;
    }

    public function view(): View
    {
        return view('exports.transactions', [
            'transactions' => $this->transactions
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Type
            'B' => 18,  // PO/DO Number
            'C' => 15,  // Ticket
            'D' => 12,  // MAT DOC
            'E' => 25,  // Vendor
            'F' => 15,  // Warehouse
            'G' => 10,  // Direction
            'H' => 18,  // Arrival
            'I' => 12,  // Lead Time
            'J' => 15,  // Target Status
            'K' => 8,   // Late?
            'L' => 15,  // User
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text with background
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50'],
                ],
            ],
        ];
    }
}
