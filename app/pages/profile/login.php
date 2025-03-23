<?php
use app\controllers\APIController;

if ($routing->isLoggedIn) {
    $routing->redirect('/dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
    APIController::verifyTurnstile($turnstileResponse, $_SERVER['REMOTE_ADDR'], $routing, '/profile/login');

    $authController = new \app\controllers\AuthController();
    $authController->handleLogin($_POST);
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><?php echo __('welcome_back'); ?></h1>
            <p><?php echo __('login_to_continue'); ?></p>
        </div>
        
        <form id="login" class="auth-form" action="" method="post">
            <div class="form-group">
                <label for="username"><?php echo __('username'); ?></label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="<?php echo __('enter_username'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><?php echo __('password'); ?></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="<?php echo __('enter_password'); ?>" required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?php echo $_ENV['CLOUDFLARE_CAPTCHA_SITE_KEY']; ?>" data-callback="onTurnstileSuccess"></div>
            </div>
            
            <div class="form-options">
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember"><?php echo __('remember_me'); ?></label>
                </div>
                <a href="<?php echo $routing->setURL('/profile/reset-password'); ?>" class="forgot-password"><?php echo __('forgot_password'); ?></a>
            </div>
            
            <button type="submit" class="btn btn-primary submit-btn" aria-label="Login">
                <span><?php echo __('login'); ?></span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-links">
            <p><?php echo __('have_not_account'); ?> <a class="link" href="<?php echo $routing->setURL('/profile/register'); ?>" id="registerLink"><?php echo __('register'); ?></a></p>
            <p><?php echo __('continue_as_guest'); ?> <a class="link" href="<?php echo $routing->setURL('/dashboard'); ?>"><?php echo __('click_here'); ?></a></p>
        </div>
    </div>
</div>
<script>
    const translations = {
        username_invalid: "<?php echo __('username_invalid'); ?>",
        password_invalid: "<?php echo __('password_invalid'); ?>",
    };
</script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>