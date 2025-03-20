<?php

namespace app\controllers\minigames;

use app\config\MySQL;

class FillingController
{
    private $db;
    private $sourceLanguage;
    private $targetLanguage;
    private const SESSION_KEY = 'filling_sentences';
    private const EXPIRATION_TIME = 60;

    public function __construct(string $sourceLanguage, string $targetLanguage)
    {
        $this->db = MySQL::getInstance()->getConnection();
        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguage = $targetLanguage;
    }

    public function handleApiRequest(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['action'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => __('something_wrong')]);
            exit;
        }

        switch ($input['action']) {
            case 'get_sentences':
                $categories = $input['categories'] ?? ['all'];
                $sentences = $this->getSentences($categories);
                header('HTTP/1.1 200 OK');
                echo json_encode(['sentences' => $sentences], JSON_UNESCAPED_UNICODE);
                exit;

            case 'set_categories':
                $categories = $input['categories'] ?? ['all'];
                $sentences = $this->getSentences($categories);
                $_SESSION[self::SESSION_KEY] = [
                    'sentences' => $sentences,
                    'expires_at' => time() + self::EXPIRATION_TIME
                ];
                header('HTTP/1.1 200 OK');
                echo json_encode(['sentences' => $sentences]);
                exit;

            default:
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => __('something_wrong')]);
                exit;
        }
    }

    /**
     * Fetch sentences from database based on categories
     *
     * @param array $categories Array of category IDs or ['all']
     * @return array Sentences array with sentence, correct answer and options
     */
    private function getSentences(array $categories): array
    {
        $limit = isset($_SESSION['user_id']) ? 20 : 10;
        $query = "SELECT sentence, correct_answer, options FROM sentences";
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

        $sentences = [];
        while ($row = $result->fetch_assoc()) {
            $options = json_decode($row['options'], true);

            if ($options === null || empty($options)) {
                continue;
            }

            $sentences[] = [
                'sentence' => $row['sentence'],
                'correct_answer' => $row['correct_answer'],
                'options' => $options
            ];
        }

        $stmt->close();
        return $sentences;
    }

    /**
     * Get unique categories from sentences table
     *
     * @return array Array of category objects with id and name
     */
    public function getCategories(): array
    {
        // Get distinct category IDs from the JSON array in sentences table
        $query = "
            SELECT DISTINCT category
            FROM sentences
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
     * Get both sentences and categories from session or database with expiration check
     *
     * @return array Array containing sentences and categories
     */
    public function getSession(): array
    {
        if (
            isset($_SESSION[self::SESSION_KEY]) &&
            isset($_SESSION[self::SESSION_KEY]['expires_at']) &&
            $_SESSION[self::SESSION_KEY]['expires_at'] > time()
        ) {
            return [
                'sentences' => $_SESSION[self::SESSION_KEY]['sentences'],
                'categories' => $_SESSION[self::SESSION_KEY]['categories']
            ];
        }

        $sentences = $this->getSentences(['all']);
        $categories = $this->getCategories();

        $_SESSION[self::SESSION_KEY] = [
            'sentences' => $sentences,
            'categories' => $categories,
            'expires_at' => time() + self::EXPIRATION_TIME
        ];

        return [
            'sentences' => $sentences,
            'categories' => $categories
        ];
    }
}