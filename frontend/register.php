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
            <form action="register_action.php" class="auth-form" method="POST">
                <!-- Name -->
                <label class="auth-label" for="name">Full Name</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/user.svg" class="icon20" alt="">
                    <input id="name" name="name" type="text" placeholder="John Doe" required>
                </div>
                <!-- Email -->
                <label class="auth-label" for="email">Email</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/mail.svg" class="icon20" alt="">
                    <input id="email" name="email" type="email" placeholder="you@example.com" required>
                </div>
                <!-- Phone -->
                <label class="auth-label" for="phone">Phone Number</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/phone.svg" class="icon20" alt="">
                    <input id="phone" name="phone" type="tel" placeholder="+359 88 123 4567" required>
                </div>
                <!-- Password -->
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/lock.svg" class="icon20" alt="">
                    <input id="password" name="password" type="password" placeholder="••••••••" required>
                </div>
                <!-- Confirm Password -->
                <label class="auth-label" for="password2">Confirm Password</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/lock.svg" class="icon20" alt="">
                    <input id="password2" name="password_confirm" type="password" placeholder="••••••••" required>
                </div>
                <!-- Terms -->
                <label class="auth-terms">
                    <input type="checkbox" name="terms" value="1" required>
                    <span>
                        I agree to the
                        <a href="terms.php" class="auth-link">Terms of Service</a>
                        and
                        <a href="privacy.php" class="auth-link">Privacy Policy</a>
                    </span>
                </label>
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
</body>
</html>