<?php

namespace App\Services;

use Carbon\Carbon;

class PublicProductPrice
{
    public function resolve($product, ?Carbon $now = null): array
    {
        $now = $now ?: now();
        $regular = (float) $product->price;
        $special = (float) $product->special;
        $action = $product->relationLoaded('priceListAction') ? $product->priceListAction : null;
        $couponOnly = $action && ! empty($action->coupon);
        $actionEnabled = ! $action || (bool) $action->status;
        $from = $product->special_from && $product->special_from !== '0000-00-00 00:00:00'
            ? Carbon::parse($product->special_from) : null;
        $to = $product->special_to && $product->special_to !== '0000-00-00 00:00:00'
            ? Carbon::parse($product->special_to) : null;
        $active = $special > 0 && $special < $regular && ! $couponOnly && $actionEnabled
            && (! $from || $from->lte($now)) && (! $to || $to->gte($now));

        return [
            'regular' => $regular,
            'current' => $active ? $special : $regular,
            'is_special' => $active,
            'special_name' => $active ? ($action->title ?? 'Akcijska ponuda') : null,
        ];
    }
}
