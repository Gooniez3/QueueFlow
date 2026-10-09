<?php

namespace App\Support;

final class BusinessCategories
{
    /** @return list<array{value: string, slug: string, label: string}> */
    public static function all(): array
    {
        return [
            ['value' => 'HEALTH', 'slug' => 'health', 'label' => 'Hospital / Clinic'],
            ['value' => 'FINANCE', 'slug' => 'finance', 'label' => 'Bank / Service'],
            ['value' => 'PUBLIC_SERVICE', 'slug' => 'public-service', 'label' => 'Public Service'],
            ['value' => 'RESTAURANT', 'slug' => 'restaurant', 'label' => 'Restaurant'],
            ['value' => 'EVENT', 'slug' => 'event', 'label' => 'Event'],
            ['value' => 'RETAIL_TECH', 'slug' => 'retail-tech', 'label' => 'Retail / Tech'],
            ['value' => 'OTHER', 'slug' => 'other', 'label' => 'Other'],
        ];
    }

    public static function valueForSlug(string $slug): ?string
    {
        foreach (self::all() as $category) {
            if ($category['slug'] === $slug) {
                return $category['value'];
            }
        }

        return null;
    }

    public static function labelForValue(string $value): ?string
    {
        foreach (self::all() as $category) {
            if ($category['value'] === strtoupper($value)) {
                return $category['label'];
            }
        }

        return null;
    }
}
