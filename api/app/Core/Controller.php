<?php

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Send JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send success response
     */
    protected function success(string $message, mixed $data = null, int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], $statusCode);
    }

    /**
     * Send error response
     */
    protected function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => time()
        ], $statusCode);
    }

    /**
     * Get request body as array
     */
    protected function getRequestBody(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
