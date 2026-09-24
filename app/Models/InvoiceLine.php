<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceLineType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class InvoiceLine extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'project_id',
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

    protected static function booted(): void
    {
        static::saving(function (InvoiceLine $line): void {
            if ($line->project_id === null) {
                return;
            }

            if (! $line->isDirty(['project_id', 'invoice_id'])) {
                return;
            }

            $clientId = Invoice::query()->whereKey($line->invoice_id)->value('client_id');

            if (! Project::belongsToClient((int) $line->project_id, $clientId)) {
                throw new InvalidArgumentException("Project {$line->project_id} does not belong to client {$clientId}.");
            }
        });

        static::updated(function (InvoiceLine $line): void {
            if (! $line->wasChanged('project_id')) {
                return;
            }

            $line->timeEntries()->update(['project_id' => $line->project_id]);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    public function subtotalInClientCurrency(): float
    {
        $rate = $this->invoice->conversion_rate ?? $this->invoice->currency->fromUsdRate();

        return round($this->subtotal * $rate, 2);
    }

    public function hourlyRateInClientCurrency(): float
    {
        if (! $this->hourly_rate) {
            return 0;
        }

        $rate = $this->invoice->conversion_rate ?? $this->invoice->currency->fromUsdRate();

        return round($this->hourly_rate * $rate, 2);
    }

    public function amountInClientCurrency(): float
    {
        if (! $this->amount) {
            return 0;
        }

        $rate = $this->invoice->conversion_rate ?? $this->invoice->currency->fromUsdRate();

        return round($this->amount * $rate, 2);
    }

    public function formattedSubTotal(): string
    {
        $currency = $this->invoice->currency ?? Currency::USD;

        return $currency->format($this->subtotalInClientCurrency());
    }

    public function formattedHourlyRate(): string
    {
        if ($this->hourly_rate === null) {
            return '';
        }
        $currency = $this->invoice->currency ?? Currency::USD;

        return $currency->format($this->hourlyRateInClientCurrency());
    }

    public static function formatHours(float $hours): string
    {
        if ($hours < 1) {
            $minutes = round($hours * 60);

            return "{$minutes} min";
        }

        if ($hours == 1) {
            return '1 hr';
        }

        $formatted = number_format($hours, fmod($hours, 1) ? 2 : 0);

        return "{$formatted} hrs";
    }

    public function formattedHours(): string
    {
        return self::formatHours((float) $this->hours);
    }

    public function scopeHourly($query)
    {
        return $query->where('type', InvoiceLineType::Hourly);
    }

    public function scopeFixed($query)
    {
        return $query->where('type', InvoiceLineType::Fixed);
    }
}
