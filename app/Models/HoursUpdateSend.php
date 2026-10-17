<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoursUpdateSend extends Model
{
    /** @use HasFactory<\Database\Factories\HoursUpdateSendFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'email',
        'hours',
        'sent_at',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
