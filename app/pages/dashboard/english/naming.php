<?php

use app\controllers\minigames\NamingController;

// Source language from routing
$source_language = $routing->lang;
$target_language = 'en';
$controller = new NamingController($source_language, $target_language);

// Handle API request if api=true is in GET
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    $controller->handleApiRequest();
}

// Load initial images and categories
['images' => $images, 'categories' => $categories] = $controller->getSession();
?>

<div class="top-buttons">
    <a href="#" onclick="goBack()"><i class="fas fa-arrow-left"></i></a>
    <button id="settings-btn"><i class="fas fa-cog"></i></button>
</div>
<div class="progress-container">
    <div class="progress-stats">
        <span class="correct"><?php echo __('correct'); ?>: <span id="correct-count">0</span></span>
        <span class="percentage" id="percentage">50%</span>
        <span class="incorrect"><?php echo __('incorrect'); ?>: <span id="incorrect-count">0</span></span>
    </div>
    <div class="progress-bar">
        <div class="progress-correct" style="width: 50%"></div>
        <div class="progress-incorrect" style="width: 50%"></div>
    </div>
</div>
<div class="game-container">
    <h2 id="instruction"><?php echo __('name_picture').strtoupper($target_language); ?></h2>
    <div class="image-box" id="image-container"><img id="image" src="" alt="Icons made by Freepik from www.flaticon.com"></div>
    <div class="input-group">
        <input type="text" id="answer-input" placeholder="<?php echo __('write_answer'); ?>" autofocus>
        <button onclick="checkAnswer()"><i class="fas fa-arrow-right"></i></button>
    </div>
    <div class="feedback" id="feedback"></div>
    <p class="hint"><?php echo __('word_grammar_hint'); ?></p>
</div>
<div id="settings-modal" class="modal"></div>

<script>
    const translations = {
        yes: "<?php echo __('yes'); ?>",
        no: "<?php echo __('no'); ?>",
        incorrect_answer: "<?php echo __('incorrect_answer'); ?>",
        server_error: "<?php echo __('server_error'); ?>",
        settings_title: "<?php echo __('settings_title'); ?>",
        select_categories: "<?php echo __('select_categories'); ?>",
        categories_loaded: "<?php echo __('categories_loaded'); ?>",
        categories_load_failed: "<?php echo __('categories_load_failed'); ?>"
    };
    const languages = {
        source: "<?php echo $source_language; ?>",
        target: "en"
    };
    const imageCategories = <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE); ?>;
    const defaultImages = <?php echo json_encode($images, JSON_UNESCAPED_UNICODE); ?>;
</script>