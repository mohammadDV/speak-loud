<x-marketing-page
    title="Terms of Service"
    heading="Terms of Service & User Agreement"
    description="Rules, limitations of liability, chat retention, privacy, and security policies for using SpeakLoud."
>
    <p class="text-sm text-[#3D2B1F]/50 -mt-4 mb-8">Last updated: {{ config('legal.terms_version') }}</p>

    <p>
        These Terms of Service and User Agreement (“Terms”) govern your access to and use of SpeakLoud
        (“we”, “us”, “our”). By creating an account or using the service, you agree to these Terms.
        If you do not agree, do not register or use SpeakLoud.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">1. What SpeakLoud provides</h2>
    <p>
        SpeakLoud is a platform that helps language learners discover practice partners, publish
        availability, send claims, and coordinate through messaging. We do not provide language
        instruction, payment processing between members, or guarantees about any user’s identity,
        intentions, or conduct.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">2. Payments and money between users</h2>
    <p>
        <strong>SpeakLoud is not involved in payments between users.</strong> If another member asks you
        for money, gifts, subscriptions, crypto, wire transfers, or any form of payment, that request
        is solely between you and that person. We do not verify payment requests, collect fees on behalf
        of users, or mediate financial disputes.
    </p>
    <p>
        You are responsible for your own financial decisions. Never send money to someone you met on
        SpeakLoud unless you fully trust them and understand the risks.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">3. Links and external content</h2>
    <p>
        Users may share links in profiles, claims, or chat (for example meeting URLs, social profiles,
        or documents). <strong>We do not review, endorse, or guarantee the safety of links sent by
        users.</strong> Clicking a link is at your own risk. Use antivirus software, verify the
        destination, and avoid entering passwords or payment details on untrusted sites.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">4. Fraud, scams, and user conduct</h2>
    <p>
        <strong>We are not liable for fraud, scams, harassment, or illegal activity by other users</strong>,
        including requests for money, impersonation, romance scams, phishing, or misuse of meeting links.
        We may investigate reports and suspend accounts when we become aware of violations, but we cannot
        prevent all harmful behavior.
    </p>
    <p>
        If you believe you are a victim of fraud, contact local authorities and your financial institution
        immediately. You can also report the user through SpeakLoud and email
        <a href="mailto:support@speakloud.app" class="text-[#FF8C42] font-semibold hover:underline">support@speakloud.app</a>.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">5. Chat and message retention</h2>
    <p>
        Messaging on SpeakLoud is provided for coordination around practice sessions. Chat history is
        <strong>temporary</strong>. We may archive, restrict access to, or delete messages after approximately
        <strong>six (6) months</strong>, or sooner if required for security, legal compliance, or system
        maintenance. Do not rely on chat as permanent storage—keep your own records if needed.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">6. Your account and acceptable use</h2>
    <ul>
        <li>You must be at least 13 years old (or the minimum age required in your country) to use SpeakLoud.</li>
        <li>Provide accurate registration information and keep your credentials secure.</li>
        <li>Do not harass, threaten, spam, or post illegal content.</li>
        <li>Do not use SpeakLoud for unsolicited commercial solicitation or pyramid schemes.</li>
        <li>Respect other members’ blocks and reports; attempts to evade blocks may result in suspension.</li>
    </ul>

    <h2 class="font-bold text-[#3D2B1F]">7. Privacy and personal data</h2>
    <p>
        We collect information you provide (such as email, username, profile details, schedules, claims,
        and messages) to operate the service. We use reasonable technical and organizational measures to
        protect data, but no system is completely secure.
    </p>
    <ul>
        <li>We use your data to run features you request (matching, chat, notifications).</li>
        <li>We may process logs (IP address, browser type, timestamps) for security and debugging.</li>
        <li>We do not sell your personal information to third parties for their marketing.</li>
        <li>We may share data when required by law or to protect rights, safety, and integrity of the platform.</li>
        <li>You may request account deletion by contacting support; some data may be retained as required by law.</li>
    </ul>

    <h2 class="font-bold text-[#3D2B1F]">8. Security</h2>
    <p>
        You are responsible for safeguarding your password and devices. Notify us promptly if you suspect
        unauthorized access. We may suspend accounts showing suspicious activity. Public profiles and
        open slots are visible to other users—only share information you are comfortable making public.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">9. Intellectual property</h2>
    <p>
        SpeakLoud’s name, branding, and software are owned by us or our licensors. You retain rights to
        content you submit, but grant us a license to host and display it as needed to operate the service.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">10. Disclaimers and limitation of liability</h2>
    <p>
        THE SERVICE IS PROVIDED “AS IS” AND “AS AVAILABLE” WITHOUT WARRANTIES OF ANY KIND. TO THE
        MAXIMUM EXTENT PERMITTED BY LAW, SPEAKLOUD AND ITS OPERATORS ARE NOT LIABLE FOR INDIRECT,
        INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR FOR LOSS OF PROFITS, DATA, OR
        GOODWILL ARISING FROM YOUR USE OF THE SERVICE OR INTERACTIONS WITH OTHER USERS.
    </p>
    <p>
        Our total liability for any claim relating to the service shall not exceed the greater of (a)
        amounts you paid us in the twelve months before the claim, or (b) one hundred US dollars (USD $100),
        if you have not paid us any fees.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">11. Changes to these Terms</h2>
    <p>
        We may update these Terms from time to time. We will post the new version on this page and update
        the “Last updated” date. Material changes may require you to accept the updated Terms before
        continuing to use certain features.
    </p>

    <h2 class="font-bold text-[#3D2B1F]">12. Contact</h2>
    <p>
        Questions about these Terms:
        <a href="mailto:legal@speakloud.app" class="text-[#FF8C42] font-semibold hover:underline">legal@speakloud.app</a>.
        General support:
        <a href="{{ route('support') }}" class="text-[#FF8C42] font-semibold hover:underline">Support</a>.
    </p>
</x-marketing-page>
