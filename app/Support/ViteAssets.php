<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

final class ViteAssets
{
    /**
     * @param list<string> $entrypoints
     */
    public static function render(array $entrypoints): HtmlString
    {
        $vite = app(Vite::class);

        if (self::shouldBypassHotFile()) {
            $vite->useHotFile(storage_path('framework/vite.hot.disabled'));
        }

        return $vite->__invoke($entrypoints);
    }

    private static function shouldBypassHotFile(): bool
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return false;
        }

        $url = trim((string) file_get_contents($hotFile));

        if ($url === '') {
            return true;
        }

        return ! self::devServerReachable($url);
    }

    private static function devServerReachable(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        if (! is_string($host) || ! is_int($port)) {
            return false;
        }

        $address = str_contains($host, ':') ? sprintf('tcp://[%s]:%d', $host, $port) : sprintf('tcp://%s:%d', $host, $port);
        $socket = @stream_socket_client($address, $errorNumber, $errorMessage, 0.2, STREAM_CLIENT_CONNECT);

        if (! is_resource($socket)) {
            return false;
        }

        fclose($socket);

        return true;
    }
}