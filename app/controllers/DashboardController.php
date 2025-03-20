<?php

namespace app\controllers;

use app\config\MySQL;

class DashboardController
{
    private $db;

    public function __construct()
    {
        $this->db = MySQL::getInstance()->getConnection();
    }

    /**
     * Get the count of vocabulary rows for a specific language
     *
     * @param string $language Language code to count vocabulary for (e.g., 'en')
     * @return int Number of vocabulary rows with non-empty data for the language
     */
    public function getVocabularyCount(string $language): int
    {
        // Check if count exists in session and return it if available
        if (isset($_SESSION['minigame']['translation'][$language]) && is_int($_SESSION['minigame']['translation'][$language])) {
            return $_SESSION['minigame']['translation'][$language];
        }

        // Prepare query to count rows where the specified language column is not NULL or empty
        $query = "SELECT COUNT(*) as count FROM vocabulary WHERE {$language} IS NOT NULL AND {$language} != '' AND {$language} != '[]'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $count = (int)$row['count'];
        $stmt->close();

        $_SESSION['minigame']['translation'][$language] = $count;

        return $count;
    }

    /**
     * Get the count of image rows for a specific language
     *
     * @param string $language Language code to count image rows for (e.g., 'en')
     * @return int Number of image rows with non-empty data for the language and valid path
     */
    public function getImageCount(string $language): int
    {
        // Check if count exists in session and return it if available
        if (isset($_SESSION['minigame']['naming'][$language]) && is_int($_SESSION['minigame']['naming'][$language])) {
            return $_SESSION['minigame']['naming'][$language];
        }

        // Prepare query to count rows where the specified language column and path are not NULL or empty
        $query = "SELECT COUNT(*) as count FROM words_from_image WHERE {$language} IS NOT NULL AND {$language} != '' AND {$language} != '[]' AND path IS NOT NULL AND path != ''";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $count = (int)$row['count'];
        $stmt->close();

        $_SESSION['minigame']['naming'][$language] = $count;

        return $count;
    }

    /**
     * Get the count of rows in the sentences table for a specific language
     *
     * @param string $language Language code to count sentences for (e.g., 'en')
     * @return int Number of rows in the sentences table matching the language
     */
    public function getSentencesCount(string $language): int
    {
        // Check if count exists in session and return it if available
        if (isset($_SESSION['minigame']['sentences'][$language]) && is_int($_SESSION['minigame']['sentences'][$language])) {
            return $_SESSION['minigame']['sentences'][$language];
        }

        // Prepare query to count rows where the language column contains the specified language code
        $query = "SELECT COUNT(*) as count FROM sentences WHERE language = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $language);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $count = (int)$row['count'];
        $stmt->close();

        $_SESSION['minigame']['sentences'][$language] = $count;

        return $count;
    }
}