<?php

namespace App\Libraries;

use App\Models\NewsModel;

/**
 * Generates URL-friendly slugs with uniqueness check.
 */
class SlugGenerator
{
    /**
     * Generate a slug from a string and ensure uniqueness in the given table.
     */
    public static function generate(string $text, string $table = 'news', string $column = 'slug', ?string $excludeId = null): string
    {
        $slug = self::slugify($text);

        if (empty($slug)) {
            $slug = 'item-' . substr(bin2hex(random_bytes(4)), 0, 8);
        }

        $db = \Config\Database::connect();
        $baseSlug = $slug;
        $counter  = 1;

        while (true) {
            $builder = $db->table($table)->where($column, $slug);
            if ($excludeId !== null) {
                $builder->where('id !=', $excludeId);
            }
            if ($builder->countAllResults() === 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Transliterate and slugify text.
     */
    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Spanish characters
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u', 'ä' => 'a', 'ö' => 'o',
        ];
        $text = strtr($text, $replacements);

        // Remove non-alphanumeric (keep hyphens and spaces)
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);

        // Replace whitespace with hyphens
        $text = preg_replace('/[\s]+/', '-', trim($text));

        // Collapse multiple hyphens
        $text = preg_replace('/-+/', '-', $text);

        return trim($text, '-');
    }
}
