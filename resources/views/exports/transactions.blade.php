<table class="st-table st-table--sm">
    <thead>
        <tr>
            <th>Type</th>
            <th>PO/SO Number</th>
            <th>Ticket</th>
            <th>SJ</th>
            <th>Vendor</th>
            <th>Truck Type</th>
            <th>Vehicle Number</th>
            <th>Warehouse</th>
            <th>Gate</th>
            <th>Direction</th>
            <th>Arrival</th>
            <th>Actual Start</th>
            <th>Actual Finish</th>
            <th>Lead Time</th>
            <th>Target Status</th>
            <th>Arrival Status</th>
            <th>Status</th>
            <th>User</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
            <tr>
                <td>{{ ucfirst($transaction->slot_type ?? 'planned') }}</td>
                <td>{{ $transaction->truck_number ?? '-' }}</td>
                <td>{{ $transaction->ticket_number ?? '-' }}</td>
                <td>{{ $transaction->mat_doc ?? '-' }}</td>
                <td>{{ $transaction->vendor_name ?? '-' }}</td>
                <td>{{ $transaction->truck_type ?? '-' }}</td>
                <td>{{ $transaction->vehicle_number_snap ?? '-' }}</td>
                <td>{{ $transaction->warehouse_name ?? '-' }}</td>
                <td>{{ $transaction->gate_number ?? '-' }}</td>
                <td>{{ ucfirst($transaction->direction ?? '-') }}</td>
                <td>{{ $transaction->arrival_time ? \Carbon\Carbon::parse($transaction->arrival_time)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ !empty($transaction->actual_start) ? \Carbon\Carbon::parse($transaction->actual_start)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ !empty($transaction->actual_finish) ? \Carbon\Carbon::parse($transaction->actual_finish)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ $transaction->lead_time ?? '-' }}</td>
                <td>{{ ucfirst($transaction->target_status ?? '-') }}</td>
                <td>{{ $transaction->is_late ? 'Late' : 'On Time' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $transaction->status ?? '-')) }}</td>
                <td>{{ $transaction->created_by_username ?? $transaction->created_by_nik ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
