@php
    $paypalEmail = config('services.paypal.donate_email');
    $currency = config('services.paypal.currency', 'USD');
    $itemName = config('services.paypal.item_name', 'SpeakLoudMate donation');
    $amounts = [5, 10, 25, 50];
@endphp

<x-marketing-page
    title="Donate"
    description="Support SpeakLoudMate with a PayPal donation. Help keep free language practice available for learners worldwide."
    heading="Support SpeakLoudMate"
>
    <p>SpeakLoudMate is free for learners. Donations help cover hosting, email, and the tools that keep schedules, claims, and chats running smoothly.</p>
    <p>Every contribution—large or small—helps the community stay open and welcoming.</p>

    <div class="not-prose mt-10">
        <div class="rounded-2xl border border-black/[0.07] bg-white p-5 sm:p-7 shadow-[0_4px_24px_rgba(0,0,0,.07)]">
            <div class="inline-flex items-center gap-1.5 bg-[#FF8C42]/10 text-[#FF8C42] text-[11px] font-semibold px-3 py-1 rounded-full mb-4 tracking-wide uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-[#FF8C42]"></span>
                PayPal
            </div>

            <h2 class="text-xl font-bold text-[#3D2B1F] tracking-tight mb-1.5">Choose an amount</h2>
            <p class="text-sm text-[#3D2B1F]/55 mb-5">You’ll finish securely on PayPal. We never see your card details.</p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5">
                @foreach ($amounts as $amount)
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_donations">
                        <input type="hidden" name="business" value="{{ $paypalEmail }}">
                        <input type="hidden" name="item_name" value="{{ $itemName }}">
                        <input type="hidden" name="currency_code" value="{{ $currency }}">
                        <input type="hidden" name="amount" value="{{ $amount }}">
                        <input type="hidden" name="no_shipping" value="1">
                        <button
                            type="submit"
                            class="w-full rounded-full border border-[#3D2B1F]/15 bg-[#FFF8F0] px-3 py-2.5 text-sm font-semibold text-[#3D2B1F] hover:border-[#FF8C42]/50 hover:text-[#FF8C42] hover:bg-[#FF8C42]/5 transition-all"
                        >
                            ${{ $amount }}
                        </button>
                    </form>
                @endforeach
            </div>

            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                <input type="hidden" name="cmd" value="_donations">
                <input type="hidden" name="business" value="{{ $paypalEmail }}">
                <input type="hidden" name="item_name" value="{{ $itemName }}">
                <input type="hidden" name="currency_code" value="{{ $currency }}">
                <input type="hidden" name="no_shipping" value="1">
                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center bg-[#FF8C42] text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-[#e67a35] transition-colors shadow-[0_2px_12px_rgba(255,140,66,.35)]"
                >
                    Donate with PayPal
                </button>
            </form>

            <p class="mt-4 text-center text-[12px] text-[#3D2B1F]/40">
                Prefer another amount? The PayPal button lets you enter any sum.
            </p>
        </div>
    </div>
</x-marketing-page>
