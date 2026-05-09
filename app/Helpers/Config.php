<?php

declare(strict_types=1);

namespace App\Helpers;

final class Config
{
    /** @var array<string, mixed> */
    private static array $data = [];

    public static function setArray(string $key, array $value): void
    {
        self::$data[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $cur = self::$data;
        foreach ($parts as $part) {
            if (!is_array($cur) || !array_key_exists($part, $cur)) {
                return $default;
            }
            $cur = $cur[$part];
        }

        return $cur;
    }
}
