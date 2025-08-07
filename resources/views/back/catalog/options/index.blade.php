@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/option.naslov') }} </h1>
                <div class="dropdown dropleft push">
                    <button type="button" class="btn btn-hero-success my-2 dropdown-toggle" style="margin-bottom: 0;" id="dropdown-dropleft-dark" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ __('back/option.dodaj_novi') }}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdown-dropleft-dark" style="">
                        <a class="dropdown-item" href="{{ route('options.create', ['type' => 'color']) }}">Boja</a>
                        <a class="dropdown-item" href="{{ route('options.create', ['type' => 'size']) }}">Veličina</a>
<!--                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:void(0)">Something else here</a>-->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content content-full">
    @include('back.layouts.partials.session')

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/option.naslov') }} ({{ $options->total() }})</h3>
            </div>
            <div class="block-content">
                <table class="table table-striped table-borderless table-vcenter">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 80%;">{{ __('back/option.pitanje') }}</th>
                        <th class="text-right"  class="text-center">{{ __('back/option.uredi') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($options as $option)
                        <tr>
                            <td>
                        <span class="font-size-sm"> {{ $option->group }} </span>
                            </td>
                            <td class="text-right font-size-sm">
                                <a class="btn btn-sm btn-alt-secondary" href="{{ route('options.edit', ['options' => $option]) }}">
                                    <i class="fa fa-fw fa-pencil-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="2">{{ __('back/option.nema') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $options->links() }}
            </div>
        </div>
    </div>
@endsection

@push('js_after')

@endpush
