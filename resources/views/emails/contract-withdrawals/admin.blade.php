@extends('emails.layouts.customer-notification')

@section('preheader', 'Nova izjava o jednostranom raskidu ugovora')

@section('content')
    <div class="ag-mail-tableset" style="padding:0;">
        <h1 style="margin:0 0 18px;color:#373f50;font-size:27px;line-height:1.35;">Nova izjava o jednostranom raskidu ugovora</h1>

        <p style="margin:0 0 18px;color:#596273;font-size:15px;line-height:1.65;">Kupac je putem webshopa podnio i potvrdio izjavu o raskidu ugovora.</p>

        <div style="margin:20px 0;padding:16px;border-left:4px solid #26b67f;background:#f2fbf7;color:#373f50;font-size:15px;font-weight:700;line-height:1.6;">{{ $withdrawal->declaration }}</div>

        <p><strong>{{ __('contract_withdrawal.withdrawal_scope') }}:</strong> {{ $withdrawal->scope_label }}</p>
        <table role="presentation" style="width:100%;margin:0 !important;table-layout:auto !important;font-size:14px;">
            <tr><td style="width:38%;padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Referenca</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ $withdrawal->reference }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Podneseno</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ optional(optional($withdrawal->submitted_at)->timezone('Europe/Zagreb'))->format('d.m.Y. H:i:s T') }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Ime i prezime</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ $withdrawal->full_name }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">E-mail</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ $withdrawal->email }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Telefon</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ $withdrawal->phone ?: '—' }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Adresa</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ $withdrawal->formatted_address }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Broj narudžbe / ugovora</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ $withdrawal->order_number }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Datum narudžbe</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ optional($withdrawal->contract_date)->format('d.m.Y.') ?: '—' }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Datum primitka proizvoda</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;">{{ optional($withdrawal->received_date)->format('d.m.Y.') ?: '—' }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Proizvodi</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;white-space:pre-line;">{{ $withdrawal->items }}</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #e5e9f0;color:#747d8c;">Napomena</td><td style="padding:8px;border-bottom:1px solid #e5e9f0;white-space:pre-line;">{{ $withdrawal->note ?: '—' }}</td></tr>
        </table>

        <a href="{{ $adminUrl }}" style="display:inline-block;margin-top:24px;padding:13px 22px;border-radius:6px;background:#26b67f;color:#fff;font-weight:700;text-decoration:none;">Otvori u administraciji</a>
    </div>
@endsection
