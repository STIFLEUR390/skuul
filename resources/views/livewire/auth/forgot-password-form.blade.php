<div class="space-y-6">
    <form action="{{ route('password.email') }}" method="POST" class="grid gap-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf

        <april:input-group name="email" id="email" type="email" label="{{ __('Email address') }}" autocomplete="email" autofocus required />
        <april:button type="submit" class="w-full justify-center" x-bind:disabled="submitting" x-bind:aria-busy="submitting">
            <span x-show="! submitting">{{ __('Email reset link') }}</span>
            <span x-show="submitting" x-cloak>{{ __('Working...') }}</span>
        </april:button>
    </form>

    <p class="text-center text-sm text-muted-foreground">
        {{ __('Remember your password?') }}
        <a href="{{ route('login') }}" class="font-medium text-foreground underline-offset-4 hover:underline" aria-label="{{ __('Login') }}">
            {{ __('Back to login') }}
        </a>
    </p>
</div>
