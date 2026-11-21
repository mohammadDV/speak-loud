@props([
    'action',
    'method',
    'class' => 'space-y-5',
])

@php
    use App\Support\Recaptcha;

    $allowedMethods = ['register', 'login', 'sendResetLink'];

    if (! in_array($action, Recaptcha::AllowedActions, true) || ! in_array($method, $allowedMethods, true)) {
        throw new InvalidArgumentException('Invalid reCAPTCHA form configuration.');
    }
@endphp

<form
    x-data="{
        submitting: false,
        action: @js($action),
        method: @js($method),
        async submit() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;

            try {
                let token = '';

                try {
                    token = await window.SpeakLoudRecaptcha.execute(this.action);
                } catch (e) {
                    token = '';
                }

                await $wire.set('recaptchaToken', token);
                await $wire[this.method]();
            } finally {
                this.submitting = false;
            }
        }
    }"
    x-on:submit.prevent="submit()"
    {{ $attributes->class($class) }}
>
    {{ $slot }}
</form>
