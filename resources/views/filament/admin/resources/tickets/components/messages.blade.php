<div class="flex max-h-80 flex-col gap-4 overflow-y-auto pe-1">
    @forelse ($this->messages as $message)
        @php
            $isStaff = $message->sender?->isStaff() ?? false;
            $senderName = $message->sender?->profile?->display_name
                ?? $message->sender?->email
                ?? 'Unknown';
        @endphp
        <div @class([
            'rounded-xl border px-4 py-4',
            'border-orange-200/80 bg-orange-50/70 dark:border-orange-500/25 dark:bg-orange-500/10' => $message->is_internal,
            'border-gray-200 bg-gray-50/80 dark:border-white/10 dark:bg-white/[0.03]' => ! $message->is_internal,
        ])>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-gray-800 dark:text-gray-100">
                    {{ $senderName }}
                    @if ($isStaff)
                        <span class="text-primary-600 dark:text-primary-400">· Staff</span>
                    @endif
                    @if ($message->is_internal)
                        <span class="text-amber-600 dark:text-amber-400">· Internal note</span>
                    @endif
                </span>
                <span class="shrink-0">{{ $message->created_at?->format('M j, Y g:i A') }}</span>
            </div>
            <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-800 dark:text-gray-100">
                {{ $message->body }}
            </p>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-200 px-6 py-10 text-center dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">No messages yet.</p>
        </div>
    @endforelse
</div>
