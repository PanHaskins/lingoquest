<?php
use app\models\User;
$path = $routing->getPagePath();
$query = $routing->query;
if (isset($query['action']) && $query['action'] === 'logout') {
    $authController = new \app\controllers\AuthController();
    $authController->handleLogout();
}
?>

<nav class="navbar">
    <div class="navbar-content">
        <a class="logo" href="<?php echo $routing->setURL('/'); ?>">
            <?php echo __('logo'); ?>
        </a>
        
        <div class="nav-right">
            <?php if ($routing->isLoggedIn): ?>
                <!-- Profile icon for logged-in user -->
                <div id="profile-select">
                    <button id="profile-button" aria-haspopup="true" aria-expanded="false">
                        <?php 
                        $user = User::getCurrentUser();
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
                        <img src="<?php echo $avatarPath;?>" alt="Profile" class="avatar">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown">
                        <a href="<?php echo $routing->setURL('profile/'. $_SESSION['user']['id']); ?>"><i class="fa-solid fa-user"></i> <?php echo __('my_profile'); ?></a>
                        <a href="<?php echo $routing->setURL('profile/settings'); ?>"><i class="fa-solid fa-gear"></i> <?php echo __('settings'); ?></a>
                        <a href="<?php echo $routing->setURL('', queryParams: ['action' => 'logout'], resetQuery: true); ?>"><i class="fa-solid fa-right-from-bracket"></i> <?php echo __('logout'); ?></a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Language selection for non-logged-in user -->
                <div id="language-select">
                    <button id="language-button" aria-haspopup="true" aria-expanded="false">
                        <img src="https://flagcdn.com/w40/<?php echo $lang; ?>.webp" alt="<?php echo $lang; ?>">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown">
                        <a href="<?php echo $routing->setURL($path, 'sk'); ?>"><img src="https://flagcdn.com/w40/sk.webp" alt="SK"> Slovenčina</a>
                        <a href="<?php echo $routing->setURL($path, 'cz'); ?>"><img src="https://flagcdn.com/w40/cz.webp" alt="CZ"> Čeština</a>
                        <a href="<?php echo $routing->setURL($path, 'us'); ?>"><img src="https://flagcdn.com/w40/us.webp" alt="US"> English</a>
                        <a href="<?php echo $routing->setURL($path, 'de'); ?>"><img src="https://flagcdn.com/w40/de.webp" alt="DE"> Deutsch</a>
                        <a href="<?php echo $routing->setURL($path, 'fr'); ?>"><img src="https://flagcdn.com/w40/fr.webp" alt="FR"> Français</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="notification-container" id="notificationContainer"></div>