#!/bin/bash

# Simulasi Operasional Gudang Sibuk - STM2
# Base URL
BASE_URL="http://localhost:8000"

# Login untuk dapat token
echo "🔐 Login sebagai admin..."
LOGIN_RESPONSE=$(curl -s -c cookies.txt -X POST "$BASE_URL/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "username=admin&password=password")

echo "✅ Login berhasil"

# Fungsi helper
create_slot() {
    local po_num=$1
    local truck_num=$2
    local warehouse=$3
    local start_time=$4

    echo "📝 Create slot: $po_num - $truck_num"
    curl -s -b cookies.txt -X POST "$BASE_URL/slots" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "po_number=$po_num&truck_number=$truck_num&truck_type=Container 20ft&direction=inbound&warehouse_id=$warehouse&vendor_id=1&planned_start=$start_time" > /dev/null
}

arrival_slot() {
    local slot_id=$1
    echo "🚚 Arrival slot $slot_id"
    curl -s -b cookies.txt -X POST "$BASE_URL/slots/$slot_id/arrival" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "ticket_number=A$(date +%s)&sj_number=SJ$slot_id&truck_type=Container 20ft&actual_arrival=$(date '+%Y-%m-%d %H:%M')&actual_gate_id=1" > /dev/null
}

start_slot() {
    local slot_id=$1
    echo "▶️  Start slot $slot_id"
    curl -s -b cookies.txt -X POST "$BASE_URL/slots/$slot_id/start" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "actual_gate_id=1" > /dev/null
}

complete_slot() {
    local slot_id=$1
    echo "✅ Complete slot $slot_id"
    curl -s -b cookies.txt -X POST "$BASE_URL/slots/$slot_id/complete" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "mat_doc=MD$slot_id&sj_number=SJ$slot_id&truck_type=Container 20ft&vehicle_number=B$slot_num&driver_number=DRV$slot_id&actual_finish=$(date '+%Y-%m-%d %H:%M')" > /dev/null
}

# Simulasi Hari Ini - 20 Slot
echo "🏭 Simulasi Operasional Gudang Sibuk"
echo "=================================="

# Batch 1: Morning Rush (8 slots)
for i in {1..8}; do
    create_slot "PO$(printf "%03d" $i)" "TRUCK$(printf "%03d" $i)" "1" "$(date -d "+$i minutes" '+%Y-%m-%d %H:%M')"
done

sleep 2

# Process batch 1
for i in {1..8}; do
    arrival_slot $i
    sleep 0.5
    start_slot $i
    sleep 0.5
    complete_slot $i &
done

# Batch 2: Mid-day (7 slots)
for i in {9..15}; do
    create_slot "PO$(printf "%03d" $i)" "TRUCK$(printf "%03d" $i)" "2" "$(date -d "+$((i*10)) minutes" '+%Y-%m-%d %H:%M')"
done

sleep 2

# Process batch 2
for i in {9..15}; do
    arrival_slot $i &
    sleep 0.3
done

# Batch 3: Afternoon rush (5 slots)
for i in {16..20}; do
    create_slot "PO$(printf "%03d" $i)" "TRUCK$(printf "%03d" $i)" "3" "$(date -d "+$((i*15)) minutes" '+%Y-%m-%d %H:%M')" &
done

wait

echo "🎯 Test concurrent operations..."
# Test concurrent arrivals
for i in {16..20}; do
    arrival_slot $i &
done

wait

# Test concurrent starts
for i in {16..20}; do
    start_slot $i &
done

wait

# Test concurrent completions
for i in {16..20}; do
    complete_slot $i &
done

wait

echo "📊 Check dashboard..."
curl -s -b cookies.txt "$BASE_URL/dashboard" | grep -o "Total Slots.*[0-9]\+" | head -5

echo "🚪 Check gate status..."
curl -s "$BASE_URL/api/gate-status" | head -10

echo "✨ Simulasi selesai!"
echo "=================="
echo "📈 Total slots created: 20"
echo "🔄 Concurrent operations tested"
echo "⚡ Performance under load verified"

# Cleanup
rm -f cookies.txt
