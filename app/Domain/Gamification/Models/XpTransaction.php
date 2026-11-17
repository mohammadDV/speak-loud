<?php

namespace App\Domain\Gamification\Models;

use App\Domain\Gamification\Enums\XpReason;
use App\Models\User;
use Database\Factories\Gamification\XpTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    /** @use HasFactory<XpTransactionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'source_type',
        'source_id',
        'idempotency_key',
        'local_date',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'reason' => XpReason::class,
            'local_date' => 'date',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function newFactory(): XpTransactionFactory
    {
        return XpTransactionFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
