<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?= $title ?></h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $field => $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="<?= $action ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <?php if (isset($return_url) && $return_url): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($return_url) ?>">
                        <?php endif; ?>
                        
                        <!-- Personal/Company Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?= htmlspecialchars($_SESSION['old']['full_name'] ?? $customer->full_name ?? '') ?>" 
                                       required
                                       minlength="2">
                                <div class="invalid-feedback">
                                    Please provide a valid name (minimum 2 characters).
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="company_name" 
                                       name="company_name" 
                                       value="<?= htmlspecialchars($_SESSION['old']['company_name'] ?? $customer->company_name ?? '') ?>">
                                <small class="text-muted">Optional</small>
                            </div>
                        </div>

                        <!-- Address Line 1 & 2 -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="address_line_1" 
                                       name="address_line_1" 
                                       value="<?= htmlspecialchars($_SESSION['old']['address_line_1'] ?? $customer->address_line_1 ?? '') ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Please provide an address.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="address_line_2" class="form-label">Address Line 2</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="address_line_2" 
                                       name="address_line_2" 
                                       value="<?= htmlspecialchars($_SESSION['old']['address_line_2'] ?? $customer->address_line_2 ?? '') ?>">
                                <small class="text-muted">Apartment, suite, etc. (optional)</small>
                            </div>
                        </div>

                        <!-- City, State, Zip -->
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="city" 
                                       name="city" 
                                       value="<?= htmlspecialchars($_SESSION['old']['city'] ?? $customer->city ?? '') ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Please provide a city.
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-select" id="state" name="state" required>
                                    <option value="">Choose...</option>
                                    <?php
                                    $states = [
                                        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                                        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                                        'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                                        'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                                        'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                                        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                                        'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                                        'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                                        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                                        'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                                        'WI' => 'Wisconsin', 'WY' => 'Wyoming'
                                    ];
                                    $selectedState = $_SESSION['old']['state'] ?? $customer->state ?? '';
                                    foreach ($states as $code => $name):
                                    ?>
                                        <option value="<?= $code ?>" <?= $selectedState === $code ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a state.
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="zip_code" 
                                       name="zip_code" 
                                       value="<?= htmlspecialchars($_SESSION['old']['zip_code'] ?? $customer->zip_code ?? '') ?>" 
                                       pattern="[0-9]{5}(-[0-9]{4})?" 
                                       placeholder="12345 or 12345-6789"
                                       required>
                                <div class="invalid-feedback">
                                    Please provide a valid zip code.
                                </div>
                            </div>
                        </div>

                        <!-- Phone and Status -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       value="<?= htmlspecialchars($_SESSION['old']['phone_number'] ?? $customer->phone_number ?? '') ?>" 
                                       placeholder="(555) 123-4567">
                                <small class="text-muted">Optional</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="customer_status" class="form-label">Status</label>
                                <select class="form-select" id="customer_status" name="customer_status">
                                    <?php $selectedStatus = $_SESSION['old']['customer_status'] ?? $customer->customer_status ?? 'active'; ?>
                                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <hr class="my-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?= $customer ? 'Update' : 'Create' ?> Customer
                                </button>
                                <?php 
                                $cancelUrl = isset($return_url) && $return_url ? htmlspecialchars($return_url) : '/azteamcrm/customers';
                                ?>
                                <a href="<?= $cancelUrl ?>" class="btn btn-secondary ms-2">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php unset($_SESSION['old']); ?>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Phone number formatting
document.getElementById('phone_number').addEventListener('input', function (e) {
    var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
    e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>