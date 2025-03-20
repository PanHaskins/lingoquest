<?php
use app\controllers\APIController;

if ($routing->isLoggedIn) {
    $routing->redirect('/dashboard');
    exit;
}

$showChangePasswordForm = isset($_SESSION['user']['reset_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$showChangePasswordForm) {
        $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
        APIController::verifyTurnstile($turnstileResponse, $_SERVER['REMOTE_ADDR'], $routing, '/profile/reset-password');
    }

    $authController = new \app\controllers\AuthController();
    $authController->handleForgotPassword($routing->query);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($routing->query['password']) && $showChangePasswordForm) {
        $authController->handleChangePassword($routing->query, $_SESSION['user']['reset_id']);
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><?php echo __('forgot_password'); ?></h1>
            <p><?php echo $showChangePasswordForm ? __('enter_new_password') : __('enter_email_to_reset'); ?></p>
        </div>

        <?php if ($showChangePasswordForm): ?>
            <form id="change-password" class="auth-form" action="" method="post">
                <div class="form-group">
                    <label for="password"><?php echo __('new_password'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="<?php echo __('enter_password'); ?>" required>
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><?php echo __('confirm_password'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo __('confirm_password'); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary submit-btn" aria-label="Change Password">
                    <span><?php echo __('change_password'); ?></span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        <?php else: ?>
            <form id="forgot-password" class="auth-form" action="" method="post">
                <div class="form-group">
                    <label for="email"><?php echo __('email'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="<?php echo __('enter_email'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="cf-turnstile" data-sitekey="<?php echo $_ENV['CLOUDFLARE_CAPTCHA_SITE_KEY']; ?>" data-callback="onTurnstileSuccess"></div>
                </div>
                <button type="submit" class="btn btn-primary submit-btn" aria-label="Reset Password">
                    <span><?php echo __('reset_password'); ?></span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php endif; ?>

        <div class="auth-links">
            <p><?php echo __('already_have_account'); ?> <a class="link" href="<?php echo $routing->setURL('/profile/login'); ?>"><?php echo __('login'); ?></a></p>
        </div>
    </div>
</div>