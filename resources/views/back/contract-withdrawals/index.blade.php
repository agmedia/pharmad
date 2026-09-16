@extends('back.layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <div>
                    <h1 class="font-size-h2 font-w400 mt-2 mb-1">Jednostrani raskidi ugovora</h1>
                    <div class="text-muted">Zaprimljene izjave i tijek njihove obrade</div>
                </div>
                <a class="btn btn-alt-primary mt-3 mt-sm-0" href="{{ route('contract-withdrawal-settings.edit') }}">
                    <i class="fa fa-cog mr-1"></i>Postavke
                </a>
            </div>
        </div>
    </div>

    <div class="content">
        @include('back.layouts.partials.session')

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h2 class="block-title">Zaprimljene izjave ({{ number_format($withdrawals->total(), 0, ',', '.') }})</h2>
            </div>

            <div class="block-content bg-body-light">
                <form method="GET" action="{{ route('contract-withdrawals.index') }}">
                    <div class="form-row align-items-end">
                        <div class="col-md-7 mb-3">
                            <label for="withdrawal-search">Pretraživanje</label>
                            <input
                                class="form-control"
                                id="withdrawal-search"
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Referenca, narudžba, ime ili e-mail..."
                            >
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="withdrawal-status">Status</label>
                            <select class="form-control" id="withdrawal-status" name="status">
                                <option value="">Svi statusi</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @if($selectedStatus === $value) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button class="btn btn-primary btn-block" type="submit">
                                <i class="fa fa-search mr-1"></i>Pretraži
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Referenca</th>
                                <th>Podneseno</th>
                                <th>Status</th>
                                <th>Kupac</th>
                                <th>Narudžba</th>
                                <th class="text-center" style="width: 90px;">Akcija</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withdrawals as $withdrawal)
                                <tr>
                                    <td>
                                        <a class="font-w600" href="{{ route('contract-withdrawals.show', $withdrawal) }}">
                                            {{ $withdrawal->reference }}
                                        </a>
                                    </td>
                                    <td class="text-nowrap">{{ optional($withdrawal->submitted_at)->format('d.m.Y. H:i') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $statusColors[$withdrawal->status] ?? 'secondary' }}">
                                            {{ $statuses[$withdrawal->status] ?? $withdrawal->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-w600">{{ $withdrawal->full_name }}</div>
                                        <a href="mailto:{{ $withdrawal->email }}">{{ $withdrawal->email }}</a>
                                    </td>
                                    <td>
                                        @if ($withdrawal->order)
                                            <a href="{{ route('orders.show', $withdrawal->order) }}">#{{ $withdrawal->order->id }}</a>
                                        @else
                                            {{ $withdrawal->order_number }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-alt-primary" href="{{ route('contract-withdrawals.show', $withdrawal) }}" title="Otvori zahtjev">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-muted py-5" colspan="6">Nema pronađenih izjava o raskidu ugovora.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
@endsection
