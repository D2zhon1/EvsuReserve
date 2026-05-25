<?php
// Mock Products Data
$products = [
    [
        'id' => 1,
        'name' => 'Wireless Mouse',
        'sku' => 'WM-001',
        'category' => 'electronics',
        'stock_quantity' => 45
    ],
    [
        'id' => 2,
        'name' => 'Keyboard',
        'sku' => 'KB-002',
        'category' => 'electronics',
        'stock_quantity' => 7
    ],
    [
        'id' => 3,
        'name' => 'Notebook',
        'sku' => 'NB-003',
        'category' => 'school_supplies',
        'stock_quantity' => 0
    ],
];

$totalStock = array_sum(array_column($products, 'stock_quantity'));

$lowStockCount = count(array_filter($products, function($p) {
    return $p['stock_quantity'] < 10 && $p['stock_quantity'] > 0;
}));

$outOfStockCount = count(array_filter($products, function($p) {
    return $p['stock_quantity'] == 0;
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="InventoryManagement.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1>Inventory Management</h1>
        <p>Monitor and adjust product stock levels</p>
    </div>

    <!-- Summary -->
    <div class="summary-grid">

        <div class="summary-card">
            <h2><?= $totalStock ?></h2>
            <p>Total Stock</p>
        </div>

        <div class="summary-card orange">
            <h2><?= $lowStockCount ?></h2>
            <p>Low Stock</p>
        </div>

        <div class="summary-card red">
            <h2><?= $outOfStockCount ?></h2>
            <p>Out of Stock</p>
        </div>

    </div>

    <!-- Search -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search inventory...">
    </div>

    <!-- Table -->
    <div class="table-card">

        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach($products as $product): ?>

                <?php
                    $qty = $product['stock_quantity'];

                    if ($qty == 0) {
                        $status = "Out of Stock";
                        $statusClass = "red";
                        $barClass = "bar-red";
                    } elseif ($qty < 10) {
                        $status = "Low Stock";
                        $statusClass = "orange";
                        $barClass = "bar-orange";
                    } else {
                        $status = "In Stock";
                        $statusClass = "green";
                        $barClass = "bar-green";
                    }
                ?>

                <tr>
                    <td><?= $product['name'] ?></td>

                    <td><?= $product['sku'] ?></td>

                    <td><?= ucfirst(str_replace('_', ' ', $product['category'])) ?></td>

                    <td>
                        <div class="stock-wrapper">

                            <div class="progress-bar">
                                <div class="progress-fill <?= $barClass ?>"
                                    style="width: <?= min(100, $qty) ?>%">
                                </div>
                            </div>

                            <span><?= $qty ?></span>

                        </div>
                    </td>

                    <td>
                        <span class="badge <?= $statusClass ?>">
                            <?= $status ?>
                        </span>
                    </td>

                    <td>
                        <button class="adjust-btn"
                            onclick="openModal(
                                '<?= $product['id'] ?>',
                                '<?= $product['name'] ?>',
                                '<?= $qty ?>'
                            )">
                            Adjust
                        </button>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

<!-- Modal -->
<div class="modal" id="stockModal">

    <div class="modal-content">

        <h2 id="modalTitle">Adjust Stock</h2>

        <form>

            <label>New Quantity</label>

            <input type="number" id="stockQty" min="0">

            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeModal()">
                    Cancel
                </button>

                <button type="button" class="update-btn" onclick="updateStock()">
                    Update Stock
                </button>
            </div>

        </form>

    </div>

</div>

<script src="InventoryManagement."></script>
</body>
</html>