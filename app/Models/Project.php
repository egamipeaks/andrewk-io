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

    public function hoursUsed(): float
    {
        if (array_key_exists('hours_used', $this->attributes)) {
            return (float) $this->attributes['hours_used'];
        }

        return (float) $this->timeEntries()->sum('hours');
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
