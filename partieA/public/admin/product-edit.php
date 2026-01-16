<?php
/**
 * Admin Product Add/Edit Page
 * Handles product creation and editing with image upload
 */

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/config.php';

// Require admin access
requireAdmin();

$pdo = getDB();
$errors = [];
$productId = intval($_GET['id'] ?? 0);
$isEdit = $productId > 0;

// Default form data
$formData = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category' => '',
    'image_path' => ''
];

// Load existing product for editing
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        setFlash('error', 'Product not found.');
        redirect('/admin/products.php');
    }
    
    $formData = [
        'name' => $product['name'],
        'description' => $product['description'],
        'price' => $product['price'],
        'stock' => $product['stock'],
        'category' => $product['category'],
        'image_path' => $product['image_path']
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid request. Please try again.';
    } else {
        $formData = [
            'name' => sanitize($_POST['name'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'category' => sanitize($_POST['category'] ?? ''),
            'image_path' => $formData['image_path'] // Keep existing
        ];
        
        // Validate
        if (empty($formData['name'])) {
            $errors['name'] = 'Product name is required.';
        }
        
        if ($formData['price'] <= 0) {
            $errors['price'] = 'Price must be greater than 0.';
        }
        
        if ($formData['stock'] < 0) {
            $errors['stock'] = 'Stock cannot be negative.';
        }
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = MAX_UPLOAD_SIZE;
            
            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors['image'] = 'Only JPG, PNG, and WebP images are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $errors['image'] = 'Image size must be less than 2MB.';
            } else {
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('product_') . '.' . $ext;
                $uploadDir = __DIR__ . '/../assets/images/products/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old image if exists
                    if ($formData['image_path'] && file_exists(__DIR__ . '/..' . $formData['image_path'])) {
                        @unlink(__DIR__ . '/..' . $formData['image_path']);
                    }
                    $formData['image_path'] = '/assets/images/products/' . $filename;
                } else {
                    $errors['image'] = 'Failed to upload image.';
                }
            }
        }
        
        // Save to database
        if (empty($errors)) {
            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image_path = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $formData['name'],
                    $formData['description'],
                    $formData['price'],
                    $formData['stock'],
                    $formData['category'],
                    $formData['image_path'],
                    $productId
                ]);
                setFlash('success', 'Product updated successfully.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, price, stock, category, image_path, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $formData['name'],
                    $formData['description'],
                    $formData['price'],
                    $formData['stock'],
                    $formData['category'],
                    $formData['image_path']
                ]);
                setFlash('success', 'Product created successfully.');
            }
            
            redirect('/admin/products.php');
        }
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Add') . ' Product - Admin';

include __DIR__ . '/../../src/templates/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/products.php">Products</a></li>
        <li class="breadcrumb-item active"><?= $isEdit ? 'Edit' : 'Add' ?> Product</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?>"></i>
                    <?= $isEdit ? 'Edit Product' : 'Add New Product' ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label required">Product Name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= e($formData['name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= e($formData['description']) ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label required">Price (€)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" 
                                   id="price" name="price" value="<?= e($formData['price']) ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="stock" class="form-label required">Stock</label>
                            <input type="number" min="0" class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>" 
                                   id="stock" name="stock" value="<?= e($formData['stock']) ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" 
                                   value="<?= e($formData['category']) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" 
                               id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Max 2MB. JPG, PNG, or WebP format.</div>
                        
                        <?php if ($formData['image_path']): ?>
                        <div class="mt-2">
                            <img src="<?= e($formData['image_path']) ?>" alt="Current image" 
                                 style="max-width: 200px; max-height: 200px;" class="rounded">
                            <p class="small text-muted mt-1">Current image</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Update' : 'Create' ?> Product
                        </button>
                        <a href="/admin/products.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
