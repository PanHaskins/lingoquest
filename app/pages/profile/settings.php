<?php 
use app\models\User;

if (!$routing->isLoggedIn) {
    $routing->redirect('/profile/login');
    exit;
}

$user = User::getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $authController = new \app\controllers\AuthController();

    // Handle profile deletion
    if (!empty($_POST['user_id']) && (int)$_POST['user_id'] === $user->getId()) {
        error_log('Profile deletion initiated by user ID: ' . $user->getId());
        $authController->handleProfileDelete($user->getId());
        exit;
    }

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $authController->handleAvatarUpload($user->getId(), $_FILES['avatar']);
        exit;
    }

    // Handle profile update (including token verification)
    $authController->handleProfileUpdate($routing->query, $user);
}

$avatarPath = "/upload/profile/{$user->getId()}.webp";
if (!file_exists(__DIR__ . '/../../..' . $avatarPath)) {
    $svgPath = "/upload/profile/{$user->getId()}.svg";
    if (file_exists(__DIR__ . '/../../..' . $svgPath)) {
        $avatarPath = $svgPath;
    } else {
        $avatarPath = '/upload/profile/default.webp';
    }
}
?>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1><?php echo htmlspecialchars($user->getUsername() ?? ''); ?> (#<?php echo htmlspecialchars($user->getId() ?? ''); ?>)</h1>
            <p class="member-since"><?php echo (new DateTime($user->getRegisteredAt()->format('Y-m-d')))->format('d.m.Y'); ?></p>
        </div>
        <div class="profile-content">
            <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="<?php echo __('profile_avatar'); ?>" class="avatar">
            <div class="upload-dropzone" id="upload-dropzone">
                <svg class="upload-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15.01l1.41 1.41L11 14.84V19h2v-4.16l1.59 1.59L16 15.01 12.01 11 8 15.01z"/>
                </svg>
                <p class="upload-text"><?php echo __('drag_and_drop'); ?>
                    <button class="btn-secondary" id="browse-file-btn"><?php echo __('browse'); ?></button>
                    <br>256x256 (250KB)
                </p>
                <input type="file" id="avatar-upload" name="avatar" accept="image/png,image/webp,image/jpeg,image/svg+xml" style="display: none;" onchange="triggerUpload(event)">
            </div>
            <div class="upload-progress" id="upload-progress" style="display: none;">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <p class="upload-success" id="upload-success" style="display: none; color: #4CAF50; font-size: 0.875rem; margin-top: 0.5rem;"><?php echo __('file_uploaded'); ?></p>
        </div>
    </div>

    <div class="edit-card">
        <div class="edit-header">
            <h1><?php echo __('edit_profile'); ?></h1>
        </div>
        <div class="edit-content">
            <form id="edit-profile" class="edit-form" method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username"><?php echo __('username'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user->getUsername() ?? ''); ?>" placeholder="<?php echo __('enter_username'); ?>" autocomplete="username">
                    </div>
                    <label for="email"><?php echo __('email'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user->getEmail() ?? ''); ?>" placeholder="<?php echo __('enter_email'); ?>" autocomplete="email">
                    </div>
                    <label for="oldPassword"><?php echo __('old_password'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="oldPassword" name="password" placeholder="<?php echo __('enter_password'); ?>">
                        <button type="button" class="toggle-password"><i class="fas fa-eye-slash"></i></button>
                    </div>
                    <label for="newPassword"><?php echo __('new_password'); ?></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="newPassword" name="new_password" placeholder="<?php echo __('enter_password'); ?>">
                        <button type="button" class="toggle-password"><i class="fas fa-eye-slash"></i></button>
                    </div>
                    <label for="language"><?php echo __('change_language'); ?></label>
                    <div class="input-group select-wrapper">
                        <i class="fas fa-globe"></i>
                        <select id="language" name="language">
                            <option value="sk" <?php echo $user->getLang() === 'sk' ? 'selected' : ''; ?>>Slovenčina</option>
                            <option value="cz" <?php echo $user->getLang() === 'cz' ? 'selected' : ''; ?>>Čeština</option>
                            <option value="us" <?php echo $user->getLang() === 'us' ? 'selected' : ''; ?>>English</option>
                            <option value="de" <?php echo $user->getLang() === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                            <option value="fr" <?php echo $user->getLang() === 'fr' ? 'selected' : ''; ?>>Français</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-primary submit-btn">
                        <span><?php echo __('update_info'); ?></span>
                    </button>
                    <button type="button" class="btn btn-delete" id="delete-profile-btn">
                        <i class="fa-solid fa-trash"></i>
                        <span><?php echo __('delete_profile'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const translations = {
        username_invalid: "<?php echo __('username_invalid'); ?>",
        email_invalid: "<?php echo __('email_invalid'); ?>",
        password_invalid: "<?php echo __('password_invalid'); ?>",
        file_size_limit: "<?php echo __('file_size_limit'); ?>",
        upload_image_only: "<?php echo __('upload_image_only'); ?>",
        avatar_upload_success: "<?php echo __('avatar_upload_success'); ?>",
        avatar_upload_failed: "<?php echo __('avatar_upload_failed'); ?>",
        avatar_upload_error: "<?php echo __('avatar_upload_error'); ?>",
        download_avatar_success: "<?php echo __('download_avatar_success'); ?>",
        confirmation_title: "<?php echo __('confirmation_title'); ?>",
        confirmation_message: "<?php echo __('confirmation_message'); ?>",
        profile_deleted: "<?php echo __('profile_deleted'); ?>",
        something_wrong: "<?php echo __('something_wrong'); ?>",
        yes: "<?php echo __('yes'); ?>",
        no: "<?php echo __('no'); ?>"
    };
</script>