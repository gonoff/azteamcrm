<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><?= $lineItem ? 'Edit Line Item' : 'Add Line Item' ?></h1>
    <a href="/azteamcrm/orders/<?= $order->id ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Order
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Order #<?= $order->id ?> - <?= htmlspecialchars($order->client_name) ?></h5>
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
        if ($lineItem) {
            $customizationAreas = explode(',', $lineItem->customization_areas);
        } elseif (isset($old['customization_areas'])) {
            $customizationAreas = explode(',', $old['customization_areas']);
        }
        ?>

        <form method="POST" action="<?= $lineItem ? '/azteamcrm/line-items/' . $lineItem->id . '/update' : '/azteamcrm/orders/' . $order->id . '/line-items/store' ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="product_description" class="form-label">Product Description <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="product_description" 
                           name="product_description" 
                           value="<?= htmlspecialchars($old['product_description'] ?? $lineItem->product_description ?? '') ?>" 
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
                        $selectedType = $old['product_type'] ?? $lineItem->product_type ?? '';
                        foreach ($productTypes as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="size" class="form-label">Size <span class="text-danger">*</span></label>
                    <select class="form-select" id="size" name="size" required>
                        <option value="">Select Size</option>
                        <optgroup label="Child Sizes">
                            <?php
                            $childSizes = ['child_xs' => 'Child XS', 'child_s' => 'Child S', 'child_m' => 'Child M', 'child_l' => 'Child L', 'child_xl' => 'Child XL'];
                            $selectedSize = $old['size'] ?? $lineItem->size ?? '';
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
                           value="<?= htmlspecialchars($old['quantity'] ?? $lineItem->quantity ?? '1') ?>" 
                           required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="color_specification" class="form-label">Color Specification</label>
                    <input type="text" 
                           class="form-control" 
                           id="color_specification" 
                           name="color_specification" 
                           value="<?= htmlspecialchars($old['color_specification'] ?? $lineItem->color_specification ?? '') ?>"
                           placeholder="e.g., Royal Blue, Red & White">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="customization_method" class="form-label">Customization Method <span class="text-danger">*</span></label>
                    <select class="form-select" id="customization_method" name="customization_method" required>
                        <option value="">Select Method</option>
                        <?php
                        $methods = [
                            'htv' => 'HTV (Heat Transfer Vinyl)',
                            'dft' => 'DFT (Direct Film Transfer)',
                            'embroidery' => 'Embroidery',
                            'sublimation' => 'Sublimation',
                            'printing_services' => 'Printing Services'
                        ];
                        $selectedMethod = $old['customization_method'] ?? $lineItem->customization_method ?? '';
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
            
            <?php if ($lineItem): ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="supplier_status" class="form-label">Supplier Status</label>
                    <select class="form-select" id="supplier_status" name="supplier_status">
                        <?php
                        $supplierStatuses = [
                            'awaiting_to_order' => 'Awaiting to Order',
                            'order_made' => 'Order Made',
                            'order_arrived' => 'Order Arrived',
                            'order_delivered' => 'Order Delivered'
                        ];
                        foreach ($supplierStatuses as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $lineItem->supplier_status === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="completion_status" class="form-label">Completion Status</label>
                    <select class="form-select" id="completion_status" name="completion_status">
                        <?php
                        $completionStatuses = [
                            'waiting_approval' => 'Waiting Approval',
                            'artwork_approved' => 'Artwork Approved',
                            'material_prepared' => 'Material Prepared',
                            'work_completed' => 'Work Completed'
                        ];
                        foreach ($completionStatuses as $value => $label):
                        ?>
                            <option value="<?= $value ?>" <?= $lineItem->completion_status === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="line_item_notes" class="form-label">Special Instructions / Notes</label>
                <textarea class="form-control" 
                          id="line_item_notes" 
                          name="line_item_notes" 
                          rows="3"
                          placeholder="Any special instructions or notes for this item"><?= htmlspecialchars($old['line_item_notes'] ?? $lineItem->line_item_notes ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?= $lineItem ? 'Update' : 'Add' ?> Line Item
                </button>
                <a href="/azteamcrm/orders/<?= $order->id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Disable size options based on product type
    const productTypeSelect = document.getElementById('product_type');
    const sizeSelect = document.getElementById('size');
    
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