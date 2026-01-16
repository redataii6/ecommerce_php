<?php
/**
 * Thank You / Order Confirmation Page
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

initSession();

$orderId = $_SESSION['last_order_id'] ?? null;

if (!$orderId) {
    redirect('/');
}

// Get order details
$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/');
}

// Get order items
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Clear the session order ID
unset($_SESSION['last_order_id']);

$pageTitle = 'Order Confirmed - Mini E-Commerce';

include __DIR__ . '/../src/templates/header.php';
?>

<div class="thankyou-page">
    <div class="checkmark">
        <i class="bi bi-check-circle-fill"></i>
    </div>
    
    <h1 class="display-4 mb-3">Thank You!</h1>
    <p class="lead mb-4">Your order has been placed successfully.</p>
    
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Order #<?= $order['id'] ?></h5>
        </div>
        <div class="card-body text-start">
            <div class="row mb-3">
                <div class="col-sm-4"><strong>Order Date:</strong></div>
                <div class="col-sm-8"><?= e($order['created_at']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4"><strong>Status:</strong></div>
                <div class="col-sm-8"><?= statusBadge($order['status']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4"><strong>Customer:</strong></div>
                <div class="col-sm-8"><?= e($order['customer_name']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4"><strong>Email:</strong></div>
                <div class="col-sm-8"><?= e($order['customer_email']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4"><strong>Phone:</strong></div>
                <div class="col-sm-8"><?= e($order['phone']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4"><strong>Address:</strong></div>
                <div class="col-sm-8"><?= nl2br(e($order['address'])) ?></div>
            </div>
            
            <hr>
            
            <h6 class="mb-3">Order Items</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
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
    
    <div class="mt-4">
        <p class="text-muted">A confirmation email has been sent to <?= e($order['customer_email']) ?></p>
        
        <div class="d-flex justify-content-center gap-2">
            <?php if (isLoggedIn()): ?>
            <a href="/orders.php" class="btn btn-primary">
                <i class="bi bi-list-check"></i> View My Orders
            </a>
            <?php endif; ?>
            <a href="/" class="btn btn-outline-primary">
                <i class="bi bi-grid"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
