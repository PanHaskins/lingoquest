<?php
namespace app\controllers;

class PageController {
    /**
     * Dispatches the request to the appropriate page based on URL segments.
     *
     * @param array $segments The URL segments.
     * @return string The full path to the page file.
     */
    public static function dispatch(array $segments) {
        $pagePath = empty($segments) ? 'home' : implode('/', $segments);
        $fullPath = __DIR__ . "/../../app/pages/{$pagePath}.php";
        return file_exists($fullPath) ? $fullPath : __DIR__ . "/../../app/pages/error/404.php";
    }
    
    /**
     * Renders the CSS and JS files for the page based on URL segments.
     *
     * @param array $segments The URL segments.
     * @return void
     */
    public static function render(array $segments) {
        $cssFiles = ['/css/style.css'];
        $jsFiles = ['/js/header.js', '/js/notification.js'];

        $cumulativePath = '';
        foreach ($segments as $segment) {
            $cumulativePath = ($cumulativePath === '') ? $segment : $cumulativePath . '/' . $segment;

            $cssCandidate = '/css/' . $cumulativePath . '.css';
            $jsCandidate = '/js/' . $cumulativePath . '.js';

            if (file_exists(__DIR__ . '/../../'. $cssCandidate)) {
                $cssFiles[] = $cssCandidate;
            }
            
            if (file_exists(__DIR__ . '/../../'. $jsCandidate)) {
                $jsFiles[] = $jsCandidate;
            }
        }

        switch ($segments[0] ?? '') {
            case '':
                $cssFiles[] = '/css/home.css';
                break;
        }

        foreach ($cssFiles as $file) {
            echo "<link rel='stylesheet' href='{$file}'>";
        }
        foreach ($jsFiles as $file) {
            echo "<script defer src='{$file}'></script>";
        }
    }
}