<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit {{$feeCategory->name}}</h2>
    </div>
    <div class="card-body">
        <form wire:submit="save" class="space-y-5 md:w-6/12">
            <x-display-validation-errors/>
            <april:input-group id="name" wire:model="name" placeholder="{{ __('Fee Category Name') }}" label="{{ __('Name') }}" />
            <div class="flex w-full flex-col gap-2">
                <april:label for="description">{{ __('Description') }}</april:label>
                <april:textarea id="description" wire:model="description" placeholder="{{ __('Fee Category Description') }}" />
            </div>
            <april:button type="submit" wire:loading.attr="disabled" class="w-full md:w-1/2">
                <x-lucide-key class="mr-2 size-4" />
                <span wire:loading.remove>{{ __('Save changes') }}</span>
                <span wire:loading>{{ __('Saving…') }}</span>
            </april:button>
        </form>
    </div>
</div>
