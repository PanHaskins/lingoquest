<?php

namespace app\controllers\minigames;

use app\config\MySQL;

/**
 * Controller for handling naming minigame logic and API requests
 */
class NamingController
{
    private $db;
    private $sourceLanguage;
    private $targetLanguage;
    private const SESSION_KEY = 'naming_images';
    private const EXPIRATION_TIME = 60; // in seconds

    /**
     * Constructor initializes database connection and languages
     *
     * @param string $sourceLanguage Source language code from routing (kept for consistency)
     * @param string $targetLanguage Target language code (fixed to 'en')
     */
    public function __construct(string $sourceLanguage, string $targetLanguage)
    {
        $this->db = MySQL::getInstance()->getConnection();
        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Handle API requests based on POST data
     *
     * @return void Outputs JSON response and exits
     */
    public function handleApiRequest(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['action'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => __('something_wrong')]);
            exit;
        }

        switch ($input['action']) {
            case 'get_images':
                $categories = $input['categories'] ?? ['all'];
                $images = $this->getImages($categories);
                header('HTTP/1.1 200 OK');
                echo json_encode(['images' => $images], JSON_UNESCAPED_UNICODE);
                exit;

            case 'set_categories':
                $categories = $input['categories'] ?? ['all'];
                $images = $this->getImages($categories);
                $_SESSION[self::SESSION_KEY] = [
                    'images' => $images,
                    'categories' => $this->getCategories(),
                    'expires_at' => time() + self::EXPIRATION_TIME
                ];
                header('HTTP/1.1 200 OK');
                echo json_encode(['images' => $images]);
                exit;

            default:
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => __('something_wrong')]);
                exit;
        }
    }

    /**
     * Fetch images and their English names from the words_from_image table based on categories
     *
     * @param array $categories Array of category IDs or ['all']
     * @return array Array of image data with paths and target language names
     */
    private function getImages(array $categories): array
    {
        $limit = isset($_SESSION['user_id']) ? 20 : 10; // Dynamic limit based on login status
        $query = "SELECT path, {$this->targetLanguage}, category FROM words_from_image";
        $params = [];

        if ($categories !== ['all']) {
            $conditions = array_fill(0, count($categories), "JSON_CONTAINS(category, ?)");
            $query .= " WHERE " . implode(" OR ", $conditions);
            $params = $categories;
        }

        $query .= " ORDER BY RAND() LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($query);

        if ($params) {
            $types = str_repeat('s', count($params) - 1) . 'i';
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $images = [];
        while ($row = $result->fetch_assoc()) {
            $target = json_decode($row[$this->targetLanguage], true);

            // Skip if target language is NULL, empty, or path is invalid
            if (empty($row['path']) || $target === null || empty($target)) {
                continue;
            }

            // Ensure path starts with a slash for correct URL
            $imagePath = $row['path'];
            if (strpos($imagePath, '/') !== 0) {
                $imagePath = '/' . $imagePath;
            }

            $images[] = [
                'image_url' => $imagePath,
                $this->targetLanguage => $target
            ];
        }

        $stmt->close();
        return $images;
    }

    /**
     * Get unique categories from words_from_image table
     *
     * @return array Array of category objects with id and name
     */
    public function getCategories(): array
    {
        // Get distinct category IDs from the JSON array in words_from_image table
        $query = "
            SELECT DISTINCT category
            FROM words_from_image
            WHERE category IS NOT NULL
        ";
        $result = $this->db->query($query);

        $categoryIds = [];
        while ($row = $result->fetch_assoc()) {
            $ids = json_decode($row['category'], true);
            if (is_array($ids)) {
                $categoryIds = array_merge($categoryIds, $ids);
            }
        }
        $categoryIds = array_unique($categoryIds);

        if (empty($categoryIds)) {
            return [];
        }

        // Get category names from the categories table based on extracted IDs
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $query = "SELECT id, name FROM categories WHERE id IN ($placeholders) ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = ['id' => $row['id'], 'name' => $row['name']];
        }
        $stmt->close();

        return $categories;
    }

    /**
     * Get both images and categories from session or database with expiration check
     *
     * @return array Array containing images and categories
     */
    public function getSession(): array
    {
        if (
            isset($_SESSION[self::SESSION_KEY]) &&
            isset($_SESSION[self::SESSION_KEY]['expires_at']) &&
            $_SESSION[self::SESSION_KEY]['expires_at'] > time()
        ) {
            return [
                'images' => $_SESSION[self::SESSION_KEY]['images'],
                'categories' => $_SESSION[self::SESSION_KEY]['categories']
            ];
        }

        $images = $this->getImages(['all']);
        $categories = $this->getCategories();

        $_SESSION[self::SESSION_KEY] = [
            'images' => $images,
            'categories' => $categories,
            'expires_at' => time() + self::EXPIRATION_TIME
        ];

        return [
            'images' => $images,
            'categories' => $categories
        ];
    }
}