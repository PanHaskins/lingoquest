<?php

use app\controllers\minigames\TranslationController;

// Source language from routing
$source_language = $routing->lang;
$target_language = 'en';
$controller = new TranslationController($source_language, $target_language);

// Handle API request if api=true is in GET
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    $controller->handleApiRequest();
}

// Load initial categories and vocabulary
['vocabulary' => $vocabulary, 'categories' => $categories] = $controller->getSession();
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
    <h2 id="instruction">Translate this word</h2>
    <div class="word-box" id="word"></div>
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
        translate_to_target: "<?php echo __('translate_to_target'); ?>",
        translate_to_source: "<?php echo __('translate_to_source'); ?>",
        correct_answer: "<?php echo __('correct_answer'); ?>",
        incorrect_answer: "<?php echo __('incorrect_answer'); ?>",
        settings_title: "<?php echo __('settings_title'); ?>",
        vocabulary_mode: "<?php echo __('vocabulary_mode'); ?>",
        list_mode: "<?php echo __('list_mode'); ?>",
        custom_mode: "<?php echo __('custom_mode'); ?>",
        select_categories: "<?php echo __('select_categories'); ?>",
        custom_vocabulary: "<?php echo __('custom_vocabulary'); ?>",
        enter_word: "<?php echo __('enter_word'); ?>",
        target_placeholder: "<?php echo __('target_placeholder'); ?>",
        add_row: "<?php echo __('add_row'); ?>",
        categories_loaded: "<?php echo __('categories_loaded'); ?>",
        categories_load_failed: "<?php echo __('categories_load_failed'); ?>",
        server_error: "<?php echo __('server_error'); ?>",
        custom_words_added: "<?php echo __('custom_words_added'); ?>",
        no_custom_words: "<?php echo __('no_custom_words'); ?>"
    };
    const languages = {
        source: "<?php echo $source_language; ?>",
        target: "en"
    };
    const vocabularyCategories = <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE); ?>;
    const defaultVocabulary = <?php echo json_encode($vocabulary, JSON_UNESCAPED_UNICODE); ?>;
</script>