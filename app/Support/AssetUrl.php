<?php

namespace App\Support;

final class AssetUrl
{
    /**
     * Normalize stored asset URLs into stable public paths.
     */
    public static function normalize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $value = trim(str_replace('\\', '/', $url));
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'writable/uploads/')) {
            return '/uploads/' . ltrim(substr($value, strlen('writable/uploads/')), '/');
        }

        if (str_starts_with($value, '/writable/uploads/')) {
            return '/uploads/' . ltrim(substr($value, strlen('/writable/uploads/')), '/');
        }

        if (str_starts_with($value, 'uploads/')) {
            return '/uploads/' . ltrim(substr($value, strlen('uploads/')), '/');
        }

        if (str_starts_with($value, '/index.php/uploads/')) {
            return '/uploads/' . ltrim(substr($value, strlen('/index.php/uploads/')), '/');
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            $parts = parse_url($value);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $path = (string) ($parts['path'] ?? '');
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

            if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
                $uploadsPos = strpos($path, '/uploads/');
                if ($uploadsPos !== false) {
                    $path = substr($path, $uploadsPos);
                }

                if ($path !== '') {
                    return $path . $query . $fragment;
                }
            }

            return $value;
        }

        return $value;
    }
}
