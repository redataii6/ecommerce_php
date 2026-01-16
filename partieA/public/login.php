<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid request. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errors['login'] = 'Please enter both email and password.';
        } else {
            $user = authenticate($email, $password);
            
            if ($user) {
                loginUser($user);
                setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                
                // Redirect to intended page or home
                $redirect = $_SESSION['redirect_after_login'] ?? '/';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
            } else {
                $errors['login'] = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login - Mini E-Commerce';

include __DIR__ . '/../src/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Login</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= e($email) ?>" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                
                <hr>
                
                <p class="text-center mb-0">
                    Don't have an account? <a href="/register.php">Register here</a>
                </p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Test Accounts</h6>
                <p class="card-text small text-muted mb-1">
                    <strong>User:</strong> user@test.test / password123
                </p>
                <p class="card-text small text-muted mb-0">
                    <strong>Admin:</strong> admin@test.test / adminpass
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
