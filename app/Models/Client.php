<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\HoursUpdateFrequency;
use App\Mail\HoursUpdateEmail;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

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
        'hours_update_frequency',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'is_active' => 'boolean',
        'hours_update_frequency' => HoursUpdateFrequency::class,
    ];

    protected $attributes = [
        'currency' => 'USD',
        'hours_update_frequency' => 'off',
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

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function projectedEntries(): HasMany
    {
        return $this->hasMany(ProjectedEntry::class);
    }

    public function hoursUpdateSends(): HasMany
    {
        return $this->hasMany(HoursUpdateSend::class);
    }

    /** @return Collection<int, array{name: string, hours: float, amount: float}> */
    public function unbilledHoursByProject(CarbonInterface $asOf): Collection
    {
        $groups = $this->timeEntries()
            ->unbilled()
            ->forMonth($asOf->year, $asOf->month)
            ->whereDate('date', '<=', $asOf)
            ->with('project')
            ->get()
            ->groupBy(fn (TimeEntry $entry): int => $entry->project_id ?? 0)
            ->map(function (Collection $entries): array {
                $hours = (float) $entries->sum('hours');

                return [
                    'name' => $entries->first()->project?->name ?? 'General',
                    'hours' => $hours,
                    'amount' => $this->currency->fromUsd($hours * ($this->hourly_rate ?? 0)),
                ];
            });

        return $groups->except(0)
            ->sortBy('name')
            ->concat($groups->only(0))
            ->values();
    }

    public function hoursUpdateEmail(CarbonInterface $asOf): HoursUpdateEmail
    {
        return new HoursUpdateEmail($this, $this->unbilledHoursByProject($asOf), $asOf);
    }

    public function sendHoursUpdate(CarbonInterface $asOf): bool
    {
        $mail = $this->hoursUpdateEmail($asOf);
        $groups = $mail->groups;

        if ($groups->isEmpty()) {
            return false;
        }

        Mail::to($this->email)->send($mail);

        $this->hoursUpdateSends()->create([
            'email' => $this->email,
            'hours' => $groups->sum('hours'),
            'sent_at' => now(),
        ]);

        return true;
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
