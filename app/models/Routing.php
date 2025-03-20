<?php
namespace app\models;

use Dotenv\Dotenv;
use app\models\User;

class Routing
{
    public $lang;
    public $url;
    public $query;
    public $segments;
    public $isLoggedIn = false;
    private static $initialized = false;

    /**
     * Initializes the Routing class, sets up session, loads environment variables, and determines the language.
     */
    public function __construct()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_ENV['DEFAULT_LANG']) && !getenv('DEFAULT_LANG')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }

        $this->url = $_SERVER['REQUEST_URI'] ?? '';
        $this->query = array_map('htmlspecialchars', array_merge($_GET ?? [], $_POST ?? []));

        $parts = parse_url($this->url);
        $path = trim($parts['path'] ?? '', '/');
        $this->segments = $path ? explode('/', $path) : [];

        // Include server name in the URL
        $this->url = $_SERVER['SERVER_NAME'] . $this->url;

        $this->lang = $this->determineLanguage();
        self::$initialized = false;
    }

    /**
     * Static method to initialize the Routing class.
     *
     * @return self
     */
    public static function init()
    {
        return new self();
    }

    /**
     * Determines the language to be used based on user settings, URL segments, session, or default settings.
     *
     * @return string The determined language code.
     */
    private function determineLanguage(): string
    {
        $user = User::getCurrentUser();
    
        // 1. Priority: Logged-in user's language
        if ($user && $user->getLang() && $this->isLanguageAvailable($user->getLang())) {
            $_SESSION['lang'] = $user->getLang();
            $this->isLoggedIn = true;
            array_shift($this->segments);
            return $_SESSION['lang'];
        }
    
        // 2. URL segment
        if (!empty($this->segments) && $this->isLanguageAvailable($this->segments[0])) {
            $_SESSION['lang'] = $this->segments[0];
            array_shift($this->segments);
            return $_SESSION['lang'];
        }
    
        // 3. Session language
        if (isset($_SESSION['lang']) && $this->isLanguageAvailable($_SESSION['lang'])) {
            return $_SESSION['lang'];
        }
    
        // 4. Cloudflare country code or default
        $lang = strtolower($_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_ENV['DEFAULT_LANG']);
        if (!$this->isLanguageAvailable($lang)) {
            $lang = $_ENV['DEFAULT_LANG'];
        }
        $_SESSION['lang'] = $lang;
    
        // Redirect to language-prefixed URL if the first segment is not a valid language
        if (empty($this->segments) || !$this->isLanguageAvailable($this->segments[0])) {
            $queryString = !empty($this->query) ? '?' . http_build_query($this->query) : '';
            $path = implode('/', $this->segments);
            header("Location: /{$lang}/{$path}{$queryString}");
            exit;
        }
    
        return $lang;
    }

    /**
     * Checks if the given language is available.
     *
     * @param string $lang The language code to check.
     * @return bool True if the language is available, false otherwise.
     */
    private function isLanguageAvailable($lang): bool
    {
        return file_exists(__DIR__ . "/../../lang/{$lang}.php");
    }

    /**
     * Returns the path of the current page based on URL segments.
     *
     * @return string The page path.
     */
    public function getPagePath(): string
    {
        return empty($this->segments) ? '' : implode('/', $this->segments);
    }

    /**
     * Returns the query parameters as a JSON string.
     *
     * @return string The query parameters in JSON format.
     */
    public function getQueryAsJson(): string
    {
        return json_encode($this->query);
    }

    /**
     * Builds a URL based on the given path, language, and query parameters.
     *
     * @param string $path The path (e.g., 'home'), default is empty; if empty, uses the current path.
     * @param ?string $overrideLang The language to override the default ($this->lang), default is null.
     * @param array $queryParams Query parameters (e.g., ['error' => 'something_wrong']), default is an empty array.
     * @return string The generated URL (e.g., '/en/home?error=something_wrong').
     */
    private function buildURL(string $path = '', ?string $overrideLang = null, array $queryParams = []): string
    {
        $lang = $overrideLang ?? $this->lang;

        if ($path === '') {
            $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = trim($currentUri ?? '', '/');
            $segments = explode('/', $path);
            if ($this->isLanguageAvailable($segments[0])) {
                array_shift($segments);
                $path = implode('/', $segments);
            }
        }

        if ($path === '/') {
            $path = '';
        }

        $path = trim(preg_replace('#/{2,}#', '/', $path), '/');
        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
        return "/{$lang}/{$path}{$queryString}";
    }

    /**
     * Redirects to the given URL with optional query parameters.
     * If resetQuery is not true, existing query parameters from the URL are merged with the new ones.
     *
     * @param string $url The path or full URL (e.g., '/en/home' or '/en/home?foo=bar').
     * @param array $queryParams New query parameters to add to the existing ones (if resetQuery is not true).
     * @param bool $resetQuery If true, existing query parameters from the URL are ignored.
     */
    public static function redirect(string $url, array $queryParams = [], bool $resetQuery = false): void
    {
        $routing = new self();
        $parts = parse_url($url);
        $path = trim($parts['path'] ?? '', '/');
        $firstSegment = $path ? explode('/', $path)[0] : '';
        $lang = $routing->lang;

        if ($firstSegment && $routing->isLanguageAvailable($firstSegment)) {
            $lang = null;
        }

        $finalParams = $queryParams;
        if (!$resetQuery && isset($parts['query'])) {
            parse_str($parts['query'], $existingParams);
            $finalParams = array_merge($existingParams, $queryParams);
        }

        $url = $routing->buildURL($path, $lang, $finalParams, $resetQuery);
        header("Location: " . $url);
        exit;
    }

    /**
     * Returns a URL string based on the given path, language, and query parameters.
     *
     * @param string $path The path (e.g., 'home'), default is empty; if empty, uses the current path.
     * @param ?string $overrideLang The language to override the default ($this->lang), default is null.
     * @param array $queryParams Query parameters (e.g., ['error' => 'something_wrong']), default is an empty array.
     * @param bool $resetQuery If true, existing query parameters are ignored, default is false.
     * @return string The generated URL (e.g., '/en/home?error=something_wrong').
     */
    public function setURL(string $path = '', ?string $overrideLang = null, array $queryParams = [], bool $resetQuery = false): string
    {
        return $this->buildURL($path, $overrideLang, $queryParams, $resetQuery);
    }
}