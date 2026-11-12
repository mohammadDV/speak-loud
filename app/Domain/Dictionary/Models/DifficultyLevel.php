<?php

namespace App\Domain\Dictionary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DifficultyLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<SenseLevel, $this>
     */
    public function senseLevels(): HasMany
    {
        return $this->hasMany(SenseLevel::class);
    }
}
