@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/option.naslov') }}</h1>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($options) ? route('options.update', ['options' => $options]) : route('options.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($options))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('options') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/option.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="faq-switch" name="status"{{ (isset($options->status) and $options->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="faq-switch">{{ __('back/option.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-12">

                            <div class="form-group">
                                <label for="title-input">{{ __('back/option.pitanje') }}</label>
                                <input type="text" class="form-control" id="title-input" name="title" value="{{ isset($options) ? $options->grupa : old('title') }}">
                            </div>

                            <div class="form-group ">
                                <label for="title-input">{{ __('back/option.tip') }}</label>
                                <select class="js-select2 form-control form-control" id="tip" name="type" style="width: 100%;" data-placeholder="Odaberite opciju">
                                    <option></option>
                                    <option value="color" @if(request()->has('type') && request()->input('type') == 'color') selected @endif @if(isset($options) && $options->type == 'color') selected @endif>Boja</option>
                                    <option value="size" @if(request()->has('type') && request()->input('type') == 'size') selected @endif @if(isset($options) && $options->type == 'size') selected @endif>Veličina</option>
                                </select>

                            </div>
                            {{-- if tip == 2--}}
                            <div class="form-group">
                                <div id="addition">
                                    @livewire('back.catalog.options-addition', ['values' => isset($options) ? $options : [], 'type' => isset($options) ? $options->type : 'text'])
                                </div>
                            </div>
                            {{-- end if--}}
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/option.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            @if (isset($options))
                                <a href="{{ route('options.destroy', ['options' => $options]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/option.obrisi') }}" onclick="event.preventDefault(); document.getElementById('delete-option-form{{ $options->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/option.obrisi') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($options))
            <form id="delete-option-form{{ $options->id }}" action="{{ route('options.destroy', ['options' => $options]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>


@endsection

@push('js_after')



@endpush
