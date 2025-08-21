<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">User Management</h1>
    <a href="/azteamcrm/users/create" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Add New User
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search users...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover searchable-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user->id ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user->username) ?></strong>
                                    <?php if ($user->id == $_SESSION['user_id']): ?>
                                        <span class="badge bg-info ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user->full_name) ?></td>
                                <td><?= htmlspecialchars($user->email) ?></td>
                                <td>
                                    <?php if ($user->role === 'administrator'): ?>
                                        <span class="badge bg-danger">Administrator</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Production Team</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user->is_active): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($user->created_at)) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/azteamcrm/users/<?= $user->id ?>/edit" 
                                           class="btn btn-outline-primary" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($user->id != $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-warning toggle-status-btn" 
                                                    data-user-id="<?= $user->id ?>"
                                                    data-current-status="<?= $user->is_active ? '1' : '0' ?>"
                                                    title="Toggle Status">
                                                <i class="bi <?= $user->is_active ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                            </button>
                                            
                                            <button type="button" 
                                                    class="btn btn-outline-danger delete-user-btn" 
                                                    data-user-id="<?= $user->id ?>"
                                                    data-username="<?= htmlspecialchars($user->username) ?>"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled title="Cannot modify own account">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate user <strong id="deleteUsername"></strong>?</p>
                <p class="text-muted">This action will prevent the user from logging in.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">Deactivate User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle status functionality
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const currentStatus = this.dataset.currentStatus;
            const icon = this.querySelector('i');
            
            // Send AJAX request
            fetch(`/azteamcrm/users/${userId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle icon
                    if (data.is_active) {
                        icon.classList.remove('bi-toggle-off');
                        icon.classList.add('bi-toggle-on');
                        this.dataset.currentStatus = '1';
                        
                        // Update status badge
                        const statusBadge = this.closest('tr').querySelector('td:nth-child(6) .badge');
                        statusBadge.classList.remove('bg-secondary');
                        statusBadge.classList.add('bg-success');
                        statusBadge.textContent = 'Active';
                    } else {
                        icon.classList.remove('bi-toggle-on');
                        icon.classList.add('bi-toggle-off');
                        this.dataset.currentStatus = '0';
                        
                        // Update status badge
                        const statusBadge = this.closest('tr').querySelector('td:nth-child(6) .badge');
                        statusBadge.classList.remove('bg-success');
                        statusBadge.classList.add('bg-secondary');
                        statusBadge.textContent = 'Inactive';
                    }
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alert.style.zIndex = '9999';
                    alert.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alert);
                    
                    setTimeout(() => alert.remove(), 3000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating user status');
            });
        });
    });
    
    // Delete user functionality
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const username = this.dataset.username;
            
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteUserForm').action = `/azteamcrm/users/${userId}/delete`;
            
            deleteModal.show();
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>