<?php
/**
 * Checkout Page
 * Collects customer info and creates order with prepared statements
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/cart.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/mail.php';

// Check if cart is empty
if (isCartEmpty()) {
    setFlash('error', 'Your cart is empty. Add some products first.');
    redirect('/');
}

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];

// Pre-fill form if user is logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $formData['name'] = $user['name'] ?? '';
    $formData['email'] = $user['email'] ?? '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid request. Please try again.';
    } else {
        // Get and sanitize form data
        $formData = [
            'name' => sanitize($_POST['name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'address' => sanitize($_POST['address'] ?? '')
        ];
        
        // Validate required fields
        $errors = validateRequired($formData, ['name', 'email', 'phone', 'address']);
        
        // Validate email format
        if (!isset($errors['email']) && !isValidEmail($formData['email'])) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        // Validate phone (basic)
        if (!isset($errors['phone']) && strlen($formData['phone']) < 8) {
            $errors['phone'] = 'Please enter a valid phone number';
        }
        
        // Validate cart stock
        $stockErrors = validateCartStock();
        if (!empty($stockErrors)) {
            $errors['stock'] = implode('. ', $stockErrors);
        }
        
        // Process order if no errors
        if (empty($errors)) {
            $pdo = getDB();
            
            try {
                $pdo->beginTransaction();
                
                $cartItems = getCartItems();
                $total = getCartTotal();
                $userId = getCurrentUserId();
                
                // Insert order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, customer_name, customer_email, phone, address, total, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $userId,
                    $formData['name'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['address'],
                    $total
                ]);
                $orderId = $pdo->lastInsertId();
                
                // Insert order items and update stock
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, quantity, price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmtStock = $pdo->prepare("
                    UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
                ");
                
                foreach ($cartItems as $item) {
                    // Insert order item
                    $stmtItem->execute([
                        $orderId,
                        $item['product_id'],
                        $item['name'],
                        $item['quantity'],
                        $item['price']
                    ]);
                    
                    // Update stock
                    $stmtStock->execute([
                        $item['quantity'],
                        $item['product_id'],
                        $item['quantity']
                    ]);
                    
                    if ($stmtStock->rowCount() === 0) {
                        throw new Exception("Insufficient stock for product: {$item['name']}");
                    }
                }
                
                $pdo->commit();
                
                // Clear cart
                clearCart();
                
                // Send confirmation email
                $order = [
                    'id' => $orderId,
                    'customer_name' => $formData['name'],
                    'address' => $formData['address'],
                    'phone' => $formData['phone'],
                    'total' => $total,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $emailItems = [];
                foreach ($cartItems as $item) {
                    $emailItems[] = [
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ];
                }
                
                sendOrderConfirmation($order, $emailItems, $formData['email']);
                
                // Redirect to thank you page
                $_SESSION['last_order_id'] = $orderId;
                redirect('/thankyou.php');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Checkout error: " . $e->getMessage());
                $errors['order'] = 'An error occurred while processing your order. ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Checkout - Mini E-Commerce';
$cartItems = getCartItems();
$cartTotal = getCartTotal();

include __DIR__ . '/../src/templates/header.php';
?>

<h1 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="checkout-form">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label required">Full Name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= e($formData['name']) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label required">Email Address</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= e($formData['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">We'll send your order confirmation to this email.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label required">Phone Number</label>
                        <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                               id="phone" name="phone" value="<?= e($formData['phone']) ?>" required>
                        <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label required">Shipping Address</label>
                        <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" 
                                  id="address" name="address" rows="3" required><?= e($formData['address']) ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                        <div class="invalid-feedback"><?= e($errors['address']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Place Order
                    </button>
                    <a href="/cart.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-arrow-left"></i> Back to Cart
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bag"></i> Order Summary</h5>
            </div>
            <div class="card-body">
                <?php foreach ($cartItems as $item): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>
                        <?= e($item['name']) ?>
                        <small class="text-muted">x<?= $item['quantity'] ?></small>
                    </span>
                    <span><?= formatPrice($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span><?= formatPrice($cartTotal) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping</span>
                    <span class="text-success">Free</span>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between">
                    <span class="fs-5 fw-bold">Total</span>
                    <span class="fs-5 fw-bold text-success"><?= formatPrice($cartTotal) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
