<?php

namespace App\Core;

/**
 * ErrorHandler — Last line of defense for uncaught exceptions and fatal errors.
 *
 * Every failure is reported to the server log (SafeLog fingerprint + full
 * detail and stack trace in storage/logs/app.log) and the client only gets a
 * generic error page carrying the fingerprint as a reference. Technical
 * details (message, file, trace) are shown to the client only when APP_DEBUG
 * is on AND APP_ENV is not "production", so a debug flag left on in
 * production can never leak a trace.
 */
final class ErrorHandler
{
    private const FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

    /** APP_ENV values where debug details are allowed; anything else counts as production. */
    private const NON_PRODUCTION_ENVS = ['local', 'development', 'dev', 'testing'];

    private static bool $registered = false;
    private static bool $handling = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        self::$registered = true;
    }

    /** Unknown or missing APP_ENV is treated as production (fail closed). */
    public static function isProduction(): bool
    {
        $appEnv = strtolower(trim((string) env('APP_ENV', 'production')));
        return !in_array($appEnv, self::NON_PRODUCTION_ENVS, true);
    }

    /** Whether technical details may be sent to the client. */
    public static function showDetails(): bool
    {
        return filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN) && !self::isProduction();
    }

    /**
     * Log an exception and return its reference fingerprint.
     *
     * SafeLog::record() writes the short, sanitized line to PHP's error_log;
     * the full message and trace go only to storage/logs/app.log.
     */
    public static function report(\Throwable $e, string $context): string
    {
        $fingerprint = SafeLog::record($context, $e);

        $entry = sprintf(
            "[%s] %s :: fp=%s :: %s: %s in %s:%d%s%s",
            date('Y-m-d H:i:s'),
            $context,
            $fingerprint,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            PHP_EOL,
            $e->getTraceAsString()
        ) . PHP_EOL;

        $logFile = APP_ROOT . '/storage/logs/app.log';
        if (@file_put_contents($logFile, $entry, FILE_APPEND) === false) {
            error_log($entry);
        }

        return $fingerprint;
    }

    public static function handleException(\Throwable $e): void
    {
        if (self::$handling) {
            return;
        }
        self::$handling = true;

        try {
            $ref = self::report($e, 'uncaught');
            self::render($ref, $e);
        } catch (\Throwable) {
            // Never let the handler itself expose anything.
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
            }
            echo 'Ocurrió un error inesperado.';
        }
    }

    /** Catch fatal errors (which bypass the exception handler). */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null || ($error['type'] & self::FATAL_ERRORS) === 0) {
            return;
        }

        self::handleException(new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }

    private static function render(string $ref, \Throwable $e): void
    {
        // Drop any partially rendered view so it is not mixed with the error page.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $details = self::showDetails()
            ? sprintf("%s: %s\n%s:%d\n\n%s", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString())
            : null;

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Error inesperado (ref: {$ref})" . ($details !== null ? PHP_EOL . $details : '') . PHP_EOL);
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (self::wantsJson()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            $payload = ['error' => 'Ocurrió un error inesperado.', 'ref' => $ref];
            if ($details !== null) {
                $payload['details'] = $details;
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        require APP_ROOT . '/app/Views/errors/500.php';
    }

    private static function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return str_contains($accept, 'application/json') || strcasecmp($requestedWith, 'XMLHttpRequest') === 0;
    }
}
