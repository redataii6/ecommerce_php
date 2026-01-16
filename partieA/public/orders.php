<?php
/**
 * User Orders Page
 * Shows orders for the logged-in user only (owner-check)
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

// Require login
requireLogin();

$userId = getCurrentUserId();
$pdo = getDB();

// Get user's orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders - Mini E-Commerce';

include __DIR__ . '/../src/templates/header.php';
?>

<h1 class="mb-4"><i class="bi bi-list-check"></i> My Orders</h1>

<?php if (empty($orders)): ?>
<div class="empty-state">
    <i class="bi bi-bag-x"></i>
    <h3>No orders yet</h3>
    <p>You haven't placed any orders yet.</p>
    <a href="/" class="btn btn-primary btn-lg">
        <i class="bi bi-grid"></i> Start Shopping
    </a>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <?php
            // Get item count
            $itemStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order['id']]);
            $itemCount = $itemStmt->fetchColumn();
            ?>
            <tr>
                <td><strong>#<?= $order['id'] ?></strong></td>
                <td><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></td>
                <td><?= $itemCount ?> item(s)</td>
                <td><?= formatPrice($order['total']) ?></td>
                <td><?= statusBadge($order['status']) ?></td>
                <td>
                    <a href="/order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
