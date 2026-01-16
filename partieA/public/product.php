<?php
/**
 * Product Detail Page
 * Shows single product with add to cart functionality
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

// Get product ID
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Fetch product
$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pageTitle = e($product['name']) . ' - Mini E-Commerce';

include __DIR__ . '/../src/templates/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Catalogue</a></li>
        <?php if ($product['category']): ?>
        <li class="breadcrumb-item"><a href="/?category=<?= urlencode($product['category']) ?>"><?= e($product['category']) ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-6 mb-4">
        <img src="<?= e($product['image_path'] ?: '/assets/images/placeholder.jpg') ?>" 
             class="img-fluid rounded shadow" alt="<?= e($product['name']) ?>">
    </div>
    <div class="col-md-6">
        <h1><?= e($product['name']) ?></h1>
        
        <?php if ($product['category']): ?>
        <p class="text-muted">
            <i class="bi bi-tag"></i> <?= e($product['category']) ?>
        </p>
        <?php endif; ?>
        
        <p class="lead"><?= e($product['description']) ?></p>
        
        <div class="my-4">
            <span class="display-5 text-success fw-bold"><?= formatPrice($product['price']) ?></span>
        </div>
        
        <div class="mb-4">
            <?php if ($product['stock'] > 0): ?>
            <span class="badge bg-success fs-6">
                <i class="bi bi-check-circle"></i> In Stock (<?= $product['stock'] ?> available)
            </span>
            <?php else: ?>
            <span class="badge bg-danger fs-6">
                <i class="bi bi-x-circle"></i> Out of Stock
            </span>
            <?php endif; ?>
        </div>
        
        <?php if ($product['stock'] > 0): ?>
        <form action="/cart.php" method="POST" class="add-to-cart-form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <?= csrfField() ?>
            
            <div class="row g-3 align-items-center mb-4">
                <div class="col-auto">
                    <label for="quantity" class="col-form-label">Quantity:</label>
                </div>
                <div class="col-auto">
                    <div class="quantity-control input-group" style="width: 150px;">
                        <button type="button" class="btn btn-outline-secondary btn-minus">-</button>
                        <input type="number" name="quantity" id="quantity" class="form-control text-center" 
                               value="1" min="1" max="<?= $product['stock'] ?>">
                        <button type="button" class="btn btn-outline-secondary btn-plus">+</button>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
        </form>
        <?php else: ?>
        <button class="btn btn-secondary btn-lg" disabled>
            <i class="bi bi-cart-x"></i> Out of Stock
        </button>
        <?php endif; ?>
        
        <hr class="my-4">
        
        <div class="d-flex gap-2">
            <a href="/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Catalogue
            </a>
            <a href="/cart.php" class="btn btn-outline-primary">
                <i class="bi bi-cart3"></i> View Cart
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
