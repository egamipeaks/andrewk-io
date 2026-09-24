<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'budget_hours',
        'is_active',
    ];

    protected $casts = [
        'budget_hours' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function manualInvoiceLines(): HasMany
    {
        return $this->invoiceLines()
            ->hourly()
            ->doesntHave('timeEntries');
    }

    public function scopeWithHours(Builder $query, string $alias = 'hours_used', ?string $from = null, ?string $until = null): Builder
    {
        return $query
            ->withSum([
                "timeEntries as {$alias}_from_entries" => fn (Builder $entries) => self::betweenDates($entries, $from, $until),
            ], 'hours')
            ->withSum([
                "invoiceLines as {$alias}_from_lines" => fn (Builder $lines) => self::betweenDates(
                    $lines->hourly()->doesntHave('timeEntries'),
                    $from,
                    $until,
                ),
            ], 'hours');
    }

    public function hasPreloadedHours(string $alias): bool
    {
        return array_key_exists("{$alias}_from_entries", $this->attributes);
    }

    public function preloadedHours(string $alias): float
    {
        return (float) ($this->attributes["{$alias}_from_entries"] ?? 0)
            + (float) ($this->attributes["{$alias}_from_lines"] ?? 0);
    }

    public function hoursUsed(): float
    {
        if ($this->hasPreloadedHours('hours_used')) {
            return $this->preloadedHours('hours_used');
        }

        return (float) $this->timeEntries()->sum('hours')
            + (float) $this->manualInvoiceLines()->sum('hours');
    }

    protected static function betweenDates(Builder $query, ?string $from, ?string $until): Builder
    {
        return $query
            ->when($from, fn (Builder $query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($until, fn (Builder $query, string $until) => $query->whereDate('date', '<=', $until));
    }

    public function hoursRemaining(): ?float
    {
        if ($this->budget_hours === null) {
            return null;
        }

        return (float) $this->budget_hours - $this->hoursUsed();
    }

    public static function belongsToClient(int $projectId, ?int $clientId): bool
    {
        return static::query()
            ->whereKey($projectId)
            ->where('client_id', $clientId)
            ->exists();
    }

    /** @return array<int, string> */
    public static function optionsForClient(?int $clientId, ?int $includeProjectId = null): array
    {
        if ($clientId === null) {
            return [];
        }

        return static::query()
            ->where('client_id', $clientId)
            ->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->when($includeProjectId, fn (Builder $query, int $id) => $query->orWhere('id', $id)))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
