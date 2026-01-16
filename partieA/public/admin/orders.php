<?php
/**
 * Admin Orders List Page
 * View and manage all orders (ROLE_ADMIN only)
 */

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

// Require admin access
requireAdmin();

$pdo = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $orderId = intval($_POST['order_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if ($orderId && in_array($status, $validStatuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            setFlash('success', 'Order status updated.');
        }
    }
    redirect('/admin/orders.php');
}

// Filters
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(customer_name LIKE ? OR customer_email LIKE ? OR id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = intval($search);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $page, $perPage);

// Get orders
$sql = "SELECT * FROM orders $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get status counts
$statusCounts = [];
$countAllStmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $countAllStmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}

$pageTitle = 'Admin - Orders';

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-receipt"></i> Orders Management</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or order ID..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending (<?= $statusCounts['pending'] ?? 0 ?>)</option>
                    <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing (<?= $statusCounts['processing'] ?? 0 ?>)</option>
                    <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Shipped (<?= $statusCounts['shipped'] ?? 0 ?>)</option>
                    <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered (<?= $statusCounts['delivered'] ?? 0 ?>)</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled (<?= $statusCounts['cancelled'] ?? 0 ?>)</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <?php if ($status || $search): ?>
                <a href="/admin/orders.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($orders)): ?>
        <div class="text-center py-4">
            <p class="text-muted">No orders found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['id'] ?></strong></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e($order['customer_email']) ?></td>
                        <td><?= formatPrice($order['total']) ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <?= csrfField() ?>
                                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                        <td><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?></td>
                        <td>
                            <a href="/admin/order-view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
