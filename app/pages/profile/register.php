<?php
use app\controllers\APIController;

if ($routing->isLoggedIn) {
    $routing->redirect('/dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
    APIController::verifyTurnstile($turnstileResponse, $_SERVER['REMOTE_ADDR'], $routing, '/profile/register');

    $authController = new \app\controllers\AuthController();
    $authController->handleRegistration($_POST);
    exit;
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><?php echo __('create_account'); ?></h1>
            <p><?php echo __('register_to_continue'); ?></p>
        </div>
        
        <form id="register" class="auth-form" action="" method="post">
            <div class="form-group">
                <label for="username"><?php echo __('username'); ?></label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="<?php echo __('enter_username'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email"><?php echo __('email'); ?></label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="<?php echo __('enter_email'); ?>" required>
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
                <label for="confirmPassword"><?php echo __('confirm_password'); ?></label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="<?php echo __('confirm_password'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?php echo $_ENV['CLOUDFLARE_CAPTCHA_SITE_KEY']; ?>" data-callback="onTurnstileSuccess"></div>
            </div>

            <div class="form-options">
                <div class="checkbox-group">
                    <input type="checkbox" id="gdpr" name="gdpr" required>
                    <label for="gdpr"><?php echo __('accept_with'); ?> <a class="link" target="_blank" href="/upload/gdpr.pdf"><?php echo __('privacy_policy'); ?></a></label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary submit-btn" aria-label="Register">
                <span><?php echo __('register'); ?></span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-links">
            <p><?php echo __('already_have_account'); ?> <a class="link" href="<?php echo $routing->setURL('/profile/login'); ?>"><?php echo __('login'); ?></a></p>
        </div>
    </div>
</div>

<script>
    const translations = {
        username_invalid: "<?php echo __('username_invalid'); ?>",
        email_invalid: "<?php echo __('email_invalid'); ?>",
        password_invalid: "<?php echo __('password_invalid'); ?>",
        passwords_mismatch: "<?php echo __('passwords_mismatch'); ?>",
    };
</script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>