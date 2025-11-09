# Multi-Client Invoice Creation from Time Tracking

## Status
**Planning** - Ready for implementation

## Overview
Add a "Create Invoices" button to the Time Tracking page header that opens a modal allowing users to select multiple clients and create invoices with their unbilled time entries for the current month.

## User Requirements
Based on conversation on 2025-11-08:

### User Preferences
1. **Invoice Line Descriptions**: One line per time entry (preserves original descriptions)
2. **Pre-selection**: Pre-select clients with unbilled time for current month
3. **After Creation**: Redirect to Invoice list page
4. **Hours Display**: Simple checkbox list with client names only (no hours/revenue details)

## Implementation Steps

### 1. Add Header Action to TimeTracking Page
**File**: `app/Filament/Pages/TimeTracking.php`

- Add `getHeaderActions()` method returning a "Create Invoices" action
- Action opens a modal with checkboxes for each client
- Pre-select clients that have unbilled time entries for the current month
- Display simple checkbox list with client names only

**Action Configuration:**
- Icon: `heroicon-o-document-plus`
- Label: "Create Invoices"
- Modal heading: "Create Invoices for {Month Year}"
- Modal description: "Select clients to create invoices for unbilled time entries"

### 2. Create Invoice Generation Logic
**In the action method:**

For each selected client:

1. **Create Invoice Record:**
   - `client_id`: selected client
   - `currency`: from client's currency
   - `due_date`: 15 days from end of current month (following existing pattern from InvoiceForm.php line 38)
   - `paid`: false
   - `note`: null

2. **Query Unbilled Time Entries:**
   ```php
   $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
   $endDate = $startDate->copy()->endOfMonth();

   $unbilledEntries = TimeEntry::query()
       ->where('client_id', $clientId)
       ->whereBetween('date', [$startDate, $endDate])
       ->whereNull('invoice_line_id')
       ->get();
   ```

3. **Create InvoiceLines - One per Time Entry:**
   ```php
   foreach ($unbilledEntries as $entry) {
       $invoiceLine = $invoice->invoiceLines()->create([
           'type' => InvoiceLineType::Hourly,
           'description' => $entry->description,
           'date' => $entry->date,
           'hourly_rate' => $entry->client->hourly_rate,
           'hours' => $entry->hours,
       ]);

       // Link time entry to invoice line
       $entry->update(['invoice_line_id' => $invoiceLine->id]);
   }
   ```

### 3. Handle Success & Redirect
- Count total invoices created
- Show success notification: "Created X invoice(s) successfully"
- Redirect to Invoice list page: `InvoiceResource::getUrl('index')`
- The Time Tracking page will show billed entries in green on next visit

### 4. Add Validation & Error Handling
- Ensure at least one client is selected (form validation required)
- Only show clients with `hourly_rate > 0` (matching existing TimeTracking pattern)
- Skip clients with no unbilled entries (show in notification)
- Example notification: "Created 3 invoices. Skipped 1 client with no unbilled time."

### 5. Form Schema for Modal
```php
Forms\Components\CheckboxList::make('client_ids')
    ->label('Clients')
    ->options(function () {
        // Get all clients with hourly rates
        return Client::query()
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->orderBy('name')
            ->pluck('name', 'id');
    })
    ->default(function () {
        // Pre-select clients with unbilled time for current month
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return TimeEntry::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('invoice_line_id')
            ->distinct()
            ->pluck('client_id')
            ->toArray();
    })
    ->required()
    ->minItems(1)
```

### 6. Test the Feature
**Create test**: `tests/Feature/TimeTrackingInvoiceCreationTest.php`

Test cases:
- Modal opens with correct clients
- Pre-selection of clients with unbilled time
- Invoice creation for single client
- Invoice creation for multiple clients
- Time entries are properly linked to invoice lines
- Redirect to invoice list page
- Validation when no clients selected
- Handling clients with no unbilled entries
- Invoice line creation preserves original time entry data

### 7. Code Formatting
- Run `vendor/bin/pint --dirty` to format the modified files

## Technical Details

### Query Patterns
**Unbilled time entries for month:**
```php
$startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
$endDate = $startDate->copy()->endOfMonth();

TimeEntry::query()
    ->where('client_id', $clientId)
    ->whereBetween('date', [$startDate, $endDate])
    ->whereNull('invoice_line_id')
    ->get();
```

**Clients with unbilled time:**
```php
TimeEntry::query()
    ->whereBetween('date', [$startDate, $endDate])
    ->whereNull('invoice_line_id')
    ->distinct()
    ->pluck('client_id');
```

### Invoice Defaults
- `due_date`: `now()->addDays(15)->addMonth()->startOfMonth()` (15 days from end of current month)
- `currency`: From client record (`$client->currency`)
- `paid`: `false`
- `note`: `null` (can be edited later in invoice edit page)

### InvoiceLine Creation
- **Type**: `InvoiceLineType::Hourly` (from enum)
- **One line per time entry** (preserves original descriptions)
- **Fields**:
  - `description`: Original time entry description
  - `date`: Original time entry date
  - `hours`: Original time entry hours
  - `hourly_rate`: Client's current hourly_rate at time of invoice creation
- **Linking**: Set `invoice_line_id` on TimeEntry to link back to InvoiceLine

### Existing Patterns to Follow
- Client filtering: Same as TimeTracking page (lines 66-70)
- Due date calculation: Same as InvoiceForm (line 38)
- Invoice line creation: Similar to InvoiceLinesRelationManager merge logic (lines 244-250)
- Time entry linking: Similar to delete action unlinking (line 172)

## Files to Modify
1. `app/Filament/Pages/TimeTracking.php` - Add header action and invoice creation logic

## Files to Create
1. `tests/Feature/TimeTrackingInvoiceCreationTest.php` - Comprehensive test coverage

## Dependencies
- Existing models: Client, Invoice, InvoiceLine, TimeEntry
- Existing enums: InvoiceLineType, Currency
- Existing resource: InvoiceResource
- Filament Actions API
- Carbon for date manipulation

## Future Enhancements (Not in Scope)
- Option to merge time entries into single line per client
- Preview of invoice before creation
- Custom due date override in modal
- Bulk edit invoice notes before creation
- Email invoices immediately after creation

## Notes
- This feature follows the existing pattern of the merge functionality in InvoiceLinesRelationManager
- Time entries remain linked to invoice lines (can be viewed via "Entries" action)
- Deleting an invoice line will unlink time entries (existing behavior)
- Green highlighting in Time Tracking will automatically update for billed entries
