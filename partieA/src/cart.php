<?php
/**
 * Cart helper functions using PHP sessions
 * Session-based cart implementation as required by the PDF specification
 */

require_once __DIR__ . '/db.php';

/**
 * Initialize cart in session
 */
function initCart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/**
 * Get all cart items with product details
 * @return array
 */
function getCartItems(): array {
    initCart();
    
    if (empty($_SESSION['cart'])) {
        return [];
    }
    
    $pdo = getDB();
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("SELECT id, name, description, price, stock, image_path FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    $items = [];
    foreach ($products as $product) {
        $productId = $product['id'];
        $quantity = $_SESSION['cart'][$productId];
        $items[] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => (float)$product['price'],
            'stock' => (int)$product['stock'],
            'image_path' => $product['image_path'],
            'quantity' => $quantity,
            'subtotal' => (float)$product['price'] * $quantity
        ];
    }
    
    return $items;
}

/**
 * Add product to cart
 * @param int $productId
 * @param int $quantity
 * @return bool
 */
function addToCart(int $productId, int $quantity = 1): bool {
    initCart();
    
    if ($quantity <= 0) {
        return false;
    }
    
    // Verify product exists and has stock
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return false;
    }
    
    $currentQty = $_SESSION['cart'][$productId] ?? 0;
    $newQty = $currentQty + $quantity;
    
    // Check stock availability
    if ($newQty > $product['stock']) {
        return false;
    }
    
    $_SESSION['cart'][$productId] = $newQty;
    return true;
}

/**
 * Update cart item quantity
 * @param int $productId
 * @param int $quantity
 * @return bool
 */
function updateCartItem(int $productId, int $quantity): bool {
    initCart();
    
    if ($quantity <= 0) {
        removeFromCart($productId);
        return true;
    }
    
    // Verify product exists and has stock
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return false;
    }
    
    // Check stock availability
    if ($quantity > $product['stock']) {
        return false;
    }
    
    $_SESSION['cart'][$productId] = $quantity;
    return true;
}

/**
 * Remove product from cart
 * @param int $productId
 */
function removeFromCart(int $productId): void {
    initCart();
    unset($_SESSION['cart'][$productId]);
}

/**
 * Clear entire cart
 */
function clearCart(): void {
    initCart();
    $_SESSION['cart'] = [];
}

/**
 * Get cart total (calculated server-side)
 * @return float
 */
function getCartTotal(): float {
    $items = getCartItems();
    $total = 0.0;
    
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    
    return $total;
}

/**
 * Get cart item count
 * @return int
 */
function getCartCount(): int {
    initCart();
    $count = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $count += $qty;
    }
    return $count;
}

/**
 * Check if cart is empty
 * @return bool
 */
function isCartEmpty(): bool {
    initCart();
    return empty($_SESSION['cart']);
}

/**
 * Validate cart items have sufficient stock
 * @return array Array of errors, empty if all valid
 */
function validateCartStock(): array {
    initCart();
    $errors = [];
    
    if (empty($_SESSION['cart'])) {
        return ['Cart is empty'];
    }
    
    $pdo = getDB();
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    $productStock = [];
    foreach ($products as $product) {
        $productStock[$product['id']] = $product;
    }
    
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        if (!isset($productStock[$productId])) {
            $errors[] = "Product ID $productId no longer exists";
            continue;
        }
        
        $product = $productStock[$productId];
        if ($quantity > $product['stock']) {
            $errors[] = "Insufficient stock for '{$product['name']}'. Available: {$product['stock']}, Requested: $quantity";
        }
    }
    
    return $errors;
}
