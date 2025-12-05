<?php

namespace App\Services;

use App\Models\ProjectedEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectedEntryService
{
    public function getProjectedEntries(
        Collection $clientIds,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return ProjectedEntry::query()
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('client')
            ->get()
            ->groupBy(fn (ProjectedEntry $entry) => "{$entry->client_id}_{$entry->date->format('Y-m-d')}");
    }
}
