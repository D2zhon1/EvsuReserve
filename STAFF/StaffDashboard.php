<?php

$user = [
    'full_name' => 'John Staff'
];

$products = [
    [
        'id' => 1,
        'name' => 'PE Uniform',
        'category' => 'uniform',
        'stock_quantity' => 3
    ],
    [
        'id' => 2,
        'name' => 'ID Lace',
        'category' => 'id_sling',
        'stock_quantity' => 15
    ],
    [
        'id' => 3,
        'name' => 'Notebook',
        'category' => 'school_supply',
        'stock_quantity' => 7
    ]
];

$orders = [
    [
        'id' => 1,
        'order_number' => 'ORD-1001',
        'customer_name' => 'Juan Dela Cruz',
        'status' => 'processing',
        'payment_status' => 'paid',
        'total_amount' => 500
    ],
    [
        'id' => 2,
        'order_number' => 'ORD-1002',
        'customer_name' => 'Maria Santos',
        'status' => 'pending',
        'payment_status' => 'verified',
        'total_amount' => 1200
    ],
    [
        'id' => 3,
        'order_number' => 'ORD-1003',
        'customer_name' => 'Carlo Reyes',
        'status' => 'completed',
        'payment_status' => 'paid',
        'total_amount' => 800
    ]
];

/* Calculations */

$lowStock = array_filter($products, function($p){
    return ($p['stock_quantity'] ?? 0) < 10;
});

$activeOrders = array_filter($orders, function($o){
    return !in_array($o['status'], ['completed', 'cancelled']);
});

$totalRevenue = array_reduce($orders, function($sum, $o){

    if(in_array($o['payment_status'], ['verified', 'paid'])){

        $sum += $o['total_amount'] ?? 0;
    }

    return $sum;

}, 0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard</title>

    <link rel="stylesheet"
          href="StaffDashboard.css">
</head>
<body>

<div class="container">

    <!-- Header -->

    <div class="page-header">

        <h1>Staff Dashboard</h1>

        <p>
            Welcome,
            <?= $user['full_name'] ?? 'Staff' ?>
        </p>

    </div>

    <!-- Stats -->

    <div class="stats-grid">

        <div class="stats-card">

            <div class="stats-icon">🛍️</div>

            <div>
                <p class="stats-title">
                    Total Products
                </p>

                <h2>
                    <?= count($products) ?>
                </h2>
            </div>

        </div>

        <div class="stats-card">

            <div class="stats-icon blue">📋</div>

            <div>
                <p class="stats-title">
                    Active Orders
                </p>

                <h2>
                    <?= count($activeOrders) ?>
                </h2>
            </div>

        </div>

        <div class="stats-card">

            <div class="stats-icon orange">⚠️</div>

            <div>
                <p class="stats-title">
                    Low Stock Items
                </p>

                <h2>
                    <?= count($lowStock) ?>
                </h2>
            </div>

        </div>

        <div class="stats-card">

            <div class="stats-icon green">📈</div>

            <div>
                <p class="stats-title">
                    Revenue
                </p>

                <h2>
                    ₱<?= number_format($totalRevenue) ?>
                </h2>
            </div>

        </div>

    </div>

    <!-- Content -->

    <div class="content-grid">

        <!-- Recent Orders -->

        <div class="card">

            <div class="card-header">

                <h2>Recent Orders</h2>

                <a href="#">
                    View all
                </a>

            </div>

            <div class="card-body">

                <?php foreach(array_slice($orders, 0, 6) as $order): ?>

                    <div class="list-item">

                        <div>

                            <p class="item-title">
                                <?= $order['order_number'] ?>
                            </p>

                            <p class="item-subtitle">
                                <?= $order['customer_name'] ?>
                            </p>

                        </div>

                        <div class="item-right">

                            <span class="badge <?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>

                            <span class="amount">
                                ₱<?= number_format($order['total_amount']) ?>
                            </span>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- Low Stock -->

        <div class="card">

            <div class="card-header">

                <h2>Low Stock Alerts</h2>

                <a href="#">
                    Manage
                </a>

            </div>

            <div class="card-body">

                <?php if(count($lowStock) == 0): ?>

                    <div class="empty-state">

                        All products are well-stocked

                    </div>

                <?php else: ?>

                    <?php foreach($lowStock as $product): ?>

                        <div class="list-item">

                            <div>

                                <p class="item-title">
                                    <?= $product['name'] ?>
                                </p>

                                <p class="item-subtitle">

                                    <?= ucfirst(str_replace('_', ' ', $product['category'])) ?>

                                </p>

                            </div>

                            <span class="<?= ($product['stock_quantity'] <= 3)
                                ? 'stock-red'
                                : 'stock-orange' ?>">

                                <?= $product['stock_quantity'] ?> left

                            </span>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<script src="StaffDashboard.js"></script>

</body>
</html>