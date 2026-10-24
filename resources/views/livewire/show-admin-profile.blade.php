<div class="flex flex-col gap-8">
    <livewire:show-user-profile :user="$admin" />

    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 border-y py-4 sm:grid-cols-3" aria-label="{{ __('School access') }}">
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Account') }}</dt>
            <dd class="flex min-h-11 items-center text-sm font-medium">{{ $admin->account_status->label() }}</dd>
        </div>
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Membership') }}</dt>
            <dd @class(['flex min-h-11 items-center text-sm font-medium', 'text-muted-foreground' => $membership?->status->value !== 'active'])>{{ $membership?->status->label() ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Joined') }}</dt>
            <dd class="flex min-h-11 items-center text-sm">{{ school_time($membership?->joined_at)?->format('j M Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Roles') }}</dt>
            <dd class="text-sm">{{ $roles === [] ? '—' : implode(', ', $roles) }}</dd>
        </div>
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Invitation') }}</dt>
            <dd class="text-sm">{{ $pendingInvitation ? 'Pending until '.school_time($pendingInvitation->expires_at)?->format('j M Y') : '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Primary school') }}</dt>
            <dd class="text-sm">{{ $membership?->is_primary ? 'Yes' : '—' }}</dd>
        </div>
    </dl>
</div>
