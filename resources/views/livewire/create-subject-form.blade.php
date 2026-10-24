<div class="card">
    <div class="card-header">
        <div class="flex items-center gap-1">
            <h3 class="card-title">{{ __('Create subject') }}</h3>
            <x-help-tooltip label="{{ __('Subject catalog help') }}">{{ __('A subject is the reusable name of what learners study, such as Mathematics, English, or Science. Later, add it to a class and reporting period for a specific school year.') }}</x-help-tooltip>
        </div>
    </div>
    <div class="card-body">
        <form wire:submit="save" class="space-y-5 md:w-1/2">
            <x-display-validation-errors/>
            <april:input-group id="name" wire:model="name" label="{{ __('Subject name') }}" placeholder="{{ __('e.g. Mathematics') }}" />
            <april:input-group id="short-name" wire:model="short_name" label="{{ __('Short name') }}" placeholder="{{ __('e.g. Maths') }}" />
            <april:button type="submit" wire:loading.attr="disabled" class="w-full sm:w-auto">
                <x-lucide-key class="mr-2 size-4" />
                <span wire:loading.remove>{{ __('Create subject') }}</span>
                <span wire:loading>{{ __('Creating…') }}</span>
            </april:button>
        </form>
    </div>
</div>
