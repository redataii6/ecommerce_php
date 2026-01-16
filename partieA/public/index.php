<?php
/**
 * Product Catalogue - Main Page
 * Lists all products with search and filtering
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'Catalogue - Mini E-Commerce';

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// Build query
$pdo = getDB();
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Pagination
$pagination = paginate($total, $page, $perPage);

// Sort options
$sortOptions = [
    'name' => 'name ASC',
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'newest' => 'created_at DESC'
];
$orderBy = $sortOptions[$sort] ?? 'name ASC';

// Get products
$sql = "SELECT * FROM products $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../src/templates/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1><i class="bi bi-grid"></i> Product Catalogue</h1>
    </div>
    <div class="col-md-4">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="btn-group" role="group">
            <a href="?<?= http_build_query(array_merge($_GET, ['category' => ''])) ?>" 
               class="btn btn-outline-secondary <?= !$category ? 'active' : '' ?>">All</a>
            <?php foreach ($categories as $cat): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['category' => $cat])) ?>" 
               class="btn btn-outline-secondary <?= $category === $cat ? 'active' : '' ?>"><?= e($cat) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-6 text-end">
        <select class="form-select d-inline-block w-auto" onchange="location.href='?<?= http_build_query(array_merge($_GET, ['sort' => ''])) ?>'.replace('sort=', 'sort=' + this.value)">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
        </select>
    </div>
</div>

<?php if (empty($products)): ?>
<div class="empty-state">
    <i class="bi bi-search"></i>
    <h3>No products found</h3>
    <p>Try adjusting your search or filter criteria.</p>
    <a href="/" class="btn btn-primary">View All Products</a>
</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
    <?php foreach ($products as $product): ?>
    <div class="col">
        <div class="card product-card h-100">
            <img src="<?= e($product['image_path'] ?: '/assets/images/placeholder.jpg') ?>" 
                 class="card-img-top" alt="<?= e($product['name']) ?>">
            <div class="card-body">
                <h5 class="card-title"><?= e($product['name']) ?></h5>
                <p class="card-text"><?= e(substr($product['description'], 0, 80)) ?>...</p>
                <p class="price mb-2"><?= formatPrice($product['price']) ?></p>
                <p class="mb-2">
                    <?php if ($product['stock'] > 0): ?>
                    <span class="stock-ok"><i class="bi bi-check-circle"></i> In Stock (<?= $product['stock'] ?>)</span>
                    <?php else: ?>
                    <span class="stock-low"><i class="bi bi-x-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="card-footer bg-transparent border-0">
                <div class="d-flex gap-2">
                    <a href="/product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <?php if ($product['stock'] > 0): ?>
                    <form action="/cart.php" method="POST" class="add-to-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($pagination['total_pages'] > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
        </li>
        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
