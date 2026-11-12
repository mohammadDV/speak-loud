<?php

namespace App\Domain\Dictionary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartOfSpeech extends Model
{
    use HasFactory;

    protected $table = 'parts_of_speech';

    protected $fillable = [
        'code',
        'display_name',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Sense, $this>
     */
    public function senses(): HasMany
    {
        return $this->hasMany(Sense::class);
    }
}
