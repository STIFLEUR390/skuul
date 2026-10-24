<div class="card">
    <div class="card-header">
        <h2 class="card-title">{{ __('Create Fee Category') }}</h2>
    </div>
    <div class="card-body">
        <x-display-validation-errors/>
        <form wire:submit="save" class="space-y-5 md:w-6/12">
            <april:input-group id="name" wire:model="name" placeholder="{{ __('Fee Category Name') }}" label="{{ __('Name') }}" />
            <div class="flex w-full flex-col gap-2">
                <april:label for="description">{{ __('Description') }}</april:label>
                <april:textarea id="description" wire:model="description" placeholder="{{ __('Fee Category Description') }}" />
            </div>
            <x-display-validation-errors />
            <april:button type="submit" wire:loading.attr="disabled" class="w-full md:w-1/2">
                <x-lucide-key class="mr-2 size-4" />
                <span wire:loading.remove>{{ __('Create') }}</span>
                <span wire:loading>{{ __('Creating…') }}</span>
            </april:button>
        </form>
    </div>
</div>
