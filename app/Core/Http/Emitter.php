<?php

declare(strict_types=1);

namespace Ishmael\Core\Http;

/**
 * Emits a Response to the client: status line, headers, and body.
 * In unit tests, this can be bypassed by asserting on the Response directly.
 */
final class Emitter
{
    public function emit(Response $response): void
    {
        // Status
        http_response_code($response->getStatusCode());
// Headers
        foreach ($response->getHeaders() as $name => $value) {
// Avoid headers already sent
            header($name . ': ' . $value, true);
        }

        // Body
        if ($response->isFileResponse() && ($path = $response->getFilePath())) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $handle = fopen($path, 'rb');
            if ($handle) {
                fpassthru($handle);
                fclose($handle);
            }
        } else {
            echo $response->getBody();
        }
    }
}
