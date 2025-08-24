<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?= $title ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <form method="POST" action="<?= $user ? "/azteamcrm/users/{$user->id}/update" : "/azteamcrm/users/store" ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                   id="username" 
                                   name="username" 
                                   value="<?= htmlspecialchars($old['username'] ?? $user->username ?? '') ?>" 
                                   required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 3 characters</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($old['email'] ?? $user->email ?? '') ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                               id="full_name" 
                               name="full_name" 
                               value="<?= htmlspecialchars($old['full_name'] ?? $user->full_name ?? '') ?>" 
                               required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['full_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" 
                                id="role" 
                                name="role" 
                                required>
                            <option value="">Select Role</option>
                            <option value="administrator" <?= ($old['role'] ?? $user->role ?? '') === 'administrator' ? 'selected' : '' ?>>
                                Administrator
                            </option>
                            <option value="production_team" <?= ($old['role'] ?? $user->role ?? '') === 'production_team' ? 'selected' : '' ?>>
                                Production Team
                            </option>
                        </select>
                        <?php if (isset($errors['role'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['role']) ?></div>
                        <?php endif; ?>
                        <small class="text-muted">
                            <strong>Administrator:</strong> Full system access, user management, financial reporting<br>
                            <strong>Production Team:</strong> Update production/supplier status, view production queue
                        </small>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Password <?= !$user ? '<span class="text-danger">*</span>' : '<small class="text-muted">(leave blank to keep current)</small>' ?>
                            </label>
                            <input type="password" 
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                   id="password" 
                                   name="password" 
                                   <?= !$user ? 'required' : '' ?>>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">
                                Confirm Password <?= !$user ? '<span class="text-danger">*</span>' : '' ?>
                            </label>
                            <input type="password" 
                                   class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   <?= !$user ? 'required' : '' ?>>
                            <?php if (isset($errors['password_confirm'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($user): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> User Status: 
                            <?php if ($user->is_active): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                            <br>
                            <small>Created: <?= date('F d, Y', strtotime($user->created_at)) ?></small>
                            <?php if ($user->updated_at): ?>
                                <br><small>Last Updated: <?= date('F d, Y', strtotime($user->updated_at)) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/azteamcrm/users" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Users
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <?= $user ? 'Update User' : 'Create User' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    
    function validatePasswordMatch() {
        if (password.value && passwordConfirm.value) {
            if (password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity('Passwords do not match');
                passwordConfirm.classList.add('is-invalid');
            } else {
                passwordConfirm.setCustomValidity('');
                passwordConfirm.classList.remove('is-invalid');
            }
        }
    }
    
    password.addEventListener('input', validatePasswordMatch);
    passwordConfirm.addEventListener('input', validatePasswordMatch);
    
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