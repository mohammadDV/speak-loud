<div class="text-sm text-[#3D2B1F]/60 space-y-1">
    <p>Your password must include:</p>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach (App\Support\PasswordRules::requirements() as $requirement)
            <li>{{ $requirement }}</li>
        @endforeach
    </ul>
</div>
