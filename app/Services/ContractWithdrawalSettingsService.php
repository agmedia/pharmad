<?php

namespace App\Services;

use App\Models\Back\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ContractWithdrawalSettingsService
{
    private const CODE = 'store';
    private const KEY = 'contract_withdrawal';
    private const CACHE_KEY = 'settings.contract_withdrawal';

    public function get(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(6),
            fn () => $this->normalize($this->stored())
        );
    }

    public function save(array $data): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        $payload = $this->normalize($data);
        $value = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $setting = Settings::query()
            ->where('code', self::CODE)
            ->where('key', self::KEY)
            ->first();

        $saved = $setting
            ? Settings::edit($setting->id, self::CODE, self::KEY, $value, true)
            : Settings::insert(self::CODE, self::KEY, $value, true);

        if ($saved) {
            Cache::forget(self::CACHE_KEY);
        }

        return (bool) $saved;
    }

    public function defaults(): array
    {
        return [
            'admin_email' => (string) config('mail.admin', 'webshop@ljekarne-pharmad.hr'),
            'return_address' => 'Ljekarne PharmAD, Zagrebačka 99, 10291 Prigorje Brdovečko',
            'return_cost_policy' => 'consumer',
            'instructions' => (string) trans('contract_withdrawal.default_instructions'),
        ];
    }

    public function returnCostText(?array $settings = null): string
    {
        $settings = $settings ?: $this->get();

        return ($settings['return_cost_policy'] ?? 'consumer') === 'merchant'
            ? (string) trans('contract_withdrawal.return_cost_merchant')
            : (string) trans('contract_withdrawal.return_cost_consumer');
    }

    private function stored(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        $setting = Settings::query()
            ->where('code', self::CODE)
            ->where('key', self::KEY)
            ->first();

        if (! $setting) {
            return [];
        }

        return json_decode((string) $setting->value, true) ?: [];
    }

    private function normalize(array $data): array
    {
        $data = array_merge($this->defaults(), $data);

        return [
            'admin_email' => strtolower(trim((string) ($data['admin_email'] ?? ''))),
            'return_address' => trim((string) ($data['return_address'] ?? '')),
            'return_cost_policy' => ($data['return_cost_policy'] ?? '') === 'merchant'
                ? 'merchant'
                : 'consumer',
            'instructions' => trim((string) ($data['instructions'] ?? '')),
        ];
    }
}
