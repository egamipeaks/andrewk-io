<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceLineType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceLine extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'description',
        'date',
        'type',
        'amount',
        'hourly_rate',
        'hours',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => InvoiceLineType::class,
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
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
        $currency = $this->invoice->currency ?? Currency::USD;

        return $currency->format($this->subtotal);
    }

    public function formattedHourlyRate(): string
    {
        if ($this->hourly_rate === null) {
            return '';
        }
        $currency = $this->invoice->currency ?? Currency::USD;

        return $currency->format($this->hourly_rate);
    }
}
