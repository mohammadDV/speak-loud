<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    use LogsModelChanges;

    public $timestamps = false;

    protected $fillable = ['name', 'slug'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}
