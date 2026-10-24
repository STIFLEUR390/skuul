<div class="flex flex-col gap-10">
    @php
        $controlClasses = 'mt-1 h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
    @endphp

    <section class="flex flex-col gap-3" aria-labelledby="role-heading">
        <div class="flex items-center justify-between gap-3">
            <h2 id="role-heading" class="text-base font-semibold">{{ __('What it hands out') }}</h2>
            @if (!$isEditing && ($canWrite || $canCopy))
                <div class="flex items-center gap-2">
                    @if ($canWrite)
                        <april:button type="button" variant="outline" class="h-11 select-none" wire:click="startEditing">
                            <x-lucide-pencil class="mr-2 size-4" aria-hidden="true" />{{ __('Change') }}
                        </april:button>
                        <april:dropdown-menu>
                            <slot:trigger>
                                <april:button type="button" variant="ghost" size="icon" class="size-11 select-none" aria-label="More for {{ $role->name }}">
                                    <x-lucide-ellipsis class="size-4" />
                                </april:button>
                            </slot:trigger>
                            <slot:content align="end">
                                @if ($role->isArchived())
                                    <april:dropdown-menu-item wire:click="restore"><x-lucide-rotate-ccw class="mr-2 size-4" />{{ __('Offer it again') }}</april:dropdown-menu-item>
                                @else
                                    <april:dropdown-menu-item wire:click="archive" wire:confirm="Stop offering {{ $role->name }}? The people holding it keep it."><x-lucide-archive class="mr-2 size-4" />{{ __('Stop offering it') }}</april:dropdown-menu-item>
                                @endif
                            </slot:content>
                        </april:dropdown-menu>
                    @endif
                </div>
            @endif
        </div>

        @if ($isShared && $canWrite && !$isEditing)
            <p class="text-sm text-muted-foreground">{{ __('Every campus uses this role. A change here makes this campus its own copy, and other campuses keep theirs.') }}</p>
        @elseif ($role->isBuiltIn())
            <p class="text-sm text-muted-foreground">{{ __('The application relies on this role. It can be given out, but not rewritten. Copy it to change the copy.') }}</p>
        @endif

        @if ($isEditing)
            <form wire:submit="save" class="flex flex-col gap-6" aria-label="{{ __('Change the role') }}">
                <div>
                    <label for="description" class="text-sm text-muted-foreground">{{ __('What it is for (optional)') }}</label>
                    <input id="description" wire:model="description" maxlength="255" class="{{ $controlClasses }}" {{ field_error_bindings('description') }}>
                    <x-field-error name="description" class="mt-1" />
                </div>

                <x-role-permissions :grantable="$grantable" />

                <div class="flex justify-end gap-2">
                    <april:button type="button" variant="ghost" class="h-11 select-none" wire:click="stopEditing">{{ __('Cancel') }}</april:button>
                    <april:button type="submit" class="h-11 select-none" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</april:button>
                </div>
            </form>
        @else
            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-muted-foreground">{{ __('State') }}</dt>
                    <dd>{{ $role->isArchived() ? 'No longer offered' : 'Offered' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">{{ __('Held here by') }}</dt>
                    <dd>{{ $holders->count() }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">{{ __('Permissions') }}</dt>
                    <dd>{{ $role->permissions->count() }}</dd>
                </div>
                <div class="sm:col-span-3">
                    <dt class="text-muted-foreground">{{ __('What it is for') }}</dt>
                    <dd>{{ $role->description ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-3">
                    <dt class="text-muted-foreground">{{ __('What it holds') }}</dt>
                    <dd class="break-words">{{ $role->permissions->pluck('name')->sort()->join(', ') ?: '—' }}</dd>
                </div>
            </dl>
        @endif
    </section>

    <section class="flex flex-col gap-3" aria-labelledby="holders-heading">
        <h2 id="holders-heading" class="text-base font-semibold">{{ __('Who holds it here') }}</h2>

        @if ($holders->isEmpty())
            <p class="text-sm text-muted-foreground">{{ __('Nobody at this campus yet.') }}</p>
        @else
            <ul class="divide-y border-y">
                @foreach ($holders as $holder)
                    <li wire:key="holder-{{ $holder->id }}" class="flex items-center gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $holder->name }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ $holder->email ?? '—' }}</p>
                        </div>
                        <april:dropdown-menu>
                            <slot:trigger>
                                <april:button type="button" variant="ghost" size="icon" class="size-11 select-none" aria-label="More for {{ $holder->name }}">
                                    <x-lucide-ellipsis class="size-4" />
                                </april:button>
                            </slot:trigger>
                            <slot:content align="end">
                                <april:dropdown-menu-item wire:click="take({{ $holder->id }})" wire:confirm="Take {{ $role->name }} away from {{ $holder->name }}?"><x-lucide-user-minus class="mr-2 size-4" />{{ __('Take it away') }}</april:dropdown-menu-item>
                            </slot:content>
                        </april:dropdown-menu>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($role->isArchived())
            <p class="text-sm text-muted-foreground">{{ __('Offer the role again before giving it to anybody new.') }}</p>
        @elseif ($people->isEmpty())
            <p class="text-sm text-muted-foreground">{{ __('Everybody who works at this campus already holds it.') }}</p>
        @else
            <form wire:submit="give" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" aria-label="{{ __('Give the role') }}">
                <div>
                    <label for="person_id" class="text-sm text-muted-foreground">{{ __('Person who works here') }}</label>
                    <select id="person_id" wire:model="personId" required class="{{ $controlClasses }}" {{ field_error_bindings('personId') }}>
                        <option value="">{{ __('Choose a person') }}</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}">{{ $person->name }} · {{ $person->email }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="personId" class="mt-1" />
                </div>
                <april:button type="submit" variant="outline" class="h-11 select-none" wire:loading.attr="disabled" wire:target="give">
                    <x-lucide-user-plus class="mr-2 size-4" aria-hidden="true" />{{ __('Give') }}
                </april:button>
            </form>
        @endif
    </section>

    @if ($canCopy)
        <section class="flex flex-col gap-3" aria-labelledby="copy-heading">
            <h2 id="copy-heading" class="text-base font-semibold">{{ __('Start a new role from this one') }}</h2>
            <p class="text-sm text-muted-foreground">{{ __('The copy holds only what you can hand out yourself.') }}</p>
            <form wire:submit="duplicate" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" aria-label="{{ __('Copy the role') }}">
                <div>
                    <label for="copy_name" class="text-sm text-muted-foreground">{{ __('Name of the copy') }}</label>
                    <input id="copy_name" wire:model="copyName" required maxlength="100" class="{{ $controlClasses }}" {{ field_error_bindings('copyName') }}>
                    <x-field-error name="copyName" class="mt-1" />
                </div>
                <april:button type="submit" variant="outline" class="h-11 select-none" wire:loading.attr="disabled" wire:target="duplicate">
                    <x-lucide-copy class="mr-2 size-4" aria-hidden="true" />{{ __('Copy') }}
                </april:button>
            </form>
        </section>
    @endif
</div>
