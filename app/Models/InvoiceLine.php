<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'description',
        'amount',
        'hourly_rate',
        'hours',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getSubtotalAttribute()
    {
        if ($this->amount !== null) {
            return $this->amount;
        }

        if ($this->hourly_rate !== null && $this->hours !== null) {
            return $this->hourly_rate * $this->hours;
        }

        return 0;
    }

    public function formattedSubTotal(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }
}
