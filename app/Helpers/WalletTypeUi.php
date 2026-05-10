<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Repositories\WalletTypeRepository;

/**
 * Friendly labels / icon presets for admin wallet-type screens (stored values remain Lucide icon keys).
 */
final class WalletTypeUi
{
    /**
     * @return list<array{lucide: string, label: string, emoji: string}>
     */
    public static function iconChoices(): array
    {
        return [
            ['lucide' => 'wallet', 'label' => 'Wallet', 'emoji' => '👛'],
            ['lucide' => 'landmark', 'label' => 'Bank', 'emoji' => '🏛️'],
            ['lucide' => 'banknote', 'label' => 'Cash', 'emoji' => '💵'],
            ['lucide' => 'credit-card', 'label' => 'Credit card', 'emoji' => '💳'],
            ['lucide' => 'smartphone', 'label' => 'E-wallet', 'emoji' => '📱'],
            ['lucide' => 'piggy-bank', 'label' => 'Savings', 'emoji' => '🐖'],
            ['lucide' => 'trending-up', 'label' => 'Investment', 'emoji' => '📈'],
            ['lucide' => 'coins', 'label' => 'Coins', 'emoji' => '🪙'],
            ['lucide' => 'building-2', 'label' => 'Landmark / building', 'emoji' => '🏢'],
            ['lucide' => 'briefcase', 'label' => 'Business', 'emoji' => '💼'],
            ['lucide' => 'receipt', 'label' => 'Receipt', 'emoji' => '🧾'],
            ['lucide' => 'more-horizontal', 'label' => 'More / other', 'emoji' => '⋯'],
        ];
    }

    /** @return array<string, true> */
    public static function presetIconKeySet(): array
    {
        $s = [];
        foreach (self::iconChoices() as $c) {
            $s[$c['lucide']] = true;
        }

        return $s;
    }

    /**
     * @param mixed $extras Allow additional validated keys beyond presets (typically the row's stored icon).
     */
    public static function sanitizeIcon(mixed $posted, ?string $extras = null): string
    {
        $p = strtolower(trim((string) $posted));
        $presets = self::presetIconKeySet();

        $extraLeg = strtolower(trim((string) ($extras ?? '')));
        if ($extraLeg !== '' && preg_match('/^[a-z0-9-]{1,64}$/', $extraLeg)) {
            $presets[$extraLeg] = true;
        }

        if ($p !== '' && preg_match('/^[a-z0-9-]{1,64}$/', $p) && isset($presets[$p])) {
            return $p;
        }

        return 'wallet';
    }

    /** Derives slug from display name ([a-z0-9_] only). */
    public static function slugFromDisplayName(string $label): string
    {
        $s = strtolower(trim($label));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
        $s = preg_replace('/_+/', '_', $s) ?? '';
        $s = trim((string) $s, '_');
        if (strlen($s) > 64) {
            $s = substr($s, 0, 64);
            $s = rtrim((string) $s, '_');
        }
        if ($s === '' || ! preg_match('/^[a-z0-9_]{1,64}$/', $s)) {
            $s = 'wallet_type_' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        return $s;
    }

    /**
     * Resolves slug for creates: manual override validated, otherwise unique slug from label.
     *
     * @throws \InvalidArgumentException When manual slug invalid or duplicates an existing slug
     */
    public static function resolveNewSlug(WalletTypeRepository $types, string $label, ?string $manualSlug): string
    {
        $manual = $manualSlug !== null ? strtolower(trim($manualSlug)) : '';
        if ($manual !== '') {
            if (! preg_match('/^[a-z0-9_]{1,64}$/', $manual)) {
                throw new \InvalidArgumentException('Internal ID can only use lowercase letters, numbers, and underscores.');
            }
            if ($types->findBySlug($manual) !== null) {
                throw new \InvalidArgumentException('That internal ID is already in use. Choose another or clear the field to auto-generate.');
            }

            return $manual;
        }

        $base = self::slugFromDisplayName($label);
        $slug = $base;
        $n = 2;
        while ($types->findBySlug($slug) !== null) {
            $suffix = '_' . $n;
            $prefix = strlen($base) + strlen($suffix) > 64
                ? substr($base, 0, max(1, 64 - strlen($suffix)))
                : $base;
            $slug = $prefix . $suffix;
            ++$n;
            if ($n > 1000) {
                throw new \InvalidArgumentException('Could not allocate a unique internal ID. Try a slightly different wallet type name.');
            }
        }

        return $slug;
    }
}
