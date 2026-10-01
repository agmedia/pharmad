@extends('back.layouts.backend')

@section('content')
<div class="bg-body-light"><div class="content content-full"><h1 class="font-size-h2 font-w400 mb-1">Digitalni cjenik</h1><div class="text-muted">Postavke XML objave, ručno generiranje i CSV unos sidrenih cijena</div></div></div>
<div class="content">
    @include('back.layouts.partials.session')
    @if(session('csv_report'))
        @php($report = session('csv_report'))
        <div class="alert alert-info">Ažurirano: <strong>{{ $report['updated_count'] }}</strong>; preskočeno: <strong>{{ $report['error_count'] }}</strong>.
            @if($report['error_count']) <a href="{{ route('digital-price-list.errors') }}">Preuzmi CSV pogrešaka</a>. @endif
        </div>
    @endif
    <div class="row">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('digital-price-list.update') }}">@csrf @method('PATCH')
                <div class="block block-rounded"><div class="block-header block-header-default"><h3 class="block-title">Podaci o objektu i arhivi</h3></div>
                    <div class="block-content">
                        <div class="form-group"><label>Oblik objekta *</label><input class="form-control" name="object_type" required value="{{ old('object_type', $settings['object_type']) }}">@error('object_type')<div class="text-danger">{{ $message }}</div>@enderror</div>
                        <div class="form-group"><label>Adresa *</label><input class="form-control" name="address" required value="{{ old('address', $settings['address']) }}">@error('address')<div class="text-danger">{{ $message }}</div>@enderror</div>
                        <div class="form-row"><div class="form-group col-md-4"><label>Oznaka objekta *</label><input class="form-control" name="object_code" required value="{{ old('object_code', $settings['object_code']) }}"></div>
                            <div class="form-group col-md-4"><label>Vrijeme generiranja *</label><input type="time" class="form-control" name="generation_time" required value="{{ old('generation_time', $settings['generation_time']) }}"></div>
                            <div class="form-group col-md-4"><label>Arhiva (dana) *</label><input type="number" min="30" class="form-control" name="retention_days" required value="{{ old('retention_days', $settings['retention_days']) }}"></div></div>
                    </div><div class="block-content bg-body-light"><button class="btn btn-success mb-3"><i class="fa fa-save mr-1"></i>Spremi postavke</button></div>
                </div>
            </form>
            <div class="block block-rounded"><div class="block-header block-header-default"><h3 class="block-title">CSV sidrene cijene</h3></div><div class="block-content">
                <p class="text-muted">Stupci: <code>sku,sidrena_cijena,referentni_datum</code>, opcionalno <code>jedinica_mjere,cijena_po_jedinici</code>. Podržani su zarez/točka-zarez i datumi YYYY-MM-DD ili DD.MM.YYYY.</p>
                <form method="POST" enctype="multipart/form-data" action="{{ route('digital-price-list.import') }}">@csrf<div class="form-group"><input type="file" name="csv" accept=".csv,text/csv" required class="form-control-file">@error('csv')<div class="text-danger">{{ $message }}</div>@enderror</div><button class="btn btn-primary mb-3">Uvezi CSV</button></form>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="block block-rounded"><div class="block-header block-header-default"><h3 class="block-title">Objava</h3></div><div class="block-content">
                <p><a href="{{ route('price-list.current') }}" target="_blank">{{ route('price-list.current') }}</a></p>
                <form method="POST" action="{{ route('digital-price-list.generate') }}">@csrf<button class="btn btn-warning mb-3">Generiraj sada</button></form>
            </div></div>
            <div class="block block-rounded"><div class="block-header block-header-default"><h3 class="block-title">Zadnje verzije</h3></div><div class="block-content p-0"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Broj</th><th>Vrijeme</th><th>Proizvodi</th></tr></thead><tbody>@forelse($exports as $export)<tr><td><a target="_blank" href="{{ route('price-list.archive', $export->filename) }}">{{ $export->sequence }}</a></td><td>{{ $export->generated_at->format('d.m.Y. H:i') }}</td><td>{{ $export->product_count }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Još nema izvoza.</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </div>
</div>
@endsection
