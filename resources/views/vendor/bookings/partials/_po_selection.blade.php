<!-- PO Selection Section -->
<div class="cb-section cb-section--full">
    <h3 class="cb-section__title">
        <i class="fas fa-file-invoice"></i>
        PO/DO Selection
    </h3>

    <div class="cb-field">
        <label class="cb-label cb-label--required">PO/DO Number</label>
        <div class="cb-po-search">
            <input type="text"
                   id="po-search"
                   class="cb-input"
                   placeholder="Search PO/DO number..."
                   autocomplete="off"
                   value="{{ old('po_number') }}">
            <input type="hidden" name="po_number" id="po-number-hidden" value="{{ old('po_number') }}">
            <div class="cb-po-results" id="po-results"></div>
        </div>
        <div class="cb-loading" id="po-loading">
            <div class="cb-spinner"></div>
            <span>Searching...</span>
        </div>
        @error('po_number')
            <div class="cb-hint cb-hint--error">{{ $message }}</div>
        @enderror
    </div>

    <!-- PO Items Table -->
    <div id="po-items-container" class="cb-po-items-container">
        <label class="cb-label">PO Items</label>
        <table class="cb-po-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Material</th>
                    <th>PO Qty</th>
                    <th>GR Total</th>
                    <th>Remaining</th>
                    <th>Book Qty</th>
                </tr>
            </thead>
            <tbody id="po-items-body"></tbody>
        </table>
        <div class="cb-hint">Enter quantities for items you want to book.</div>
    </div>
</div>
