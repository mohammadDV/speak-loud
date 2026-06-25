<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use LogsModelChanges;

    protected $fillable = [
        'user_id', 'category_id', 'subject', 'status',
        'priority', 'assigned_to', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function awaitingStaffReply(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }

    public function userCanReply(): bool
    {
        return in_array($this->status, ['waiting_user', 'resolved'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open', 'in_progress' => 'Waiting for support',
            'waiting_user'        => 'Awaiting your reply',
            'resolved'            => 'Resolved',
            'closed'              => 'Closed',
            default               => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'open', 'in_progress' => 'bg-[#FFD166]/40 text-[#3D2B1F]',
            'waiting_user'        => 'bg-[#FF8C42]/20 text-[#FF8C42]',
            'resolved'            => 'bg-green-100 text-green-700',
            'closed'              => 'bg-[#3D2B1F]/10 text-[#3D2B1F]/60',
            default               => 'bg-[#FFF0E0] text-[#3D2B1F]',
        };
    }

    public function replyBlockedMessage(): string
    {
        return match ($this->status) {
            'closed' => 'This ticket is closed. Open a new ticket if you need more help.',
            default  => 'Our team is reviewing your message. You can reply once they respond.',
        };
    }
}
