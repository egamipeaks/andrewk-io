<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceEmailSend extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'invoice_email_sends';

    protected $fillable = [
        'invoice_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getSentAtAttribute($value)
    {
        return Carbon::parse($value)->setTimezone('America/Chicago');
    }
}
