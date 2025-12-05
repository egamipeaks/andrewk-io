<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'email_from',
        'currency',
        'hourly_rate',
        'is_active',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'currency' => 'USD',
    ];

    public function scopeCanTrackTime($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function projectedEntries(): HasMany
    {
        return $this->hasMany(ProjectedEntry::class);
    }

    public function formattedHourlyRate(): string
    {
        return Currency::USD->format($this->hourly_rate);
    }

    public function shortName(): string
    {
        $words = preg_split('/\s+/', trim($this->name));

        if (count($words) > 1) {
            // Multiple words: create acronym from first letter of each word
            $acronym = '';
            foreach ($words as $word) {
                if (! empty($word)) {
                    $acronym .= mb_substr($word, 0, 1);
                }
            }

            return mb_strtoupper($acronym);
        }

        // Single word: take first three characters
        return mb_strtoupper(mb_substr($this->name, 0, 3));
    }
}
