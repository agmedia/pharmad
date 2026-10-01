@php
    $hasSpecial = $product->special() && $product->special() < $product->price;
    $anchorDate = $product->anchor_date ? \Carbon\Carbon::parse($product->anchor_date)->format('d.m.Y.') : null;
@endphp
<div class="anchor-prices {{ ($compact ?? false) ? 'anchor-prices--compact' : 'anchor-prices--detail' }}">
    @if($hasSpecial)
        <div class="anchor-prices__row anchor-prices__row--current">
            <span class="anchor-prices__label">Akcijska cijena</span>
            <span class="anchor-prices__value">{{ $product->main_special_text }}</span>
        </div>
        <div class="anchor-prices__row">
            <span class="anchor-prices__label">NC30</span>
            <span class="anchor-prices__value">{{ $product->main_price_text }}</span>
        </div>
    @else
        <div class="anchor-prices__row anchor-prices__row--current">
            <span class="anchor-prices__label">Cijena</span>
            <span class="anchor-prices__value">{{ $product->main_price_text }}</span>
        </div>
    @endif
    @if($product->anchor_price && $anchorDate)
        <div class="anchor-prices__row anchor-prices__row--anchor">
            <span class="anchor-prices__label">Cijena na {{ $anchorDate }}</span>
            <span class="anchor-prices__value">{{ $product->main_anchor_price_text }}</span>
        </div>
    @endif
</div>
