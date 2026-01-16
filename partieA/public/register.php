<?php
/**
 * Registration Page
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/');
}

$errors = [];
$formData = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid request. Please try again.';
    } else {
        $formData = [
            'name' => sanitize($_POST['name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? '')
        ];
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // Validate
        $errors = validateRequired($formData, ['name', 'email']);
        
        if (!isValidEmail($formData['email'])) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already registered.';
            }
        }
        
        // Create user
        if (empty($errors)) {
            $pdo = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, roles, is_active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $formData['name'],
                $formData['email'],
                password_hash($password, PASSWORD_DEFAULT),
                json_encode(['ROLE_USER'])
            ]);
            
            setFlash('success', 'Registration successful! Please login.');
            redirect('/login.php');
        }
    }
}

$pageTitle = 'Register - Mini E-Commerce';

include __DIR__ . '/../src/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Register</h4>
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
                
                <form method="POST">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label required">Full Name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= e($formData['name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label required">Email Address</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= e($formData['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label required">Password</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" name="password" required minlength="6">
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label required">Confirm Password</label>
                        <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                               id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus"></i> Register
                    </button>
                </form>
                
                <hr>
                
                <p class="text-center mb-0">
                    Already have an account? <a href="/login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
