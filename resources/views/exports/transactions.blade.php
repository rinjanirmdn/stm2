<table>
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
            <th>ARRIVAL STATUS</th>
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
                <td>{{ $transaction->warehouse_name ?? '-' }}</td>
                <td>{{ ucfirst($transaction->direction ?? '-') }}</td>
                <td>{{ $transaction->arrival_time ? \Carbon\Carbon::parse($transaction->arrival_time)->format('d M Y H:i') : '-' }}</td>
                <td>{{ $transaction->lead_time ?? '-' }}</td>
                <td>{{ ucfirst($transaction->target_status ?? '-') }}</td>
                <td>{{ $transaction->is_late ? 'Late' : 'On Time' }}</td>
                <td>{{ $transaction->created_by_username ?? $transaction->created_by_nik ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
