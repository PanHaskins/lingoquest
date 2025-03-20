<?php
if (isset($_GET['mode']) && in_array($_GET['mode'], ['course', 'sandbox'])) {
    $_SESSION['mode'] = $_GET['mode'];
}

$mode = $_SESSION['mode'] ?? 'sandbox';

$dashboardController = new \app\controllers\DashboardController();
$translationCount = $dashboardController->getVocabularyCount('en');
$namingCount = $dashboardController->getImageCount('en');
$fillingCount = $dashboardController->getSentencesCount('en');
?>
<?php if ($mode === 'course'): ?>
    <div class="mode-switch-container">
        <span class="mode-label"><?php echo strtoupper($mode); ?></span>
        <label class="switch">
            <input type="checkbox" id="mode-switch" checked>
            <span class="slider"></span>
        </label>
    </div>
    <h1 style="font-size: 8rem; font-weight: 800; color: var(--primary); line-height: 1; text-align: center;">Coming Soon</h1>
<?php else: ?>
    <div class="mode-switch-container">
        <span class="mode-label"><?php echo strtoupper($mode); ?></span>
        <label class="switch">
            <input type="checkbox" id="mode-switch">
            <span class="slider"></span>
        </label>
    </div>
    <div class="card-grid">
        <a class="card" href="<?php echo $routing->setURL('/dashboard/english/translation'); ?>">
            <div class="card-header">
                <span class="status-label active"><?php echo __('active'); ?></span>
            </div>
            <div class="card-content">
                <img src="/upload/system/sandbox/translation.gif" alt="Icon made by Freepik from www.flaticon.com" class="card-image">
                <h3><?php echo __('word_translation'); ?></h3>
                <p><?php echo __('word_translation_description'); ?></p>
                <div class="stats-container">
                    <div class="stat-box"><i class="fas fa-tasks"></i> <?php echo __('tasks'); ?>: <?php echo $translationCount; ?></div>
                </div>
            </div>
        </a>
        <a class="card" href="<?php echo $routing->setURL('/dashboard/english/naming'); ?>">
            <div class="card-header">
                <span class="status-label active"><?php echo __('active'); ?></span>
            </div>
            <div class="card-content">
                <img src="/upload/system/sandbox/naming-object.gif" alt="Icon made by Freepik from www.flaticon.com" class="card-image">
                <h3><?php echo __('naming_from_pictures'); ?></h3>
                <p><?php echo __('naming_from_pictures_description'); ?></p>
                <div class="stats-container">
                    <div class="stat-box"><i class="fas fa-tasks"></i> <?php echo __('tasks'); ?>: <?php echo $namingCount; ?></div>
                </div>
            </div>
        </a>
        <a class="card" href="<?php echo $routing->setURL('/dashboard/english/filling'); ?>">
            <div class="card-header">
                <span class="status-label active"><?php echo __('active'); ?></span>
            </div>
            <div class="card-content">
                <img src="/upload/system/sandbox/add-word.gif" alt="Icon made by Freepik from www.flaticon.com" class="card-image">
                <h3><?php echo __('word_completion'); ?></h3>
                <p><?php echo __('word_completion_description'); ?></p>
                <div class="stats-container">
                    <div class="stat-box"><i class="fas fa-tasks"></i> <?php echo __('tasks'); ?>: <?php echo $fillingCount; ?></div>
                </div>
            </div>
        </a>
        <a class="card locked" href="<?php echo $routing->setURL('/dashboard/english/writing'); ?>">
            <div class="card-header">
                <span class="status-label soon"><?php echo __('soon'); ?></span>
            </div>
            <div class="card-content">
                <img src="/upload/system/sandbox/writing.gif" alt="Icon made by Freepik from www.flaticon.com" class="card-image">
                <h3><?php echo __('essay_writing'); ?></h3>
                <p><?php echo __('essay_writing_description'); ?></p>
                <div class="stats-container">
                    <div class="stat-box"><i class="fas fa-sync"></i> <?php echo __('attempts'); ?>: 0</div>
                </div>
            </div>
        </a>
        <a class="card locked" href="<?php echo $routing->setURL('/dashboard/english/conversation'); ?>">
            <div class="card-header">
                <span class="status-label soon"><?php echo __('soon'); ?></span>
                <span class="status-label premium"><?php echo __('premium'); ?></span>
            </div>
            <div class="card-content">
                <img src="/upload/system/sandbox/conversation.gif" alt="Icon made by Freepik from www.flaticon.com" class="card-image">
                <h3><?php echo __('real_conversation'); ?></h3>
                <p><?php echo __('real_conversation_description'); ?></p>
                <div class="stats-container">
                    <div class="stat-box"><i class="fas fa-clock"></i> <?php echo __('length'); ?>: 0 m</div>
                </div>
            </div>
        </a>
    </div>
<?php endif; ?>