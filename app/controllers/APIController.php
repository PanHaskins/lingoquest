<?php

namespace app\controllers;

class APIController
{
    /**
     * Executes a POST request to the API.
     *
     * @param string $url The API URL.
     * @param array $data The data to send.
     * @param object $routing The routing object for redirection.
     * @param string $redirectPath The path to redirect to in case of an error.
     * @return array|null The decoded response from the API or null in case of an error.
     */
    private static function makePostRequest($url, $data, $routing, $redirectPath)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || !self::isSuccessHttpCode($httpCode)) {
            curl_close($ch);
            $routing->redirect(url: $redirectPath, queryParams: ['error' => 'something_wrong']);
            exit;
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Checks if the HTTP code indicates success (200-299).
     *
     * @param int $httpCode The HTTP code.
     * @return bool True if the code indicates success.
     */
    private static function isSuccessHttpCode($httpCode)
    {
        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Checks if the response is empty or invalid.
     *
     * @param mixed $response The response from the API.
     * @param object $routing The routing object.
     * @param string $redirectPath The path to redirect to.
     * @return void Redirects in case of an error.
     */
    private static function checkNotResponse($response, $routing, $redirectPath)
    {
        if ($response === null || !is_array($response)) {
            $routing->redirect(url: $redirectPath, queryParams: ['error' => 'something_wrong']);
            exit;
        }
    }

    /**
     * Verifies the Cloudflare Turnstile token.
     *
     * @param string $token The token from the form.
     * @param string|null $remoteIp The user's IP address (optional).
     * @param object $routing The routing object for redirection.
     * @param string $redirectPath The path to redirect to in case of an error.
     * @return bool Returns true if the token is valid.
     */
    public static function verifyTurnstile($token, $remoteIp = null, $routing, $redirectPath)
    {
        $secretKey = $_ENV['CLOUDFLARE_CAPTCHA_SECRET_KEY'];
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        $data = [
            'secret' => $secretKey,
            'response' => $token,
        ];

        if ($remoteIp) {
            $data['remoteip'] = $remoteIp;
        }

        $response = self::makePostRequest($url, $data, $routing, $redirectPath);
        self::checkNotResponse($response, $routing, $redirectPath);

        if (!($response['success'] ?? false)) {
            $routing->redirect(url: $redirectPath, queryParams: ['error' => 'something_wrong']);
            exit;
        }

        return true;
    }
}