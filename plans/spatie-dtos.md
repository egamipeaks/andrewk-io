# Spatie Data DTOs for TimeTracking & Services

## Summary
Introduce type-safe DTOs using Spatie Laravel Data v4.18 to replace untyped arrays in the TimeTracking pages and Services layer. Using camelCase property names (PHP convention).

## DTOs to Create

### 1. TimeEntryItemData
Individual time entry within a cell.
```php
namespace App\Data\TimeTracking;

class TimeEntryItemData extends Data
{
    public function __construct(
        public ?int $id,
        public string $description,
        public float $hours,
        public bool $isBilled = false,
        public ?int $invoiceLineId = null,
    ) {}
}
```

### 2. TimeEntryCellData
Aggregated cell data for TimeTrackingPage.
```php
class TimeEntryCellData extends Data
{
    public function __construct(
        public float $totalHours,
        public bool $isBilled,
        /** @var array<TimeEntryItemData> */
        public array $entries,
    ) {}

    public static function fromCollection(Collection $entries): self
    {
        return new self(
            totalHours: $entries->sum('hours'),
            isBilled: $entries->every(fn ($e) => $e->is_billed),
            entries: TimeEntryItemData::collect($entries->toArray()),
        );
    }
}
```

### 3. ProjectedEntryCellData
Aggregated cell data for IncomeProjectionPage (no `isBilled`).
```php
class ProjectedEntryCellData extends Data
{
    public function __construct(
        public float $totalHours,
        public array $entries,
    ) {}

    public static function fromCollection(Collection $entries): self
    {
        return new self(
            totalHours: $entries->sum('hours'),
            entries: $entries->toArray(),
        );
    }
}
```

### 4. TimeEntryFormData
Form input/output for editing time entries.
```php
class TimeEntryFormData extends Data
{
    public function __construct(
        public ?int $id,
        public string $description,
        public float $hours,
        public bool $isBilled = false,
        public ?int $invoiceLineId = null,
    ) {}

    public static function empty(): self
    {
        return new self(null, '', 1, false, null);
    }
}
```

## Files to Create
- `app/Data/TimeTracking/TimeEntryItemData.php`
- `app/Data/TimeTracking/TimeEntryCellData.php`
- `app/Data/TimeTracking/ProjectedEntryCellData.php`
- `app/Data/TimeTracking/TimeEntryFormData.php`
- `tests/Unit/Data/TimeTracking/TimeEntryCellDataTest.php`

## Files to Modify

### Phase 1: Create DTOs (no breaking changes)
1. Create `app/Data/TimeTracking/` folder and DTO classes

### Phase 2: Update TimeTrackingPage
**File**: `app/Filament/Pages/TimeTracking/TimeTrackingPage.php`

Changes:
- Line 32: `public array $timeEntriesData = [];` - add docblock `/** @var array<string, TimeEntryCellData> */`
- Line 71-75: Replace array mapping with `TimeEntryCellData::fromCollection()`
- Line 83: Change return type `?array` → `?TimeEntryCellData`
- Line 97: `$data['total_hours']` → `$data->totalHours`
- Line 130: `->sum('total_hours')` → iterate and sum `->totalHours`

### Phase 3: Update EditCellActionBuilder
**File**: `app/Filament/Pages/TimeTracking/Actions/EditCellActionBuilder.php`

Changes:
- Line 40: Access `$this->page->timeEntriesData[$key]->entries` instead of `['entries']`
- Line 42-48: Use `TimeEntryFormData::collect()` instead of manual mapping
- Line 51-57: Use `TimeEntryFormData::empty()`
- Line 79-81: Access `->entries` instead of `['entries']`

### Phase 4: Update IncomeProjectionPage
**File**: `app/Filament/Pages/TimeTracking/IncomeProjectionPage.php`

Changes:
- Line 35: Add docblock `/** @var array<string, ProjectedEntryCellData> */`
- Line 88-91: Use `ProjectedEntryCellData::fromCollection()`

### Phase 5: Update Blade Template
**File**: `resources/views/filament/pages/time-tracking/time-tracking-page.blade.php`

Changes:
- Line 87: `$cellData['is_billed']` → `$cellData->isBilled`
- Line 89: `$cellData['total_hours']` → `$cellData->totalHours`
- Line 91: `$cellData['total_hours']` → `$cellData->totalHours`

### Phase 6: Update Tests
**File**: `tests/Feature/Filament/Pages/TimeTrackingTest.php`

Changes:
- Update assertions that access `$component->timeEntriesData[$key]['total_hours']` to use `->totalHours`
- Update any assertions checking array structure

**File**: `tests/Feature/IncomeProjectionTest.php`

Changes:
- Update assertions accessing `$component->projectedEntriesData` array keys to use DTO properties

## Implementation Order

1. **Create DTOs** - No risk, additive only
2. **Add unit tests for DTOs** - Validate DTO behavior
3. **Update TimeTrackingPage.php** - Core page changes
4. **Update EditCellActionBuilder.php** - Form handling
5. **Update time-tracking-page.blade.php** - Template property access
6. **Update IncomeProjectionPage.php** - Secondary page
7. **Update TimeTrackingTest.php** - Fix test assertions
8. **Update IncomeProjectionTest.php** - Fix test assertions
9. **Run full test suite** - Verify no regressions
10. **Run Pint** - Code formatting

## Key Decisions
- **camelCase** properties (user confirmed)
- **No MapName attributes** - cleaner code, templates will use new syntax
- **Simple arrays** for `entries` property (not DataCollection) - keeps Livewire serialization simple
- **Static factory methods** (`fromCollection`) for cleaner instantiation
