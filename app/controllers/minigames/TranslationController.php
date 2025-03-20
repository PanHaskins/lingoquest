<?php

namespace app\controllers\minigames;

use app\config\MySQL;

/**
 * Controller for handling translation minigame logic and API requests
 */
class TranslationController
{
    private $db;
    private $sourceLanguage;
    private $targetLanguage;
    private const SESSION_KEY = 'translation_vocabulary';
    private const EXPIRATION_TIME = 60; // in seconds

    /**
     * Constructor initializes database connection and languages
     *
     * @param string $sourceLanguage Source language code from routing
     * @param string $targetLanguage Target language code from page
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
            case 'get_vocabulary':
                $categories = $input['categories'] ?? ['all'];
                $vocabulary = $this->getVocabulary($categories);
                header('HTTP/1.1 200 OK');
                echo json_encode(['vocabulary' => $vocabulary], JSON_UNESCAPED_UNICODE);
                exit;

            case 'set_categories':
                $categories = $input['categories'] ?? ['all'];
                $vocabulary = $this->getVocabulary($categories);
                $_SESSION[self::SESSION_KEY] = [
                    'vocabulary' => $vocabulary,
                    'expires_at' => time() + self::EXPIRATION_TIME
                ];
                header('HTTP/1.1 200 OK');
                echo json_encode(['vocabulary' => $vocabulary]);
                exit;

            default:
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => __('something_wrong')]);
                exit;
        }
    }

    /**
     * Fetch vocabulary from database based on categories
     *
     * @param array $categories Array of category IDs or ['all']
     * @return array Vocabulary array with source and target language pairs
     */
    private function getVocabulary(array $categories): array
    {
        $limit = isset($_SESSION['user_id']) ? 20 : 10;
        $query = "SELECT {$this->sourceLanguage}, {$this->targetLanguage} FROM vocabulary";
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

        $vocabulary = [];
        while ($row = $result->fetch_assoc()) {
            $source = json_decode($row[$this->sourceLanguage], true);
            $target = json_decode($row[$this->targetLanguage], true);

            if ($source === null || empty($source) || $target === null || empty($target)) {
                continue;
            }

            $vocabulary[] = [
                $this->sourceLanguage => $source,
                $this->targetLanguage => $target
            ];
        }

        $stmt->close();
        return $vocabulary;
    }

    /**
     * Get unique categories from vocabulary table where source and target languages are present
     *
     * @return array Array of category objects with id and name
     */
    public function getCategories(): array
    {
        // Get distinct category IDs from the JSON array in vocabulary where both languages are present
        $query = "
            SELECT DISTINCT category
            FROM vocabulary
            WHERE category IS NOT NULL 
            AND {$this->sourceLanguage} IS NOT NULL 
            AND {$this->sourceLanguage} != '' 
            AND {$this->targetLanguage} IS NOT NULL 
            AND {$this->targetLanguage} != ''
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
        error_log("Extracted categoryIds: " . print_r($categoryIds, true));

        if (empty($categoryIds)) {
            return [];
        }

        // Load categories names
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

        error_log("Final categories: " . print_r($categories, true));
        return $categories;
    }

    /**
     * Get both vocabulary and categories from session or database with expiration check
     *
     * @return array Array containing vocabulary and categories
     */
    public function getSession(): array
    {
        if (
            isset($_SESSION[self::SESSION_KEY]) &&
            isset($_SESSION[self::SESSION_KEY]['expires_at']) &&
            $_SESSION[self::SESSION_KEY]['expires_at'] > time()
        ) {
            return [
                'vocabulary' => $_SESSION[self::SESSION_KEY]['vocabulary'],
                'categories' => $_SESSION[self::SESSION_KEY]['categories']
            ];
        }

        $vocabulary = $this->getVocabulary(['all']);
        $categories = $this->getCategories();

        $_SESSION[self::SESSION_KEY] = [
            'vocabulary' => $vocabulary,
            'categories' => $categories,
            'expires_at' => time() + self::EXPIRATION_TIME
        ];

        return [
            'vocabulary' => $vocabulary,
            'categories' => $categories
        ];
    }
}