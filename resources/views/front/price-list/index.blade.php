@extends('front.layouts.app')
@section('title', 'Digitalni cjenik | Ljekarne PharmAD')
@section('content')
<div class="container py-5">
    <h1>Digitalni cjenik</h1>
    <p>{{ $settings['object_type'] }} — {{ $settings['address'] }} ({{ $settings['object_code'] }})</p>
    @if($exports->first())
        <p><a class="btn btn-primary" href="{{ route('price-list.current') }}">Preuzmi aktualni XML cjenik</a></p>
    @else
        <div class="alert alert-info">Cjenik još nije generiran.</div>
    @endif
    <h2 class="h4 mt-5">Arhiva</h2>
    <div class="table-responsive"><table class="table"><thead><tr><th>Broj pohrane</th><th>Datum i vrijeme</th><th>Broj proizvoda</th><th>Kontrolni zbroj</th></tr></thead><tbody>
        @forelse($exports as $export)<tr><td><a href="{{ route('price-list.archive', $export->filename) }}">{{ $export->sequence }}</a></td><td>{{ $export->generated_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i:s') }}</td><td>{{ $export->product_count }}</td><td><code>{{ $export->checksum }}</code></td></tr>@empty<tr><td colspan="4">Nema arhiviranih verzija.</td></tr>@endforelse
    </tbody></table></div>{{ $exports->links() }}
</div>
@endsection
