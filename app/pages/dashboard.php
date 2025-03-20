<h1><?php echo __('select_learn_language')?></h1>
<div class="card-grid">
    <a href="<?php echo $routing->setURL('/dashboard/english'); ?>" class="card">
        <div class="card-header">
            <span></span>
            <span class="status-label"></span>
        </div>
        <div class="card-content">
            <img src="https://flagcdn.com/256x192/gb.webp" alt="English" class="language-flag">
            <div class="language-name">English</div>
        </div>
    </a>
    <a href="<?php echo $routing->setURL('/dashboard/german'); ?>" class="card locked">
        <div class="card-header">
            <i class="fas fa-lock lock-icon"></i>
            <span class="status-label soon"><?php echo __('soon')?></span>
        </div>
        <div class="card-content">
            <img src="https://flagcdn.com/256x192/de.webp" alt="Deutsch" class="language-flag">
            <div class="language-name">Deutsch</div>
        </div>
    </a>
    <a href="<?php echo $routing->setURL('/dashboard/spanish'); ?>" class="card locked">
        <div class="card-header">
            <i class="fas fa-lock lock-icon"></i>
            <span class="status-label soon"><?php echo __('soon')?></span>
        </div>
        <div class="card-content">
            <img src="https://flagcdn.com/256x192/es.webp" alt="Español" class="language-flag">
            <div class="language-name">Español</div>
        </div>
    </a>
    <a href="<?php echo $routing->setURL('/dashboard/russian'); ?>" class="card locked">
        <div class="card-header">
            <i class="fas fa-lock lock-icon"></i>
            <span class="status-label soon"><?php echo __('soon')?></span>
        </div>
        <div class="card-content">
            <img src="https://flagcdn.com/256x192/ru.webp" alt="Русский" class="language-flag">
            <div class="language-name">Русский</div>
        </div>
    </a>
</div>