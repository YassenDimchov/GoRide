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

            <h1 class="auth-title">Welcome Back!</h1>
            <p class="auth-subtitle">Sign in to your account</p>

            <form action="login_action.php" class="auth-form" method="POST">

                <!-- Email -->
                <label class="auth-label" for="email">Email</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/mail.svg" class="icon20" alt="">
                    <input id="email" name="email" type="email" placeholder="you@example.com" required>
                </div>

                <!-- Password -->
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input">
                    <img src="assets/images/Icons/lock.svg" class="icon20" alt="">
                    <input id="password" name="password" type="password" placeholder="••••••••" required>
                </div>

                <div class="auth-row">
                    <label class="auth-check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Remember me</span>
                    </label>
                </div>

                <a class="auth-link" href="forgot_password.php">Forgot password?</a>

                <button class="auth-btn-primary" type="submit">Sign In</button>
            </form>

            <div class="auth-foot">
                <span>Don't have an account?</span>
                <a class="auth-link strong" href="register.php">Sign up</a>
            </div>

            <div class="auth-divider">
                <span>Or continue with</span>
            </div>

            <!-- Google button - does nothing right now FINISH LATER !!!-->
            <button type="button" class="auth-btn-google" onclick="return false;">
                <span class="g-badge">G</span>
                <span>Google</span>
            </button>
            
        </div>
    </div>
</body>
</html>