<section class="hero">
    <div class="hero-content">
        <h1><?php echo __('hero_title'); ?></h1>
        <p><?php echo __('hero_description'); ?></p>
        <a href="<?php echo $routing->setURL('/profile/login'); ?>" class="btn btn-primary"><?php echo __('try_free'); ?></a>
    </div>
    <div class="hero-image">
        <img src="/upload/system/book.svg" alt="LingoQuest" width="300" height="200" loading="lazy">
    </div>
</section>

<section class="features">
    <article class="feature">
        <img src="/upload/system/personalised.webp" alt="<?php echo __('feature1_title'); ?>">
        <h3><?php echo __('feature1_title'); ?></h3>
        <p><?php echo __('feature1_description'); ?></p>
    </article>
    <article class="feature">
        <img src="/upload/system/like_a_game.webp" alt="<?php echo __('feature2_title'); ?>">
        <h3><?php echo __('feature2_title'); ?></h3>
        <p><?php echo __('feature2_description'); ?></p>
    </article>
    <article class="feature">
        <img src="/upload/system/practical_use.webp" alt="<?php echo __('feature3_title'); ?>">
        <h3><?php echo __('feature3_title'); ?></h3>
        <p><?php echo __('feature3_description'); ?></p>
    </article>
</section>