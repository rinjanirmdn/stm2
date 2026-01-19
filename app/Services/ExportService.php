<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ExportService
{
    /**
     * Export data to Excel format
     */
    public function exportToExcel(Collection $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            echo $this->buildExcelHtml($data);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    /**
     * Export data to CSV format
     */
    public function exportToCsv(Collection $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, [
                'Type', 'PO', 'Ticket', 'MAT DOC', 'Vendor', 'Warehouse',
                'Direction', 'Arrival', 'Lead Time', 'Target Status', 'Late?', 'User'
            ]);

            // Data rows
            foreach ($data as $row) {
                fputcsv($out, [
                    (string) ($row->slot_type ?? 'planned'),
                    (string) ($row->truck_number ?? ''),
                    (string) ($row->ticket_number ?? ''),
                    (string) ($row->mat_doc ?? ''),
                    (string) ($row->vendor_name ?? ''),
                    (string) ($row->warehouse_name ?? ''),
                    (string) ($row->direction ?? ''),
                    (string) ($row->arrival_time ?? ''),
                    $this->calculateLeadTime($row),
                    $this->getTargetStatus($row),
                    $this->getLateStatus($row),
                    (string) ($row->created_by_username ?? ''),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Build Excel HTML content
     */
    private function buildExcelHtml(Collection $data): string
    {
        $html = $this->getExcelHeader();
        $html .= $this->buildExcelTable($data);
        $html .= $this->getExcelFooter();

        return $html;
    }

    /**
     * Get Excel HTML header with styles
     */
    private function getExcelHeader(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transactions Report</title>
    <style>
        @page {
            margin: 0.5in;
            orientation: landscape;
        }
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            color: #2E7D32;
            margin: 0;
        }
        .header p {
            font-size: 10pt;
            color: #666;
            margin: 5px 0 0 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        col {
            mso-width-source: userset;
        }
        col:nth-child(1) { width: 80px; }  /* Type */
        col:nth-child(2) { width: 120px; } /* PO/DO */
        col:nth-child(3) { width: 100px; } /* Ticket */
        col:nth-child(4) { width: 100px; } /* MAT DOC */
        col:nth-child(5) { width: 150px; } /* Vendor */
        col:nth-child(6) { width: 100px; } /* Warehouse */
        col:nth-child(7) { width: 80px; }  /* Direction */
        col:nth-child(8) { width: 130px; } /* Arrival */
        col:nth-child(9) { width: 90px; }  /* Lead Time */
        col:nth-child(10) { width: 100px; } /* Target Status */
        col:nth-child(11) { width: 60px; } /* Late? */
        col:nth-child(12) { width: 100px; } /* User */
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .late {
            color: #d32f2f;
            font-weight: bold;
            text-align: center;
        }
        .on-time {
            color: #388e3c;
            font-weight: bold;
            text-align: center;
        }
        .center {
            text-align: center;
        }
        .date {
            mso-number-format: "dd\/mm\/yyyy\ hh:mm";
        }
        .number {
            mso-number-format: "0";
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TRANSACTIONS REPORT - COMPLETED SLOTS</h1>
        <p>Generated: ' . date('d M Y H:i:s') . '</p>
        <p>All Dates</p>
    </div>';
    }

    /**
     * Build Excel table content
     */
    private function buildExcelTable(Collection $data): string
    {
        $html = '<table>
        <colgroup>
            <col><col><col><col><col><col><col><col><col><col><col><col>
        </colgroup>
        <thead>
            <tr>
                <th>Type</th>
                <th>PO/DO Number</th>
                <th>Ticket</th>
                <th>MAT DOC</th>
                <th>Vendor</th>
                <th>Warehouse</th>
                <th>Direction</th>
                <th>Arrival</th>
                <th>Lead Time</th>
                <th>Target Status</th>
                <th>Late?</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($data as $row) {
            $html .= $this->buildExcelRow($row);
        }

        $html .= '
        </tbody>
    </table>';

        return $html;
    }

    /**
     * Build single Excel row
     */
    private function buildExcelRow($row): string
    {
        $typeLabel = ((string) ($row->slot_type ?? 'planned')) === 'unplanned' ? 'Unplanned' : 'Planned';
        $lateDisplay = $this->determineLateDisplay($row);
        $lateClass = $lateDisplay === 'late' ? 'late' : 'on-time';
        $lateText = $lateDisplay === 'late' ? 'Late' : 'On Time';

        $leadTimeMinutes = (int) $this->calculateLeadTime($row) ?: null;
        $minutesLabel = $this->formatDuration($leadTimeMinutes);
        $fmt = $this->formatDateTime($row->arrival_time);

        return '<tr>
            <td class="center">' . htmlspecialchars($typeLabel) . '</td>
            <td>' . htmlspecialchars((string) ($row->po_number ?? $row->truck_number ?? '')) . '</td>
            <td>' . htmlspecialchars((string) ($row->ticket_number ?? '')) . '</td>
            <td>' . htmlspecialchars((string) ($row->mat_doc ?? '')) . '</td>
            <td>' . htmlspecialchars((string) ($row->vendor_name ?? '')) . '</td>
            <td class="center">' . htmlspecialchars((string) ($row->warehouse_code ?? '')) . '</td>
            <td class="center">' . htmlspecialchars(ucfirst((string) ($row->direction ?? ''))) . '</td>
            <td class="date">' . $fmt . '</td>
            <td>' . htmlspecialchars($minutesLabel) . '</td>
            <td class="center">' . htmlspecialchars(ucfirst((string) ($row->target_status ?? ''))) . '</td>
            <td class="' . $lateClass . '">' . htmlspecialchars($lateText) . '</td>
            <td>' . htmlspecialchars((string) ($row->created_by_username ?? '')) . '</td>
        </tr>';
    }

    /**
     * Get Excel HTML footer
     */
    private function getExcelFooter(): string
    {
        return '
</body>
</html>';
    }

    /**
     * Calculate lead time for a row
     */
    private function calculateLeadTime($row): string
    {
        try {
            $end = !empty($row->actual_finish) ? new \DateTime((string) $row->actual_finish) : null;
            $startStr = !empty($row->actual_start) ? (string) $row->actual_start : (!empty($row->arrival_time) ? (string) $row->arrival_time : '');
            $start = $startStr !== '' ? new \DateTime($startStr) : null;

            if ($start && $end) {
                $diff = $start->diff($end);
                return (string) ((int) $diff->days * 24 * 60 + (int) $diff->h * 60 + (int) $diff->i);
            }
        } catch (\Throwable $e) {
            // Return empty on error
        }

        return '';
    }

    /**
     * Get target status for a row
     */
    private function getTargetStatus($row): string
    {
        $targetMinutes = isset($row->target_duration_minutes) ? (int) $row->target_duration_minutes : 0;
        $leadTime = $this->calculateLeadTime($row);

        if ($targetMinutes > 0 && $leadTime !== '') {
            $lt = (int) $leadTime;
            return $lt <= ($targetMinutes + 15) ? 'achieve' : 'not_achieve';
        }

        return '';
    }

    /**
     * Get late status for a row
     */
    private function getLateStatus($row): string
    {
        return !empty($row->is_late) ? 'late' : 'on_time';
    }

    /**
     * Determine late display status
     */
    private function determineLateDisplay($row): string
    {
        $slotTypeForLate = (string) ($row->slot_type ?? 'planned');

        if (!empty($row->arrival_time) && $slotTypeForLate === 'planned') {
            try {
                $p = new \DateTime((string) $row->planned_start);
                $p->modify('+15 minutes');
                $a = new \DateTime((string) $row->arrival_time);
                return $a > $p ? 'late' : 'on_time';
            } catch (\Throwable $e) {
                return 'on_time';
            }
        }

        return !empty($row->is_late) ? 'late' : 'on_time';
    }

    /**
     * Format duration for display
     */
    private function formatDuration(?int $minutes): string
    {
        if ($minutes === null) {
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

    /**
     * Format date time for display
     */
    private function formatDateTime($dateTime): string
    {
        if (empty($dateTime)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse((string) $dateTime)->format('d M Y H:i');
        } catch (\Throwable $e) {
            return (string) $dateTime;
        }
    }

    /**
     * Generate filename with timestamp
     */
    public function generateFilename(string $type, string $extension): string
    {
        $timestamp = date('Ymd_His');
        return "transactions_report_{$timestamp}.{$extension}";
    }

    /**
     * Export search suggestions to CSV
     */
    public function exportSearchSuggestions(Collection $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, ['Text', 'Type']);

            // Data rows
            foreach ($data as $item) {
                fputcsv($out, [
                    $item['text'] ?? '',
                    $this->determineSuggestionType($item)
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Determine suggestion type based on content
     */
    private function determineSuggestionType(array $item): string
    {
        $text = strtolower($item['text'] ?? '');

        if (strpos($text, 'po') !== false || preg_match('/^\d{6,}$/', $text)) {
            return 'PO/DO';
        } elseif (strpos($text, 'mat') !== false) {
            return 'MAT DOC';
        } elseif (in_array($text, ['inbound', 'outbound'])) {
            return 'Direction';
        } elseif (strlen($text) >= 10 && preg_match('/[a-zA-Z]/', $text)) {
            return 'Vendor';
        } else {
            return 'Other';
        }
    }
}
