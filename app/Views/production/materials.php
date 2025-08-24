<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Materials Report</h1>
    <div>
        <button class="btn btn-primary" onclick="exportToCSV()">
            <i class="bi bi-download"></i> Export to CSV
        </button>
        <a href="/azteamcrm/production" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle"></i> This report shows all materials needed for pending and in-production items.
</div>

<?php if (empty($materials)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No materials needed at this time.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="materialsTable">
                    <thead>
                        <tr>
                            <th>Product Type</th>
                            <th>Size</th>
                            <th>Customization Method</th>
                            <th>Number of Items</th>
                            <th>Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalItems = 0;
                        $totalQuantity = 0;
                        foreach ($materials as $material): 
                            $totalItems += $material['item_count'];
                            $totalQuantity += $material['total_quantity'];
                        ?>
                        <tr>
                            <td>
                                <?php 
                                $productType = $material['product_type'] ?: 'N/A';
                                echo ucwords(str_replace('_', ' ', $productType));
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!$material['product_size']) {
                                    echo 'N/A';
                                } else {
                                    $sizes = [
                                        'child_xs' => 'Child XS',
                                        'child_s' => 'Child S',
                                        'child_m' => 'Child M',
                                        'child_l' => 'Child L',
                                        'child_xl' => 'Child XL',
                                        'xs' => 'XS',
                                        's' => 'S',
                                        'm' => 'M',
                                        'l' => 'L',
                                        'xl' => 'XL',
                                        'xxl' => 'XXL',
                                        'xxxl' => 'XXXL',
                                        'xxxxl' => 'XXXXL',
                                        'one_size' => 'One Size'
                                    ];
                                    echo $sizes[strtolower($material['product_size'])] ?? $material['product_size'];
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!$material['custom_method']) {
                                    echo 'N/A';
                                } else {
                                    $methods = [
                                        'htv' => 'HTV',
                                        'dft' => 'DFT',
                                        'embroidery' => 'Embroidery',
                                        'sublimation' => 'Sublimation',
                                        'printing' => 'Printing Services'
                                    ];
                                    echo $methods[strtolower($material['custom_method'])] ?? ucwords(str_replace('_', ' ', $material['custom_method']));
                                }
                                ?>
                            </td>
                            <td class="text-center"><?= $material['item_count'] ?></td>
                            <td class="text-center"><strong><?= $material['total_quantity'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th colspan="3">Total</th>
                            <th class="text-center"><?= $totalItems ?> items</th>
                            <th class="text-center"><?= $totalQuantity ?> units</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary by Product Type -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Summary by Product Type</h5>
        </div>
        <div class="card-body">
            <?php 
            $productSummary = [];
            foreach ($materials as $material) {
                $type = $material['product_type'] ?: 'N/A';
                if (!isset($productSummary[$type])) {
                    $productSummary[$type] = 0;
                }
                $productSummary[$type] += $material['total_quantity'];
            }
            arsort($productSummary);
            ?>
            <div class="row">
                <?php foreach ($productSummary as $type => $quantity): ?>
                <div class="col-md-3 mb-2">
                    <div class="border rounded p-2">
                        <strong><?= ucwords(str_replace('_', ' ', $type)) ?>:</strong> <?= $quantity ?> units
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function exportToCSV() {
    const table = document.getElementById('materialsTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Get headers
    const headers = [];
    rows[0].querySelectorAll('th').forEach(header => {
        headers.push('"' + header.textContent.trim() + '"');
    });
    csv.push(headers.join(','));
    
    // Get data rows (skip header and footer)
    for (let i = 1; i < rows.length - 1; i++) {
        const row = rows[i];
        const data = [];
        row.querySelectorAll('td').forEach(cell => {
            data.push('"' + cell.textContent.trim() + '"');
        });
        csv.push(data.join(','));
    }
    
    // Add footer
    const footer = rows[rows.length - 1];
    const footerData = [];
    footer.querySelectorAll('th').forEach(cell => {
        footerData.push('"' + cell.textContent.trim() + '"');
    });
    csv.push(footerData.join(','));
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'materials_report_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>