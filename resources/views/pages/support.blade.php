<x-marketing-page
    title="Support"
    description="Help with SpeakLoud accounts, schedules, claims, safety reports, and technical issues."
>
    <p>Check the <a href="{{ route('faq.index') }}" class="text-[#FF8C42] font-semibold hover:underline">FAQ</a> for quick answers about schedules, claims, and profiles.</p>
    @auth
        <p>Signed in? <a href="{{ route('tickets.index') }}" class="text-[#FF8C42] font-semibold hover:underline">Open a support ticket</a> and chat with our team in the app.</p>
    @else
        <p><a href="{{ route('login') }}" class="text-[#FF8C42] font-semibold hover:underline">Sign in</a> to open a support ticket and get replies in the app.</p>
    @endauth
    <p>You can also email <a href="mailto:support@speakloud.app" class="text-[#FF8C42] font-semibold hover:underline">support@speakloud.app</a> with your username and a short description of the issue.</p>
    <p>To report another member, open their profile or chat and use the report option—our team reviews reports promptly.</p>
</x-marketing-page>
