<?php

namespace App\Core;

/**
 * Controller
 *
 * Base controller class that provides common functionality for all controllers.
 * Every controller in the application should extend this class.
 */
abstract class Controller
{
    /** @var array Data to pass to views */
    protected array $viewData = [];

    /**
     * Render a view file with optional data.
     *
     * @param string $view  Dot-notation path relative to Views/ (e.g., "dashboard.index")
     * @param array  $data  Associative array of variables available in the view
     * @param string|null $layout  Layout to wrap the view (null = default "layouts.main")
     */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts.main'): void
    {
        $data = array_merge($this->viewData, $data);
        extract($data, EXTR_SKIP);

        $viewPath = APP_ROOT . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [{$view}] not found at [{$viewPath}].");
        }

        if ($layout) {
            $layoutPath = APP_ROOT . '/app/Views/' . str_replace('.', '/', $layout) . '.php';

            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout [{$layout}] not found.");
            }

            // Capture the view content
            ob_start();
            require $viewPath;
            $content = ob_get_clean();

            // Render inside layout
            require $layoutPath;
        } else {
            require $viewPath;
        }
    }

    /**
     * Send a JSON response.
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirect to another URL.
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Get a value from the request ($_GET, $_POST, or $_REQUEST).
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Get all request input.
     */
    protected function allInput(): array
    {
        return $_REQUEST;
    }

    /**
     * Set shared view data available to all views rendered by this controller.
     */
    protected function share(string $key, mixed $value): void
    {
        $this->viewData[$key] = $value;
    }
}
