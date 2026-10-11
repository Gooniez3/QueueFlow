<?php

namespace App\Support;

final class BusinessCategories
{
    /** @return list<array{value: string, slug: string, label: string, icon: string}> */
    public static function all(): array
    {
        return [
            ['value' => 'HEALTH', 'slug' => 'health', 'label' => 'Hospital / Clinic', 'icon' => 'hospital'],
            ['value' => 'FINANCE', 'slug' => 'finance', 'label' => 'Bank / Service', 'icon' => 'landmark'],
            ['value' => 'PUBLIC_SERVICE', 'slug' => 'public-service', 'label' => 'Public Service', 'icon' => 'building-2'],
            ['value' => 'RESTAURANT', 'slug' => 'restaurant', 'label' => 'Restaurant', 'icon' => 'utensils'],
            ['value' => 'EVENT', 'slug' => 'event', 'label' => 'Event', 'icon' => 'calendar-days'],
            ['value' => 'RETAIL_TECH', 'slug' => 'retail-tech', 'label' => 'Retail / Tech', 'icon' => 'shopping-bag'],
            ['value' => 'OTHER', 'slug' => 'other', 'label' => 'Other', 'icon' => 'building-2'],
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
