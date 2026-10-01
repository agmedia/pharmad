<?php

namespace App\Services;

use App\Models\Back\Settings\Settings;

class PriceListSettingsService
{
    public const CODE = 'digital_price_list';

    public function defaults(): array
    {
        return [
            'object_type' => 'webshop',
            'address' => 'Zagrebačka 99, 10291 Prigorje Brdovečko',
            'object_code' => 'WEB-01',
            'generation_time' => '07:00',
            'retention_days' => 35,
        ];
    }

    public function all(): array
    {
        $values = Settings::query()->where('code', self::CODE)->pluck('value', 'key')->all();

        return array_merge($this->defaults(), $values);
    }

    public function save(array $values): void
    {
        foreach (array_keys($this->defaults()) as $key) {
            Settings::query()->updateOrCreate(
                ['code' => self::CODE, 'key' => $key],
                ['value' => (string) $values[$key], 'json' => 0]
            );
        }
    }
}
