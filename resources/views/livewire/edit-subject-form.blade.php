<div class="card">
    <div class="card-header">
        <div class="flex items-center gap-1">
            <h3 class="card-title">Edit subject {{$subject->name}}</h3>
            <x-help-tooltip label="{{ __('Subject catalog help') }}">{{ __('Teacher assignments belong to dated course offerings, not the subject catalog.') }}</x-help-tooltip>
        </div>
    </div>
    <div class="card-body">
        <form wire:submit="save" class="space-y-5 md:w-6/12">
        <x-display-validation-errors/>
            <april:input-group id="name" wire:model="name" label="{{ __('Subject name') }}" placeholder="{{ __('Enter subject name') }}" />
            <april:input-group id="short-name" wire:model="short_name" label="{{ __('Subject short name') }}" placeholder="{{ __('Enter subject short name') }}" />
            <april:button type="submit" wire:loading.attr="disabled" class="w-full md:w-1/2">
                <x-lucide-key class="mr-2 size-4" />
                <span wire:loading.remove>{{ __('Save changes') }}</span>
                <span wire:loading>{{ __('Saving…') }}</span>
            </april:button>
        </form>
    </div>
</div>
