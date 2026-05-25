<?php

$orders = [
    [
        'id' => 1,
        'order_number' => 'ORD-1001',
        'customer_name' => 'Juan Dela Cruz',
        'customer_email' => 'juan@gmail.com',
        'items' => [
            [
                'product_name' => 'T-Shirt',
                'size' => 'M',
                'quantity' => 2,
                'subtotal' => 500
            ]
        ],
        'total_amount' => 500,
        'payment_method' => 'GCash',
        'payment_status' => 'paid',
        'status' => 'processing',
        'created_date' => '2026-05-26',
        'notes' => 'Deliver ASAP'
    ],

    [
        'id' => 2,
        'order_number' => 'ORD-1002',
        'customer_name' => 'Maria Santos',
        'customer_email' => 'maria@gmail.com',
        'items' => [
            [
                'product_name' => 'Notebook',
                'size' => '',
                'quantity' => 1,
                'subtotal' => 120
            ]
        ],
        'total_amount' => 120,
        'payment_method' => 'Cash',
        'payment_status' => 'pending',
        'status' => 'pending',
        'created_date' => '2026-05-25',
        'notes' => ''
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>

    <link rel="stylesheet" href="OrderManagement.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1>Order Management</h1>
        <p>View and manage all orders</p>
    </div>

    <!-- Filters -->
    <div class="filters">

        <input type="text"
               id="searchInput"
               placeholder="Search orders...">

        <select id="statusFilter">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="ready">Ready</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>

    </div>

    <!-- Table -->
    <div class="table-card">

        <table id="ordersTable">

            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach($orders as $order): ?>

                <tr data-status="<?= $order['status'] ?>">

                    <td><?= $order['order_number'] ?></td>

                    <td><?= $order['customer_name'] ?></td>

                    <td><?= count($order['items']) ?></td>

                    <td>₱<?= number_format($order['total_amount']) ?></td>

                    <td>
                        <span class="badge <?= $order['payment_status'] ?>">
                            <?= ucfirst($order['payment_status']) ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge <?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </td>

                    <td>
                        <?= date('M d', strtotime($order['created_date'])) ?>
                    </td>

                    <td>

                        <select class="status-select">

                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>

                            <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>

                            <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>

                            <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Ready</option>

                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>

                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>

                        </select>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<!-- Modal -->
<div class="modal" id="orderModal">

    <div class="modal-content">

        <div class="modal-header">

            <h2 id="modalOrderNumber">Order Details</h2>

            <button class="close-btn" onclick="closeModal()">×</button>

        </div>

        <div id="modalBody"></div>

    </div>

</div>

<script>
    const orders = <?php echo json_encode($orders); ?>;
</script>

<script src="OrderManagement.js"></script>

</body>
</html>