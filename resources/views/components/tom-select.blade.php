@props([
    'endpoint' => null,
    'placeholder' => 'Pilih...',
    'valueField' => 'id',
    'labelField' => 'name',
    'searchField' => "['name']",
    'preload' => 'true',
    'plugins' => "[]",
    'renderOption' => null,
    'renderItem' => null,
])

<div wire:ignore 
    x-data="{
        tomSelectInstance: null,
        value: @entangle($attributes->wire('model')),
        init() {
            if (this.tomSelectInstance) return;
            
            let config = {
                valueField: '{{ $valueField }}',
                labelField: '{{ $labelField }}',
                searchField: {!! $searchField !!},
                loadThrottle: 300,
                controlClass: 'ts-control flex flex-nowrap items-center mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 bg-white py-2 px-3 text-base',
                preload: {{ $preload }},
                maxItems: 1,
                placeholder: '{{ $placeholder }}',
                plugins: {!! $plugins !!},
                onChange: (val) => {
                    this.value = val;
                }
            };

            @if($endpoint)
            config.load = function(query, callback) {
                if (query.length === 1) return callback(); // Ignore 1 character queries
                fetch(`{{ $endpoint }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => callback(json))
                    .catch(() => callback());
            };
            @endif

            @if($renderOption || $renderItem)
            config.render = {
                @if($renderOption) option: {!! $renderOption !!}, @endif
                @if($renderItem) item: {!! $renderItem !!} @endif
            };
            @endif

            this.tomSelectInstance = new TomSelect(this.$refs.select, config);
            
            this.$watch('value', (newValue) => {
                if (!newValue && this.tomSelectInstance) {
                    this.tomSelectInstance.clear(true);
                }
            });

            // Prevent auto-open when Livewire/Alpine modal traps focus on mount
            setTimeout(() => {
                if (document.activeElement === this.tomSelectInstance.control_input) {
                    this.tomSelectInstance.blur();
                }
            }, 10);
        }
    }"
    @set-tomselect-option-{{ $attributes->get('id') }}.window="
        let data = $event.detail[0] || $event.detail;
        if(data && data.id) {
            tomSelectInstance.addOption(data);
            tomSelectInstance.setValue(data.id, true);
        }
    "
>
    <select x-ref="select" {{ $attributes->except('wire:model') }} class="w-full"></select>
</div>
