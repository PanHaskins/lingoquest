<?php
require_once __DIR__ . '/vendor/autoload.php';

use app\models\Routing;
use app\controllers\PageController;
use app\controllers\NotificationController;

// Initialize routing
$routing = Routing::init();
$lang = $routing->lang;
$segments = $routing->segments;
$queryParams = $routing->query;

date_default_timezone_set($_ENV['TIMEZONE']);

// Load language file
$translations = @include(__DIR__ . "/lang/{$lang}.php") ?: [];

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

// Check for API in queryParams
if (isset($queryParams['api']) && $queryParams['api'] === 'true') {
    $viewFile = PageController::dispatch($segments);
    include $viewFile;
    exit;
}

// Determine view file
$viewFile = PageController::dispatch($segments);
?>

<!DOCTYPE html>
<html lang="<?php echo __('language'); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo __('title'); ?></title>
        <meta name="description" content="<?php echo __('description'); ?>">
        <meta name="keywords" content="<?php echo __('keywords'); ?> learn, foreign, language, how, speak, write, read, listen, vocabulary, grammar, exercises, quiz, quizzes, tests, english, help, game, lessons, courses, online, free, beginners, advanced, intermediate, interactive, resources, materials, tips, tricks, guides, study, practice, improve, skills, fluency, pronunciation, conversation, dialogues, phrases, idioms, expressions">

        <link rel="icon" type="image/x-icon" href="/upload/system/favicon.ico">
        <link rel="canonical" href="https://lingoquest.eu">

        <?php PageController::render($segments); ?>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <header>
            <?php include 'app/pages/layout/header.php'; ?>
        </header>

        <main>
            <?php include $viewFile; ?>
        </main>

        <footer>
            <?php include 'app/pages/layout/footer.php'; ?>
        </footer>

        <?php NotificationController::showNotification($queryParams); ?>
    </body>
</html>