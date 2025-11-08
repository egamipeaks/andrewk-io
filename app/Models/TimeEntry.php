<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'invoice_line_id',
        'date',
        'hours',
        'description',
    ];

    protected $appends = [
        'is_billed',
        'value',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class);
    }

    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->whereNull('invoice_line_id');
    }

    public function scopeBilled(Builder $query): Builder
    {
        return $query->whereNotNull('invoice_line_id');
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    public function scopeForDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    protected function isBilled(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => ! is_null($this->invoice_line_id),
        );
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->hours * ($this->client->hourly_rate ?? 0),
        );
    }
}
