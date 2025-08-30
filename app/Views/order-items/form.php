<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><?= $orderItem ? 'Edit Order Item' : 'Add Order Item' ?></h1>
    <a href="/azteamcrm/orders/<?= $order->order_id ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Order
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Order #<?= $order->order_id ?> - Customer: <?= htmlspecialchars($customer ? $customer->full_name : 'N/A') ?></h5>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <?php 
        $old = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
        
        // Parse customization areas for editing
        $customizationAreas = [];
        if ($orderItem && $orderItem->custom_area) {
            $customizationAreas = explode(',', $orderItem->custom_area);
        } elseif (isset($old['custom_area'])) {
            $customizationAreas = explode(',', $old['custom_area']);
        }
        ?>

        <form method="POST" action="<?= $orderItem ? '/azteamcrm/order-items/' . $orderItem->order_item_id . '/update' : '/azteamcrm/orders/' . $order->order_id . '/order-items/store' ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="product_description" class="form-label">Product Description <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="product_description" 
                           name="product_description" 
                           value="<?= htmlspecialchars($old['product_description'] ?? $orderItem->product_description ?? '') ?>" 
                           required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="product_type" class="form-label">Product Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="product_type" name="product_type" required>
                        <option value="">Select Product Type</option>
                        <?php
                        $productTypes = [
                            'shirt' => 'Shirt',
                            'apron' => 'Apron',
                            'scrub' => 'Scrub',
                            'hat' => 'Hat',
                            'bag' => 'Bag',
                            'beanie' => 'Beanie',
                            'business_card' => 'Business Card',
                            'yard_sign' => 'Yard Sign',
                            'car_magnet' => 'Car Magnet',
                            'greeting_card' => 'Greeting Card',
                            'door_hanger' => 'Door Hanger',
                            'magnet_business_card' => 'Magnet Business Card'
                        ];
                        $selectedType = $old['product_type'] ?? $orderItem->product_type ?? '';
                        foreach ($productTypes as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="product_size" class="form-label">Size <span class="text-danger">*</span></label>
                    <select class="form-select" id="product_size" name="product_size" required>
                        <option value="">Select Size</option>
                        <optgroup label="Child Sizes">
                            <?php
                            $childSizes = ['child_xs' => 'Child XS', 'child_s' => 'Child S', 'child_m' => 'Child M', 'child_l' => 'Child L', 'child_xl' => 'Child XL'];
                            $selectedSize = $old['product_size'] ?? $orderItem->product_size ?? '';
                            foreach ($childSizes as $value => $label):
                            ?>
                                <option value="<?= $value ?>" <?= $selectedSize === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Adult Sizes">
                            <?php
                            $adultSizes = ['xs' => 'XS', 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL', 'xxl' => 'XXL', 'xxxl' => 'XXXL', 'xxxxl' => 'XXXXL'];
                            foreach ($adultSizes as $value => $label):
                            ?>
                                <option value="<?= $value ?>" <?= $selectedSize === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control" 
                           id="quantity" 
                           name="quantity" 
                           min="1" 
                           value="<?= htmlspecialchars($old['quantity'] ?? $orderItem->quantity ?? '1') ?>" 
                           required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="unit_price" class="form-label">Unit Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" 
                               class="form-control" 
                               id="unit_price" 
                               name="unit_price"
                               step="0.01"
                               min="0" 
                               value="<?= htmlspecialchars($old['unit_price'] ?? $orderItem->unit_price ?? '') ?>"
                               placeholder="0.00"
                               required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="custom_method" class="form-label">Customization Method <span class="text-danger">*</span></label>
                    <select class="form-select" id="custom_method" name="custom_method" required>
                        <option value="">Select Method</option>
                        <?php
                        $methods = [
                            'htv' => 'HTV (Heat Transfer Vinyl)',
                            'dft' => 'DFT (Direct Film Transfer)',
                            'embroidery' => 'Embroidery',
                            'sublimation' => 'Sublimation',
                            'printing_services' => 'Printing Services'
                        ];
                        $selectedMethod = $old['custom_method'] ?? $orderItem->custom_method ?? '';
                        foreach ($methods as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $selectedMethod === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customization Areas</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="customization_area_front" 
                                   name="customization_area_front"
                                   <?= in_array('front', $customizationAreas) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="customization_area_front">
                                Front
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="customization_area_back" 
                                   name="customization_area_back"
                                   <?= in_array('back', $customizationAreas) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="customization_area_back">
                                Back
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="customization_area_sleeve" 
                                   name="customization_area_sleeve"
                                   <?= in_array('sleeve', $customizationAreas) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="customization_area_sleeve">
                                Sleeve
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($orderItem): ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="order_item_status" class="form-label">Item Status</label>
                    <select class="form-select" id="order_item_status" name="order_item_status">
                        <?php
                        $itemStatuses = [
                            'pending' => 'Pending',
                            'artwork_sent_for_approval' => 'Artwork Sent for Approval',
                            'artwork_approved' => 'Artwork Approved',
                            'nesting_digitalization_done' => 'Nesting/Digitalization Done',
                            'completed' => 'Completed'
                        ];
                        foreach ($itemStatuses as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $orderItem->order_item_status === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="supplier_status" class="form-label">Supplier Status</label>
                    <select class="form-select" id="supplier_status" name="supplier_status">
                        <?php
                        $supplierStatuses = [
                            'awaiting_order' => 'Awaiting Order',
                            'order_made' => 'Order Made',
                            'order_arrived' => 'Order Arrived',
                            'order_delivered' => 'Order Delivered'
                        ];
                        foreach ($supplierStatuses as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $orderItem->supplier_status === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="note_item" class="form-label">Special Instructions / Notes</label>
                <textarea class="form-control" 
                          id="note_item" 
                          name="note_item" 
                          rows="3"
                          placeholder="Any special instructions or notes for this item"><?= htmlspecialchars($old['note_item'] ?? $orderItem->note_item ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?= $orderItem ? 'Update' : 'Add' ?> Order Item
                </button>
                <a href="/azteamcrm/orders/<?= $order->order_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Disable size options based on product type
    const productTypeSelect = document.getElementById('product_type');
    const sizeSelect = document.getElementById('product_size');
    
    productTypeSelect.addEventListener('change', function() {
        const nonApparelTypes = ['business_card', 'yard_sign', 'car_magnet', 'greeting_card', 'door_hanger', 'magnet_business_card'];
        
        if (nonApparelTypes.includes(this.value)) {
            // For non-apparel items, set size to N/A or disable
            sizeSelect.disabled = true;
            sizeSelect.value = '';
        } else {
            sizeSelect.disabled = false;
        }
    });
    
    // Trigger change event on page load
    productTypeSelect.dispatchEvent(new Event('change'));
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>