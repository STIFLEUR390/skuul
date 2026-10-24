<div class="space-y-6">
    <april:card>
        <slot:title>{{ __('Give organization scope') }}</slot:title>
        <slot:description>
            {{ __('Organization scope opens the organization screens only. It does not open campus records, and it does not change the schools a person already works in.') }}
        </slot:description>
        <slot:content>
            <form wire:submit="grant" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <april:input
                        type="email"
                        wire:model="email"
                        id="member-email"
                        placeholder="person@example.com"
                        aria-label="{{ __('Email address') }}"
                        class="w-full" />
                    <x-field-error name="email" class="mt-1" />
                </div>
                <april:button type="submit">
                    <x-lucide-user-plus class="mr-2 size-4" />
                    {{ __('Give scope') }}
                </april:button>
            </form>
        </slot:content>
    </april:card>

    <april:card>
        <slot:title>{{ __('Organization administrators') }}</slot:title>
        <slot:description>{{ trans_choice('{1} :count person administers|[2,*] :count people administer', $activeMemberships->count()) }} {{ $organization->name }}.</slot:description>
        <slot:content class="space-y-4">
            @error('members')
                <april:alert variant="destructive">
                    <slot:icon><x-lucide-ban class="size-4" /></slot:icon>
                    <slot:title>{{ __('Not done') }}</slot:title>
                    <slot:description>{{ $message }}</slot:description>
                </april:alert>
            @enderror

            @forelse ($activeMemberships as $membership)
                <div class="rounded-lg border p-4" wire:key="member-{{ $membership->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="font-semibold">{{ $membership->user->name }}</p>
                            <p class="text-sm text-muted-foreground">{{ $membership->user->email }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ $membership->user->campus_count }} campus memberships. Removing organization
                                scope leaves those alone.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($membership->hasFullAuthority())
                                <april:badge>{{ __('Organization Administrator') }}</april:badge>
                            @else
                                <april:badge variant="secondary">{{ __('Delegated') }}</april:badge>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($membership->grantedPermissions() as $permission)
                            <span wire:key="member-{{ $membership->id }}-perm-{{ $permission->value }}">
                                <april:badge variant="outline">{{ $permission->label() }}</april:badge>
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <april:button type="button" variant="outline" class="h-11 select-none" wire:click="edit({{ $membership->user_id }})">
                            <x-lucide-sliders-horizontal class="mr-2 size-3.5" />
                            {{ __('Permissions') }}
                        </april:button>

                        @if ($confirmingRemovalUserId === $membership->user_id)
                            <april:button type="button" variant="destructive" class="h-11 select-none" wire:click="revoke({{ $membership->user_id }})">
                                {{ __('Confirm removal') }}
                            </april:button>
                            <april:button type="button" variant="ghost" class="h-11 select-none" wire:click="cancelRemoval">
                                {{ __('Cancel') }}
                            </april:button>
                        @else
                            <april:button type="button" variant="outline" class="h-11 select-none" wire:click="confirmRemoval({{ $membership->user_id }})">
                                <x-lucide-user-minus class="mr-2 size-3.5" />
                                {{ __('Remove scope') }}
                            </april:button>
                        @endif
                    </div>

                    @if ($editingUserId === $membership->user_id)
                        <div class="mt-4 space-y-4 rounded-md border bg-muted/40 p-4">
                            <p class="text-sm font-medium">What may {{ $membership->user->name }} run?</p>

                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    wire:model.live="fullAuthority"
                                    id="full-authority-{{ $membership->id }}"
                                    @checked($fullAuthority)
                                    @disabled(!$this->holdsFullAuthority)
                                    class="mt-0.5 size-4 shrink-0 cursor-pointer accent-primary" />
                                <span>
                                    <span class="font-medium">{{ __('Full authority') }}</span>
                                    <span class="block text-muted-foreground">{{ __('Every organization permission, including future ones.') }}</span>
                                </span>
                            </label>

                            @unless ($fullAuthority)
                                <div class="space-y-3 border-t pt-3">
                                    @foreach ($this->delegablePermissions as $permission)
                                        <label class="flex items-start gap-3 text-sm" wire:key="draft-{{ $membership->id }}-{{ $permission->value }}">
                                            <input
                                                type="checkbox"
                                                wire:model="draftPermissions"
                                                value="{{ $permission->value }}"
                                                @checked(in_array($permission->value, $draftPermissions, true))
                                                @disabled(!in_array($permission, $this->heldPermissions, true))
                                                class="mt-0.5 size-4 shrink-0 cursor-pointer accent-primary"  {{ field_error_bindings('draftPermissions') }}/>
                                            <span>
                                                <span class="font-medium">{{ $permission->label() }}</span>
                                                <span class="block text-muted-foreground">{{ $permission->description() }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                    <p class="text-xs text-muted-foreground">
                                        {{ __('Every member keeps the read permission, so the organization screens still open.') }}
                                    </p>
                                </div>
                            @endunless

                            <x-field-error name="draftPermissions" />

                            <div class="flex flex-wrap gap-2">
                                <april:button type="button" class="h-11 select-none" wire:click="savePermissions">{{ __('Save permissions') }}</april:button>
                                <april:button type="button" variant="ghost" class="h-11 select-none" wire:click="stopEditing">{{ __('Cancel') }}</april:button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted-foreground">{{ __('Nobody administers this organization yet.') }}</p>
            @endforelse
        </slot:content>
    </april:card>

    @if ($pastMemberships->isNotEmpty())
        <april:card>
            <slot:title>{{ __('Past administrators') }}</slot:title>
            <slot:description>{{ __('Scope that was taken away. The record is kept so the history stays readable.') }}</slot:description>
            <slot:content class="space-y-2">
                @foreach ($pastMemberships as $membership)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3" wire:key="past-{{ $membership->id }}">
                        <div>
                            <p class="font-medium">{{ $membership->user->name }}</p>
                            <p class="text-sm text-muted-foreground">{{ $membership->user->email }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <april:badge variant="outline">{{ ucfirst($membership->status->value) }}</april:badge>
                            <span class="text-sm text-muted-foreground">
                                {{ $membership->ended_at?->toFormattedDateString() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </slot:content>
        </april:card>
    @endif
</div>
