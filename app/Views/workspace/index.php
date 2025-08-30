<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Personal Workspace</h1>
    <div>
        <button id="refresh-tabs" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-4" id="workspaceTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="available-tab" data-bs-toggle="tab" data-bs-target="#available" type="button" role="tab">
            <i class="bi bi-list-ul"></i> Available Work 
            <span id="available-count" class="badge bg-secondary ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="artwork-sent-tab" data-bs-toggle="tab" data-bs-target="#artwork-sent" type="button" role="tab">
            <i class="bi bi-palette"></i> Artwork Sent
            <span id="artwork-sent-count" class="badge bg-primary ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="artwork-approved-tab" data-bs-toggle="tab" data-bs-target="#artwork-approved" type="button" role="tab">
            <i class="bi bi-check-square"></i> Artwork Approved
            <span id="artwork-approved-count" class="badge bg-info ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="nesting-done-tab" data-bs-toggle="tab" data-bs-target="#nesting-done" type="button" role="tab">
            <i class="bi bi-grid-3x3-gap"></i> Nesting/Digitalization
            <span id="nesting-done-count" class="badge bg-purple ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">
            <i class="bi bi-check-circle"></i> My Completed 
            <span id="completed-count" class="badge bg-success ms-1">0</span>
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="workspaceTabsContent">
    
    <!-- Available Work Tab -->
    <div class="tab-pane fade show active" id="available" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Available Work Pool</h4>
            <small class="text-muted">Items ready to start artwork process</small>
        </div>
        
        <div id="available-loading" class="d-none">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading available work...</p>
            </div>
        </div>
        
        <div id="available-items" class="row">
            <!-- Available items will be loaded here via AJAX -->
        </div>
        
        <div id="available-empty" class="text-center py-4 d-none">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
            <h4 class="mt-3">All caught up!</h4>
            <p class="text-muted">No items available to start artwork process.</p>
        </div>
    </div>

    <!-- Artwork Sent Tab -->
    <div class="tab-pane fade" id="artwork-sent" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Artwork Sent for Approval</h4>
            <small class="text-muted">Items with artwork pending client approval</small>
        </div>
        
        <div id="artwork-sent-loading" class="d-none">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading artwork sent items...</p>
            </div>
        </div>
        
        <div id="artwork-sent-items" class="row">
            <!-- Artwork sent items will be loaded here via AJAX -->
        </div>
        
        <div id="artwork-sent-empty" class="text-center py-4 d-none">
            <i class="bi bi-palette text-muted" style="font-size: 3rem;"></i>
            <h4 class="mt-3">No artwork pending</h4>
            <p class="text-muted">No items waiting for artwork approval.</p>
        </div>
    </div>

    <!-- Artwork Approved Tab -->
    <div class="tab-pane fade" id="artwork-approved" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Artwork Approved</h4>
            <small class="text-muted">Items with approved artwork ready for nesting</small>
        </div>
        
        <div id="artwork-approved-loading" class="d-none">
            <div class="text-center py-4">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading artwork approved items...</p>
            </div>
        </div>
        
        <div id="artwork-approved-items" class="row">
            <!-- Artwork approved items will be loaded here via AJAX -->
        </div>
        
        <div id="artwork-approved-empty" class="text-center py-4 d-none">
            <i class="bi bi-check-square text-muted" style="font-size: 3rem;"></i>
            <h4 class="mt-3">No approved artwork</h4>
            <p class="text-muted">No items with approved artwork ready for nesting.</p>
        </div>
    </div>

    <!-- Nesting/Digitalization Tab -->
    <div class="tab-pane fade" id="nesting-done" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Nesting/Digitalization Complete</h4>
            <small class="text-muted">Items ready for final production</small>
        </div>
        
        <div id="nesting-done-loading" class="d-none">
            <div class="text-center py-4">
                <div class="spinner-border text-purple" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading nesting/digitalization items...</p>
            </div>
        </div>
        
        <div id="nesting-done-items" class="row">
            <!-- Nesting/digitalization items will be loaded here via AJAX -->
        </div>
        
        <div id="nesting-done-empty" class="text-center py-4 d-none">
            <i class="bi bi-grid-3x3-gap text-muted" style="font-size: 3rem;"></i>
            <h4 class="mt-3">No items ready</h4>
            <p class="text-muted">No items with completed nesting/digitalization.</p>
        </div>
    </div>

    <!-- My Completed Tab -->
    <div class="tab-pane fade" id="completed" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>My Completed Work</h4>
            <small class="text-muted">All items you've completed</small>
        </div>
        
        <div id="completed-loading" class="d-none">
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading completed work...</p>
            </div>
        </div>
        
        <div id="completed-items" class="row">
            <!-- Completed items will be loaded here via AJAX -->
        </div>
        
        <div id="completed-empty" class="text-center py-4 d-none">
            <i class="bi bi-trophy text-warning" style="font-size: 3rem;"></i>
            <h4 class="mt-3">Start completing items!</h4>
            <p class="text-muted">Completed items will appear here to track your productivity.</p>
        </div>
    </div>
</div>

<!-- Success/Error Alert -->
<div id="alert-container" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050;">
    <!-- Alerts will be inserted here -->
</div>

<!-- CSRF Token for JavaScript -->
<script>
    const CSRF_TOKEN = '<?= $csrf_token ?>';
    const BASE_URL = '/azteamcrm';
</script>

<!-- Workspace JavaScript -->
<script src="/azteamcrm/assets/js/workspace.js"></script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>