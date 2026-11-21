@props([
    'action',
])

@php
    $enabled = \App\Support\Recaptcha::enabled() && filled(\App\Support\Recaptcha::siteKey());
    $siteKey = \App\Support\Recaptcha::siteKey();
@endphp

@once
    @if ($enabled)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}" async defer></script>
        <script>
            window.SpeakLoudRecaptcha = {
                enabled: true,
                siteKey: @js($siteKey),
                execute(action) {
                    return new Promise((resolve, reject) => {
                        const run = () => {
                            if (!window.grecaptcha || typeof window.grecaptcha.execute !== 'function') {
                                reject(new Error('reCAPTCHA is not ready'));
                                return;
                            }

                            window.grecaptcha.ready(() => {
                                window.grecaptcha
                                    .execute(this.siteKey, { action })
                                    .then(resolve)
                                    .catch(reject);
                            });
                        };

                        if (window.grecaptcha) {
                            run();
                            return;
                        }

                        let attempts = 0;
                        const timer = setInterval(() => {
                            attempts += 1;
                            if (window.grecaptcha) {
                                clearInterval(timer);
                                run();
                            } else if (attempts >= 40) {
                                clearInterval(timer);
                                reject(new Error('reCAPTCHA failed to load'));
                            }
                        }, 50);
                    });
                },
            };
        </script>
    @else
        <script>
            window.SpeakLoudRecaptcha = {
                enabled: false,
                execute() {
                    return Promise.resolve('');
                },
            };
        </script>
    @endif
@endonce

@error('recaptchaToken')
    <p {{ $attributes->class('text-sm text-red-600') }}>{{ $message }}</p>
@enderror
