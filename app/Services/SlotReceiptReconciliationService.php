<?php

namespace App\Services;

use App\Models\PoItemGrCheckpoint;
use App\Models\SlotPoItemReceipt;
use App\Models\SlotPoItem;
use App\Services\PoSearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotReceiptReconciliationService
{
    public function __construct(
        private readonly PoSearchService $poSearchService,
    ) {}

    /**
     * Reconcile slot completion with SAP QtyGRTotal
     * - Fetch SAP QtyGRTotal for each PO+item
     * - Calculate delta vs checkpoint
     * - Store per-slot-item receipt
     * - If delta negative, apply LIFO reversal to recent slots
     */
    public function reconcileSlotCompletion(int $slotId): void
    {
        DB::transaction(function () use ($slotId) {
            $slotPoItems = SlotPoItem::where('slot_id', $slotId)->get();
            if ($slotPoItems->isEmpty()) {
                Log::warning("No PO items found for slot {$slotId} during reconciliation");
                return;
            }

            foreach ($slotPoItems as $slotItem) {
                $this->reconcileSlotItem($slotId, $slotItem);
            }
        });
    }

    /**
     * Reconcile a single slot PO item
     */
    private function reconcileSlotItem(int $slotId, SlotPoItem $slotItem): void
    {
        $poNumber = $slotItem->po_number;
        $itemNo = $slotItem->item_no;

        // 1) Get latest SAP QtyGRTotal
        $sapDetail = $this->poSearchService->getPoDetail($poNumber);
        if (! $sapDetail || ! isset($sapDetail['items'])) {
            Log::error("Failed to fetch SAP PO detail for {$poNumber} during reconciliation");
            return;
        }

        $sapItem = collect($sapDetail['items'])->firstWhere('item_no', $itemNo);
        if (! $sapItem) {
            Log::error("Item {$itemNo} not found in SAP PO {$poNumber}");
            return;
        }

        $sapQtyGrTotalNow = (float) ($sapItem['qty_gr_total'] ?? 0);

        // 2) Get or create checkpoint
        $checkpoint = PoItemGrCheckpoint::getOrCreate($poNumber, $itemNo);
        $checkpointLast = (float) $checkpoint->sap_qty_gr_total_last;

        // 3) Calculate delta
        $delta = $sapQtyGrTotalNow - $checkpointLast;

        // 4) Store receipt for this slot
        SlotPoItemReceipt::updateOrCreate(
            ['slot_id' => $slotId, 'po_number' => $poNumber, 'item_no' => $itemNo],
            [
                'qty_received' => max(0, $delta), // store positive delta as received
                'sap_qty_gr_total_after' => $sapQtyGrTotalNow,
            ]
        );

        // 5) If delta negative (reversal), apply LIFO to recent slots
        if ($delta < 0) {
            $this->applyLifoReversal($poNumber, $itemNo, abs($delta));
        }

        // 6) Update checkpoint to SAP latest
        $checkpoint->updateCheckpoint($sapQtyGrTotalNow);
    }

    /**
     * Apply LIFO reversal to recent slots for a PO+item
     */
    private function applyLifoReversal(string $poNumber, string $itemNo, float $reversalAmount): void
    {
        // Get recent slots with receipts for this PO+item, ordered by completed_at desc (LIFO)
        $recentReceipts = SlotPoItemReceipt::select('slot_po_item_receipts.*')
            ->join('slots', 'slots.id', '=', 'slot_po_item_receipts.slot_id')
            ->where('slot_po_item_receipts.po_number', $poNumber)
            ->where('slot_po_item_receipts.item_no', $itemNo)
            ->where('slot_po_item_receipts.qty_received', '>', 0)
            ->where('slots.status', 'completed')
            ->orderByDesc('slots.actual_finish')
            ->get();

        $remaining = $reversalAmount;

        foreach ($recentReceipts as $receipt) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($receipt->qty_received, $remaining);
            $newQty = $receipt->qty_received - $deduct;

            $receipt->update(['qty_received' => $newQty]);

            $remaining -= $deduct;

            Log::info("LIFO reversal applied", [
                'po' => $poNumber,
                'item' => $itemNo,
                'slot_id' => $receipt->slot_id,
                'deducted' => $deduct,
                'remaining_reversal' => $remaining,
            ]);
        }

        if ($remaining > 0) {
            Log::warning("LIFO reversal could not fully apply: remaining {$remaining} for PO {$poNumber} item {$itemNo}");
        }
    }
}
