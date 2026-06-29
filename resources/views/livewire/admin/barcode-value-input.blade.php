<div>
  <div class="relative mt-1 w-full">
    <x-input name="value" id="value" class="block w-full pr-10" type="text" placeholder="Kode Barcode"
      wire:model="value" />
    <button type="button" wire:click="generate" class="absolute inset-y-0 right-0 flex items-center pr-3 text-sky-500 hover:text-sky-700 focus:outline-none" title="Generate New Barcode">
      <x-heroicon-o-arrow-path class="h-5 w-5" />
    </button>
  </div>
  @error('value')
    <x-input-error for="value" class="mt-2" message="{{ $message }}" />
  @enderror
</div>
