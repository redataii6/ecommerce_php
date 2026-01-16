<?php
/**
 * Admin Order View Page
 * View order details and update status (ROLE_ADMIN only)
 */

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

// Require admin access
requireAdmin();

$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    setFlash('error', 'Order not found.');
    redirect('/admin/orders.php');
}

$pdo = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $status = sanitize($_POST['status'] ?? '');
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (in_array($status, $validStatuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            setFlash('success', 'Order status updated.');
        }
    }
    redirect('/admin/order-view.php?id=' . $orderId);
}

// Get order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect('/admin/orders.php');
}

// Get order items
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.image_path 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Get user info if exists
$user = null;
if ($order['user_id']) {
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$order['user_id']]);
    $user = $userStmt->fetch();
}

$pageTitle = 'Order #' . $order['id'] . ' - Admin';

include __DIR__ . '/../../src/templates/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/orders.php">Orders</a></li>
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
                        <h6>Customer Information</h6>
                        <p class="mb-1"><strong>Name:</strong> <?= e($order['customer_name']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= e($order['customer_email']) ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?= e($order['phone']) ?></p>
                        <?php if ($user): ?>
                        <p class="mb-0"><strong>User ID:</strong> <?= $user['id'] ?> (<?= e($user['email']) ?>)</p>
                        <?php else: ?>
                        <p class="mb-0 text-muted"><em>Guest checkout</em></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Shipping Address</h6>
                        <p class="mb-0"><?= nl2br(e($order['address'])) ?></p>
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
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e($item['image_path'] ?: '/assets/images/placeholder.jpg') ?>" 
                                         alt="" style="width: 40px; height: 40px; object-fit: cover;" class="rounded">
                                    <?= e($item['product_name']) ?>
                                </div>
                            </td>
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
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Order Details</h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Order Date:</strong><br><?= e(date('M j, Y H:i:s', strtotime($order['created_at']))) ?></p>
                <?php if ($order['updated_at']): ?>
                <p class="mb-0"><strong>Last Updated:</strong><br><?= e(date('M j, Y H:i:s', strtotime($order['updated_at']))) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="/admin/orders.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
