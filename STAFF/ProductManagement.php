<?php

$products = [
    [
        'id' => 1,
        'name' => 'PE Uniform',
        'description' => 'Official PE uniform',
        'category' => 'uniform',
        'price' => 450,
        'markup_price' => 500,
        'stock_quantity' => 20,
        'image_url' => '',
        'sizes_available' => ['S', 'M', 'L'],
        'is_active' => true,
        'sku' => 'UNI-001'
    ],
    [
        'id' => 2,
        'name' => 'ID Lace',
        'description' => 'School ID lace',
        'category' => 'id_sling',
        'price' => 50,
        'markup_price' => 70,
        'stock_quantity' => 5,
        'image_url' => '',
        'sizes_available' => [],
        'is_active' => true,
        'sku' => 'IDL-002'
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>

    <link rel="stylesheet" href="ProductManagement.css">
</head>
<body>

<div class="container">

    <div class="page-header">

        <div>
            <h1>Product Management</h1>
            <p>Add, edit, and manage IGP products</p>
        </div>

        <button class="add-btn" onclick="openForm()">
            + Add Product
        </button>

    </div>

    <!-- Search -->
    <div class="search-box">
        <input type="text"
               id="searchInput"
               placeholder="Search products...">
    </div>

    <!-- Product Table -->
    <div class="table-card">

        <table id="productTable">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Markup</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach($products as $product): ?>

                <tr>

                    <td>

                        <div class="product-info">

                            <div class="product-image">

                                <?php if($product['image_url']): ?>

                                    <img src="<?= $product['image_url'] ?>">

                                <?php else: ?>

                                    📦

                                <?php endif; ?>

                            </div>

                            <div>
                                <p class="product-name">
                                    <?= $product['name'] ?>
                                </p>

                                <p class="product-sku">
                                    <?= $product['sku'] ?>
                                </p>
                            </div>

                        </div>

                    </td>

                    <td>
                        <?= ucfirst(str_replace('_', ' ', $product['category'])) ?>
                    </td>

                    <td>
                        ₱<?= number_format($product['price']) ?>
                    </td>

                    <td>
                        ₱<?= number_format($product['markup_price']) ?>
                    </td>

                    <td>

                        <span class="<?= $product['stock_quantity'] < 10 ? 'low-stock' : '' ?>">
                            <?= $product['stock_quantity'] ?>
                        </span>

                    </td>

                    <td>

                        <span class="status <?= $product['is_active'] ? 'active' : 'inactive' ?>">

                            <?= $product['is_active'] ? 'Active' : 'Inactive' ?>

                        </span>

                    </td>

                    <td>

                        <div class="action-buttons">

                            <button class="edit-btn"
                                onclick='editProduct(<?= json_encode($product) ?>)'>
                                Edit
                            </button>

                            <button class="delete-btn"
                                onclick="deleteProduct(<?= $product['id'] ?>)">
                                Delete
                            </button>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<!-- Modal -->
<div class="modal" id="productModal">

    <div class="modal-content">

        <div class="modal-header">

            <h2 id="modalTitle">Add Product</h2>

            <button class="close-btn" onclick="closeForm()">×</button>

        </div>

        <form id="productForm">

            <div class="form-group">
                <label>Product Name</label>

                <input type="text" id="name" required>
            </div>

            <div class="form-group">
                <label>Description</label>

                <textarea id="description"></textarea>
            </div>

            <div class="grid-2">

                <div class="form-group">

                    <label>Category</label>

                    <select id="category">

                        <option value="uniform">Uniform</option>

                        <option value="id_sling">ID Sling</option>

                        <option value="booklet">Booklet</option>

                        <option value="school_supply">School Supply</option>

                        <option value="merchandise">Merchandise</option>

                        <option value="other">Other</option>

                    </select>

                </div>

                <div class="form-group">

                    <label>SKU</label>

                    <input type="text" id="sku">

                </div>

            </div>

            <div class="grid-3">

                <div class="form-group">

                    <label>Base Price</label>

                    <input type="number" id="price">

                </div>

                <div class="form-group">

                    <label>Markup Price</label>

                    <input type="number" id="markup_price">

                </div>

                <div class="form-group">

                    <label>Stock Qty</label>

                    <input type="number" id="stock_quantity">

                </div>

            </div>

            <div class="form-group">

                <label>Image URL</label>

                <input type="text" id="image_url">

            </div>

            <div class="form-group">

                <label>Sizes</label>

                <div class="size-box">

                    <input type="text"
                           id="sizeInput"
                           placeholder="S, M, L">

                    <button type="button"
                            onclick="addSize()">
                        Add
                    </button>

                </div>

                <div id="sizesContainer"></div>

            </div>

            <div class="switch-box">

                <input type="checkbox"
                       id="is_active"
                       checked>

                <label for="is_active">Active</label>

            </div>

            <div class="form-actions">

                <button type="button"
                        class="cancel-btn"
                        onclick="closeForm()">
                    Cancel
                </button>

                <button type="submit"
                        class="save-btn">
                    Save Product
                </button>

            </div>

        </form>

    </div>

</div>

<script>
    const products = <?php echo json_encode($products); ?>;
</script>

<script src="ProductManagement.js"></script>

</body>
</html>