<?php
namespace app\controllers;

class NotificationController {
    public static function showNotification($queryParams) {
        $allowedTypes = ['error', 'warning', 'info', 'success'];
        foreach ($allowedTypes as $type) {
            if (isset($queryParams[$type]) && !empty($queryParams[$type])) {
                $message = $queryParams[$type];
                echo "<script defer>showNotification('{$type}', '" . __(addslashes($message)) . "');</script>";
                break;
            }
        }
    }
}