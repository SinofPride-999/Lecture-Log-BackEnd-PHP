<?php

namespace App\Core;

class Request
{
    private array $queryParams;
    private array $bodyParams;
    private array $serverParams;
    private array $headers;

    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->bodyParams = $this->parseBody();
        $this->serverParams = $_SERVER;
        $this->headers = $this->parseHeaders();
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get request URI
     */
    public function getUri(): string
    {
        $uri = parse_url($this->serverParams['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return '/' . trim($uri, '/');
    }

    /**
     * Get query parameter
     */
    public function getQuery(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function getAllQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * Get body parameter
     */
    public function getBody(string $key, $default = null)
    {
        return $this->bodyParams[$key] ?? $default;
    }

    /**
     * Get all body parameters
     */
    public function getAllBody(): array
    {
        return $this->bodyParams;
    }

    /**
     * Get header value
     */
    public function getHeader(string $name, $default = null)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return $this->headers[$name] ?? $default;
    }

    /**
     * Check if request is AJAX/JSON
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('CONTENT_TYPE', '');
        return stripos($contentType, 'application/json') !== false;
    }

    /**
     * Get bearer token from Authorization header
     */
    public function getBearerToken(): ?string
    {
        $authHeader = $this->getHeader('AUTHORIZATION', '');
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Parse request body
     */
    private function parseBody(): array
    {
        $body = file_get_contents('php://input');

        if ($this->isJson() && !empty($body)) {
            return json_decode($body, true) ?? [];
        }

        return $_POST;
    }

    /**
     * Parse HTTP headers
     */
    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($this->serverParams as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
