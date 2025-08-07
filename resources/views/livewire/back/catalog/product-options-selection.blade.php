<div>
    @if ($step == 'start')
        <div class="justify-content-center">
            <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="selectType(1)">
                <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">Jedna Opcija</span>
            </a>
            <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="selectType(2)">
                <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">Dvije Opcije</span>
            </a>
        </div>
    @elseif($step == 'select')
        <div class="row">
            <div class="col-lg-4 col-12">
            <label for="first-option">Prva Opcija</label>
            <select class="js-select2 form-control  form-select-solid" wire:model="select_first_option" style="width: 100%;" data-placeholder="Odaberite opciju">
                <option></option>
                @foreach ($select_options as $option)
                    <option value="{{ $option['id'] }}">{{ $option['title'] }}</option>
                @endforeach
            </select>

            @if ($type == 2)
                <label for="second-option">Druga Opcija</label>
                <select class="js-select2 form-control  form-select-solid" wire:model="select_second_option" style="width: 100%;" data-placeholder="Odaberite opciju">
                    <option></option>
                    @foreach ($select_options as $option)
                        <option value="{{ $option['id'] }}">{{ $option['title'] }}</option>
                    @endforeach
                </select>
            @endif
            </div>
        </div>
    @elseif($step == 'one')
        @foreach ($items as $group => $item)
            <div class="block-header p-0 mb-2" wire:ignore>
                <h3 class="block-title">{{ $option_title }} </h3>
                <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="addItem('{{ $group }}')">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">{{ __('back/attribute.dodaj_vrijednost') }}</span>
                </a>
                <a class="btn btn-info btn-sm ml-2" href="javascript:void(0);" wire:click="addAllDefaultItems('{{ $group }}')">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">Dodaj sve predefinirane vrijednosti</span>
                </a>
            </div>

            <table class="table table-striped table-borderless table-vcenter">
                <thead class="thead-light">
                <tr>
                    <th class="font-size-sm" style="width:25%">Vrijednost</th>
                    <th class="font-size-sm">Šifra <small>(auto)</small></th>
                    <th class="font-size-sm">Količina</th>
                    <th class="font-size-sm">+/- Cijena</th>
                    <th class="text-right font-size-sm"  class="text-center">{{ __('back/attribute.obrisi') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($item['options'] as $key => $option)
                    <tr>
                        <td class="font-size-sm">
                            <select class="js-select2 form-control form-control-sm form-select-solid" id="select-{{ $group }}-{{ $key }}" wire:model="items.{{ $group }}.options.{{ $key }}.value" name="options[{{ $group }}][{{ $key }}][value]" style="width: 100%;" data-placeholder="Odaberite opciju">
                                <option></option>
                                @foreach ($item['selections'] as $selection)
                                    <option value="{{ $selection['id'] }}">{{ $selection['title'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.sku" name="options[{{ $group }}][{{ $key }}][sku]" readonly> </span>
                        </td>
                        <td>
                            <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.qty" name="options[{{ $group }}][{{ $key }}][qty]"> </span>
                        </td>
                        <td>
                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.price" name="options[{{ $group }}][{{ $key }}][price]"></span>
                        </td>
                        <td class="text-right font-size-sm">
                            <a href="javascript:void(0);" wire:click="deleteItem('{{ $group }}',{{ $key }})" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endforeach
    @elseif($step == 'two')
        @foreach ($items as $group => $item)
            <div class="block-header p-0 mb-2" >
                <h3 class="block-title">Glavna opcija: {{ $group }}</h3>
                <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="addItem('{{ $group }}')">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">{{ __('back/attribute.dodaj_vrijednost') }}</span>
                </a>
            </div>

            <table class="table table-striped table-bordered  table-vcenter">
                <thead class="thead-light">
                <tr>
                    <th  class="font-size-sm" >Vrijednost</th>
                    <th class="text-right font-size-sm">Dodaj podopciju</th>
                    <th class="text-right font-size-sm" style="width:10%" class="text-center">{{ __('back/option.obrisi') }}</th>
                </tr>
                </thead>
                <tbody>
                @if (isset($item['options']) && ! empty($item['options']))
                    @foreach ($item['options'] as $key => $option)
                        @php
                            $selections = collect($item['selections'])->sortBy('title');
                        @endphp

                        <tr>
                            <td class="font-size-sm">
                                <select class="js-select2 form-control form-control-sm form-select-solid" id="select-{{ $group }}-{{ $key }}" wire:model="items.{{ $group }}.options.{{ $key }}.value" name="options[{{ $group }}][{{ $key }}][main_id]" style="width: 100%;" data-placeholder="Odaberite opciju">
                                    <option></option>

                                    @foreach ($selections as $selection)
                                        <option value="{{ $selection['id'] }}">{{ $selection['title'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-right font-size-sm">
                                <a class="btn btn-success btn-sm" href="javascript:void(0);" wire:click="addSubItem('{{ $group }}', '{{ $key }}' )">
                                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">Dodaj Vezanu Opciju</span>
                                </a>
                                <a class="btn btn-info btn-sm ml-2" href="javascript:void(0);" wire:click="addAllDefaultSubItems('{{ $group }}', '{{ $key }}')">
                                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">Dodaj sve predefinirane vrijednosti</span>
                                </a>
                                @if($loop->last)
                                    <a class="btn btn-warning btn-sm ml-2" href="javascript:void(0);" wire:click="copySubItemValues('{{ $group }}')">
                                        <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">Kopiraj Vrijednosti</span>
                                    </a>
                                @endif
                            </td>
                            <td class="text-right font-size-sm">
                                <a href="javascript:void(0);" wire:click="deleteItem('{{ $group }}',{{ $key }})" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <table class="table table-bordered table-plain mb-0">
                                    <tr class="thead-gray">
                                        <th class="font-size-sm" style="width:25%">Vrijednost</th>
                                        <th class="font-size-sm">Šifra <small>(auto)</small></th>
                                        <th class="font-size-sm">Količina</th>
                                        <th class="font-size-sm">+/- Cijena</th>
                                        <th class="text-right font-size-sm"  class="text-center">{{ __('back/option.obrisi') }}</th>
                                    </tr>
                                    @foreach ($item['options'][$key]['sub_options'] as $sub_key => $sub_option)
                                        <tr>
                                            <td class="font-size-sm">
                                                <select class="js-select2 form-control form-control-sm form-select-solid" id="select-{{ $group }}-{{ $key }}-{{ $sub_key }}" wire:model="items.{{ $group }}.options.{{ $key }}.sub_options.{{ $sub_key }}.value" name="options[{{ $group }}][{{ $key }}][sub_options][{{ $sub_key }}][id]" style="width: 100%;" data-placeholder="Odaberite opciju">
                                                    <option></option>
                                                    @foreach ($item['sub_selections'] as $sub_selection)
                                                        <option value="{{ $sub_selection['id'] }}">{{ $sub_selection['title'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.sub_options.{{ $sub_key }}.sku" name="options[{{ $group }}][{{ $key }}][sub_options][{{ $sub_key }}][sku]" readonly > </span>
                                            </td>
                                            <td>
                                                <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.sub_options.{{ $sub_key }}.qty" name="options[{{ $group }}][{{ $key }}][sub_options][{{ $sub_key }}][qty]"> </span>
                                            </td>
                                            <td>
                                                <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" wire:model="items.{{ $group }}.options.{{ $key }}.sub_options.{{ $sub_key }}.price" name="options[{{ $group }}][{{ $key }}][sub_options][{{ $sub_key }}][price]"></span>
                                            </td>
                                            <td class="text-right font-size-sm">
                                                <a href="javascript:void(0);" wire:click="deleteItem('{{ $group }}',{{ $key }},{{ $sub_key }})" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        @endforeach
    @else
    @endif
</div>

@push('product_scripts')
    <script>

        Livewire.on('success_alert', () => {

        });

        Livewire.on('error_alert', () => {

        });
    </script>
@endpush
