<?php
/**
 * Shopping Cart Page
 * Session-based cart implementation
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/cart.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    // CSRF validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request. Please try again.');
        redirect('/cart.php');
    }
    
    switch ($action) {
        case 'add':
            if ($productId && addToCart($productId, $quantity)) {
                setFlash('success', 'Product added to cart!');
            } else {
                setFlash('error', 'Could not add product to cart. Check stock availability.');
            }
            break;
            
        case 'update':
            if ($productId && updateCartItem($productId, $quantity)) {
                setFlash('success', 'Cart updated!');
            } else {
                setFlash('error', 'Could not update cart. Check stock availability.');
            }
            break;
            
        case 'remove':
            if ($productId) {
                removeFromCart($productId);
                setFlash('success', 'Product removed from cart.');
            }
            break;
            
        case 'clear':
            clearCart();
            setFlash('success', 'Cart cleared.');
            break;
    }
    
    redirect('/cart.php');
}

$pageTitle = 'Shopping Cart - Mini E-Commerce';
$cartItems = getCartItems();
$cartTotal = getCartTotal();

include __DIR__ . '/../src/templates/header.php';
?>

<h1 class="mb-4"><i class="bi bi-cart3"></i> Shopping Cart</h1>

<?php if (empty($cartItems)): ?>
<div class="empty-state">
    <i class="bi bi-cart-x"></i>
    <h3>Your cart is empty</h3>
    <p>Add some products to your cart to get started.</p>
    <a href="/" class="btn btn-primary btn-lg">
        <i class="bi bi-grid"></i> Browse Products
    </a>
</div>
<?php else: ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><strong><?= count($cartItems) ?></strong> item(s) in cart</span>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="clear">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Clear all items from cart?">
                        <i class="bi bi-trash"></i> Clear Cart
                    </button>
                </form>
            </div>
            <div class="card-body">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item d-flex align-items-center gap-3">
                    <img src="<?= e($item['image_path'] ?: '/assets/images/placeholder.jpg') ?>" 
                         alt="<?= e($item['name']) ?>" class="rounded">
                    
                    <div class="flex-grow-1">
                        <h5 class="mb-1">
                            <a href="/product.php?id=<?= $item['product_id'] ?>" class="text-decoration-none">
                                <?= e($item['name']) ?>
                            </a>
                        </h5>
                        <p class="text-muted mb-0"><?= formatPrice($item['price']) ?> each</p>
                        <?php if ($item['quantity'] > $item['stock']): ?>
                        <p class="text-danger small mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Only <?= $item['stock'] ?> in stock
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <?= csrfField() ?>
                        
                        <div class="quantity-control input-group" style="width: 130px;">
                            <button type="button" class="btn btn-outline-secondary btn-minus">-</button>
                            <input type="number" name="quantity" class="form-control text-center" 
                                   value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                                   onchange="this.form.submit()">
                            <button type="button" class="btn btn-outline-secondary btn-plus">+</button>
                        </div>
                    </form>
                    
                    <div class="text-end" style="min-width: 100px;">
                        <strong><?= formatPrice($item['subtotal']) ?></strong>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="cart-summary">
            <h4 class="mb-4">Order Summary</h4>
            
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <span><?= formatPrice($cartTotal) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Shipping</span>
                <span class="text-success">Free</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-4">
                <span class="fs-5">Total</span>
                <span class="total"><?= formatPrice($cartTotal) ?></span>
            </div>
            
            <a href="/checkout.php" class="btn btn-success btn-lg w-100">
                <i class="bi bi-credit-card"></i> Proceed to Checkout
            </a>
            
            <a href="/" class="btn btn-outline-secondary w-100 mt-2">
                <i class="bi bi-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
