<?php
/**
 * Admin Products List Page
 * CRUD operations for products (ROLE_ADMIN only)
 */

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

// Require admin access
requireAdmin();

$pdo = getDB();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            setFlash('success', 'Product deleted successfully.');
        }
    }
    redirect('/admin/products.php');
}

// Get products
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$where = '';
$params = [];
if ($search) {
    $where = "WHERE name LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $page, $perPage);

// Get products
$sql = "SELECT * FROM products $where ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Admin - Products';

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-box-seam"></i> Products Management</h1>
    <a href="/admin/product-edit.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Product
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($search): ?>
                <a href="/admin/products.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($products)): ?>
        <div class="text-center py-4">
            <p class="text-muted">No products found.</p>
            <a href="/admin/product-edit.php" class="btn btn-primary">Add First Product</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td>
                            <img src="<?= e($product['image_path'] ?: '/assets/images/placeholder.jpg') ?>" 
                                 alt="" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                        </td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['category'] ?: '-') ?></td>
                        <td><?= formatPrice($product['price']) ?></td>
                        <td>
                            <?php if ($product['stock'] <= 5): ?>
                            <span class="text-danger"><?= $product['stock'] ?></span>
                            <?php else: ?>
                            <?= $product['stock'] ?>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="/admin/product-edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        data-confirm="Are you sure you want to delete this product?">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
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
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
