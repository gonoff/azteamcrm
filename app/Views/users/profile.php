<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="h2 mb-4">My Profile</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Profile Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 text-muted">Username:</div>
                    <div class="col-md-8"><strong><?= htmlspecialchars($user->username) ?></strong></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 text-muted">Full Name:</div>
                    <div class="col-md-8"><strong><?= htmlspecialchars($user->full_name) ?></strong></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 text-muted">Email:</div>
                    <div class="col-md-8"><strong><?= htmlspecialchars($user->email) ?></strong></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 text-muted">Role:</div>
                    <div class="col-md-8">
                        <?php if ($user->role === 'administrator'): ?>
                            <span class="badge badge-danger">Administrator</span>
                        <?php else: ?>
                            <span class="badge badge-primary">Production Team</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 text-muted">Account Status:</div>
                    <div class="col-md-8">
                        <?php if ($user->is_active): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4 text-muted">Member Since:</div>
                    <div class="col-md-8"><?= date('F d, Y', strtotime($user->created_at)) ?></div>
                </div>
                
                <?php if ($user->updated_at): ?>
                    <div class="row">
                        <div class="col-md-4 text-muted">Last Updated:</div>
                        <div class="col-md-8"><?= date('F d, Y g:i A', strtotime($user->updated_at)) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Change Password Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/azteamcrm/profile/update-password" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="current_password" 
                               name="current_password" 
                               required>
                        <div class="invalid-feedback">
                            Please enter your current password.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password" 
                               name="new_password" 
                               minlength="6"
                               required>
                        <div class="invalid-feedback">
                            New password must be at least 6 characters.
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               minlength="6"
                               required>
                        <div class="invalid-feedback">
                            Please confirm your new password.
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        After changing your password, you will remain logged in with your current session.
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswordMatch() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
                confirmPassword.classList.add('is-invalid');
                
                // Update feedback message
                const feedback = confirmPassword.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Passwords do not match.';
                }
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('is-invalid');
                
                // Reset feedback message
                const feedback = confirmPassword.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = 'Please confirm your new password.';
                }
            }
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        validatePasswordMatch();
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>