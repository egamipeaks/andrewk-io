<?php

namespace App\Services;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimeEntryService
{
    public function getTimeEntries(
        Collection $clientIds,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return TimeEntry::query()
            ->whereIn('client_id', $clientIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('client')
            ->get()
            ->groupBy(fn (TimeEntry $entry) => "{$entry->client_id}_{$entry->date->format('Y-m-d')}");
    }

    public function syncEntriesForCell(
        int $clientId,
        string $date,
        array $entriesData,
        array $existingEntryIds
    ): void {
        DB::transaction(function () use ($clientId, $date, $entriesData, $existingEntryIds) {
            $processedIds = [];
            $defaultDescription = Carbon::parse($date)->format('M j').' hours';

            foreach ($entriesData as $entryData) {
                $description = filled($entryData['description'] ?? null)
                    ? $entryData['description']
                    : $defaultDescription;

                $entryId = $entryData['id'] ?? null;
                $hours = $entryData['hours'] ?? 0;
                $projectId = filled($entryData['project_id'] ?? null) ? (int) $entryData['project_id'] : null;

                if ($entryId !== null) {
                    $processedIds[] = $this->updateEntry($entryId, $description, $hours, $projectId);
                } else {
                    $processedIds[] = $this->createEntry($clientId, $date, $description, $hours, $projectId);
                }
            }

            $this->deleteRemovedEntries($existingEntryIds, $processedIds);
        });
    }

    protected function updateEntry(int $id, string $description, float $hours, ?int $projectId): int
    {
        $entry = TimeEntry::find($id);

        if ($entry && ! $entry->is_billed) {
            $entry->update([
                'description' => $description,
                'hours' => $hours,
                'project_id' => $projectId,
            ]);
        }

        return $id;
    }

    protected function createEntry(int $clientId, string $date, string $description, float $hours, ?int $projectId): int
    {
        return TimeEntry::create([
            'client_id' => $clientId,
            'project_id' => $projectId,
            'date' => $date,
            'description' => $description,
            'hours' => $hours,
        ])->id;
    }

    protected function deleteRemovedEntries(array $existingIds, array $processedIds): void
    {
        $idsToDelete = array_diff($existingIds, $processedIds);

        TimeEntry::whereIn('id', $idsToDelete)
            ->unbilled()
            ->delete();
    }
}
