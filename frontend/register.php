<?php
    session_start();
    $errors = $_SESSION['errors'] ?? [];
    $old    = $_SESSION['old'] ?? [];
    unset($_SESSION['errors'], $_SESSION['old']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoRide</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/auth.css"/>
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-icon">
                <img src="assets/images/Icons/car.svg" class="icon32" alt="">
            </div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Sign up to get started</p>
            <form action="register_action.php" class="auth-form" method="POST" novalidate>

                <!-- Name -->
                <label class="auth-label" for="name">Full Name</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/user.svg" class="icon20" alt="">
                    <input id="name" name="name" type="text" placeholder="John Doe" 
                        value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                </div>

                <?php if (!empty($errors['name'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>

                <!-- Email -->
                <label class="auth-label" for="email">Email</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/mail.svg" class="icon20" alt="">
                    <input id="email" name="email" type="email" placeholder="you@example.com" 
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                </div>

                <?php if (!empty($errors['email'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>

                <!-- Phone -->
                <label class="auth-label" for="phone">Phone Number</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/phone.svg" class="icon20" alt="">
                    <input id="phone" name="phone" type="tel" placeholder="+359 88 123 4567" 
                        value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>

                <?php if (!empty($errors['phone'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['phone']) ?></div>
                <?php endif; ?>

                <!-- Password -->
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input auth-password">
                    <img src="assets/images/Icons/lock.svg" class="icon20" alt="">
                    <input id="password" name="password" type="password" placeholder="••••••••" required>
                    <button type="button" class="eye-btn" data-target="password">
                        <img src="assets/images/Icons/eye-closed.svg" alt="Toggle password">
                    </button>
                </div>

                <?php if (!empty($errors['password'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>

                <!-- Confirm Password -->
                <label class="auth-label" for="password2">Confirm Password</label>
                <div class="auth-input auth-password">
                    <img src="assets/images/Icons/lock.svg" class="icon20" alt="">
                    <input id="password2" name="password_confirm" type="password" placeholder="••••••••" required>
                    <button type="button" class="eye-btn" data-target="password2">
                        <img src="assets/images/Icons/eye-closed.svg" alt="Toggle password">
                    </button>
                </div>

                <?php if (!empty($errors['password_confirm'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors['password_confirmation'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['password_confirmation']) ?></div>
                <?php endif; ?>

                <!-- Terms -->
                <label class="auth-terms">
                    <input type="checkbox" name="terms" value="1" <?= !empty($old['terms']) ? 'checked' : '' ?> required>
                    <span>
                        I agree to the
                        <a href="terms.php" class="auth-link">Terms of Service</a>
                        and
                        <a href="privacy.php" class="auth-link">Privacy Policy</a>
                    </span>
                </label>

                <?php if (!empty($errors['terms'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['terms']) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors['general'])): ?>
                    <div class="form-error"><?= htmlspecialchars($errors['general']) ?></div>
                <?php endif; ?>
                <button class="auth-btn-primary" type="submit">Create Account</button>
            </form>
            <div class="auth-foot">
                <span>Already have an account?</span>
                <a class="auth-link strong" href="login.php">Sign in</a>
            </div>
            <div class="auth-divider">
                <span>Or sign up with</span>
            </div>
            <!-- Google button - does nothing right now FINISH LATER !!! -->
            <button type="button" class="auth-btn-google" onclick="return false;">
                <span class="g-badge">G</span>
                <span>Google</span>
            </button>
            
        </div>
    </div>

    <script src="assets/js/password-eye.js"></script>
</body>
</html>