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

    // $action / $method are whitelisted identifiers only — safe to interpolate into Alpine.
    $submitHandler = 'if (submitting) return; submitting = true; window.SpeakLoudRecaptcha.execute('
        .json_encode($action, JSON_THROW_ON_ERROR)
        .').then((token) => $wire.set(\'recaptchaToken\', token)).then(() => $wire.'
        .$method
        .'()).catch(() => $wire.set(\'recaptchaToken\', \'\').then(() => $wire.'
        .$method
        .'())).finally(() => { submitting = false; })';
@endphp

<form
    x-data="{ submitting: false }"
    x-on:submit.prevent="{!! $submitHandler !!}"
    {{ $attributes->class($class) }}
>
    {{ $slot }}
</form>
