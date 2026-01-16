<?php
/**
 * Single Order View Page
 * Implements owner-check: user can only see their own orders
 * Returns 403 Forbidden if user tries to access another user's order
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

// Require login
requireLogin();

$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pdo = getDB();

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// OWNER-CHECK: Verify user owns this order or is admin
// This is the mandatory security check as per PDF requirements
if ($order['user_id'] !== getCurrentUserId() && !isAdmin()) {
    // Return 403 Forbidden
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}

// Get order items
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

$pageTitle = 'Order #' . $order['id'] . ' - Mini E-Commerce';

include __DIR__ . '/../src/templates/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/orders.php">My Orders</a></li>
        <li class="breadcrumb-item active">Order #<?= $order['id'] ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Order #<?= $order['id'] ?></h5>
                <?= statusBadge($order['status']) ?>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p class="mb-1"><strong>Date:</strong> <?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></p>
                        <p class="mb-1"><strong>Status:</strong> <?= ucfirst(e($order['status'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Shipping Information</h6>
                        <p class="mb-1"><strong>Name:</strong> <?= e($order['customer_name']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= e($order['customer_email']) ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?= e($order['phone']) ?></p>
                        <p class="mb-0"><strong>Address:</strong><br><?= nl2br(e($order['address'])) ?></p>
                    </div>
                </div>
                
                <h6>Order Items</h6>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end"><?= formatPrice($item['price']) ?></td>
                            <td class="text-end"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong><?= formatPrice($order['total']) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Order Status Timeline</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Order Placed</strong>
                        <br><small class="text-muted"><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></small>
                    </li>
                    <?php if (in_array($order['status'], ['processing', 'shipped', 'delivered'])): ?>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Processing</strong>
                    </li>
                    <?php endif; ?>
                    <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Shipped</strong>
                    </li>
                    <?php endif; ?>
                    <?php if ($order['status'] === 'delivered'): ?>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <strong>Delivered</strong>
                    </li>
                    <?php endif; ?>
                    <?php if ($order['status'] === 'cancelled'): ?>
                    <li class="mb-2">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        <strong>Cancelled</strong>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="/orders.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left"></i> Back to My Orders
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
