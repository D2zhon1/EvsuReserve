<?php
/**
 * Order stock adjustment helpers.
 */

function evsu_orders_track_stock_flag(mysqli $conn): bool
{
    return function_exists('evsu_column_exists') && evsu_column_exists($conn, 'orders', 'stock_deducted');
}

function evsu_deduct_order_stock(mysqli $conn, int $order_id): void
{
    $track_flag = evsu_orders_track_stock_flag($conn);

    if ($track_flag) {
        $flag = $conn->prepare('SELECT stock_deducted FROM orders WHERE id = ? LIMIT 1');
        $flag->bind_param('i', $order_id);
        $flag->execute();
        $row = $flag->get_result()->fetch_assoc();
        $flag->close();

        if (!$row || (int) $row['stock_deducted'] === 1) {
            return;
        }
    }

    $items_stmt = $conn->prepare(
        'SELECT product_id, product_name, quantity FROM order_items WHERE order_id = ?'
    );
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    if (count($items) === 0) {
        throw new RuntimeException('Order has no items.');
    }

    $stock_stmt = $conn->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
    $deduct_stmt = $conn->prepare(
        'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?'
    );

    foreach ($items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];

        if ($pid <= 0) {
            continue;
        }

        $stock_stmt->bind_param('i', $pid);
        $stock_stmt->execute();
        $stock_res = $stock_stmt->get_result()->fetch_assoc();
        $current_stock = (int) ($stock_res['stock_quantity'] ?? 0);

        if ($current_stock < $qty) {
            throw new RuntimeException(
                $item['product_name'] . ' does not have enough stock. Available: ' . $current_stock
            );
        }

        $deduct_stmt->bind_param('iii', $qty, $pid, $qty);
        $deduct_stmt->execute();
        if ($deduct_stmt->affected_rows <= 0) {
            throw new RuntimeException('Failed to deduct stock for ' . $item['product_name']);
        }
    }

    $stock_stmt->close();
    $deduct_stmt->close();

    if ($track_flag) {
        $upd = $conn->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?');
        $upd->bind_param('i', $order_id);
        $upd->execute();
        $upd->close();
    }
}

function evsu_restore_order_stock(mysqli $conn, int $order_id): void
{
    $track_flag = evsu_orders_track_stock_flag($conn);

    if ($track_flag) {
        $flag = $conn->prepare('SELECT stock_deducted FROM orders WHERE id = ? LIMIT 1');
        $flag->bind_param('i', $order_id);
        $flag->execute();
        $row = $flag->get_result()->fetch_assoc();
        $flag->close();

        if (!$row || (int) $row['stock_deducted'] !== 1) {
            return;
        }
    }

    $items_stmt = $conn->prepare(
        'SELECT product_id, quantity FROM order_items WHERE order_id = ?'
    );
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    $restore_stmt = $conn->prepare(
        'UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?'
    );

    foreach ($items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        $restore_stmt->bind_param('ii', $qty, $pid);
        $restore_stmt->execute();
    }

    $restore_stmt->close();

    if ($track_flag) {
        $upd = $conn->prepare('UPDATE orders SET stock_deducted = 0 WHERE id = ?');
        $upd->bind_param('i', $order_id);
        $upd->execute();
        $upd->close();
    }
}
