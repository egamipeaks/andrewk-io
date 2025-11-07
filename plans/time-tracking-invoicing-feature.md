# Time Tracking & Invoicing Feature Plan

**Status:** Planning
**Created:** 2025-01-07
**Last Updated:** 2025-01-07

## Overview
Build a spreadsheet-like time entry system with monthly views and seamless invoice integration, add date tracking to invoice lines, and formalize invoice line types (Fixed Amount vs. Hourly).

---

## 1. Database Schema Changes

### Migration 1: Add `date` and `type` to `invoice_lines`
- Add `date` column (date, nullable) to `invoice_lines` table
  - Default to current date for new entries
  - No backfill needed for existing records
- Add `type` column (string, nullable) to `invoice_lines` table
  - Will be cast to `InvoiceLineType` enum
  - Values: 'fixed' or 'hourly'
  - No backfill needed (can be inferred from existing data)

### Migration 2: Create `time_entries` table
- `id` (primary key)
- `client_id` (foreign key to clients, cascade delete)
- `invoice_line_id` (nullable foreign key to invoice_lines, set null on delete)
- `date` (date) - The day this work was performed
- `hours` (decimal 8,2) - Hours worked
- `description` (text) - Task/work description
- `timestamps`

**Indexes on `time_entries`:**
- `client_id` + `date` (for quick monthly queries)
- `invoice_line_id` (to filter billed vs unbilled)
- `client_id` + `invoice_line_id` (for unbilled queries)

---

## 2. New Enum: `InvoiceLineType`

**Location:** `app/Enums/InvoiceLineType.php`

**Cases:**
- `Fixed` - Fixed amount billing (uses `amount` field)
- `Hourly` - Hourly billing (uses `hourly_rate` + `hours` fields)

**Methods:**
- `label()` - Returns human-readable label ("Fixed Amount", "Hourly")
- Optional: icon/color methods for UI

---

## 3. Update `InvoiceLine` Model

**Add:**
- `date` attribute (cast to date)
- `type` attribute (cast to `InvoiceLineType` enum)
- Default values in factory: `date` = now(), `type` = Hourly

**Update `subtotal` Accessor:**
- Make it aware of the type (though current logic already handles this correctly)
- Ensure it returns correct value based on type

**Add Validation/Logic:**
- When type is `Fixed`: require `amount`, ignore `hourly_rate` and `hours`
- When type is `Hourly`: require `hourly_rate` and `hours`, ignore `amount`

---

## 4. New Model: `TimeEntry`

**Relationships:**
- `belongsTo(Client::class)`
- `belongsTo(InvoiceLine::class)` - nullable
- Add to `Client` model: `hasMany(TimeEntry::class)`
- Add to `InvoiceLine` model: `hasMany(TimeEntry::class)`

**Scopes:**
- `unbilled()` - where invoice_line_id is null
- `billed()` - where invoice_line_id is not null
- `forMonth($year, $month)` - filter by specific month
- `forDateRange($start, $end)` - filter by date range

**Computed Attributes:**
- `isBilled` - returns `!is_null($this->invoice_line_id)`
- `value` - returns `$this->hours * $this->client->hourly_rate`

**Constraint:**
- Time entries can ONLY be linked to invoice lines with type = `Hourly`

---

## 5. Update `InvoiceLinesRelationManager`

### Add Type Selection to Form:
- Add `type` field (select/radio) at the top of the form
  - Options: Fixed Amount, Hourly
  - Default: Hourly
- Use reactive form fields to show/hide relevant inputs based on type

### Form Fields (Conditional based on type):

**When Type = Fixed:**
- Description (required, textarea, full width)
- Date (date picker, required, default today)
- Amount (required, numeric, prefix $, step 0.01)

**When Type = Hourly:**
- Description (required, textarea, full width)
- Date (date picker, required, default today)
- Hourly Rate (required, numeric, prefix $, step 0.01, defaults to client rate)
- Hours (required, numeric, step 0.25, placeholder 8.0)

### Add Type Column to Table:
- Show badge with "Fixed" or "Hourly" label
- Color-coded for quick visual distinction
- Filterable

### Update Other Columns:
- Show date in readable format (sortable)
- Hours column: only show for Hourly type (or show "--" for Fixed)
- Hourly Rate column: only show for Hourly type
- Subtotal: show for both types

### Table Ordering:
- Default order by `date` descending (most recent first)

---

## 6. Filament Custom Page: `TimeTracking`

**Location:** `app/Filament/Pages/TimeTracking.php`

### Features:
- Dedicated page accessible from Filament navigation ("Time Tracking")
- Month selector (dropdown or prev/next buttons)
- Spreadsheet-like table using Livewire
- Manual "Save All Changes" button
- Summary statistics at top/bottom

### Table Structure:
- **Columns:** One per active client (clients with hourly_rate set)
- **Rows:** One per day of selected month (1-31)
- **Cells:** Show total hours for that client/day
  - Empty state: "--" or "0"
  - Filled state: "8.5" (clickable to open detail modal)
  - Different styling for billed vs unbilled hours

### Detail Modal (when clicking a cell):
- List of existing time entries for that client/day
- Ability to add new entries with description + hours
- Ability to edit/delete existing unbilled entries
- Billed entries shown as read-only with invoice link
- "Close" button (saves on page-level save)

### Summary Display:
- Per-client column totals: Hours + Revenue (hours × client hourly_rate)
- Grand totals row: Total hours + Total revenue for entire month
- Separate visual indicators for billed vs unbilled

### Technical Implementation:
- Store changes in Livewire component state (not DB)
- On "Save All Changes": batch create/update/delete time entries
- Use wire:loading states for save operation
- Toast notifications on successful save

**Note:** Time tracking only creates Hourly-type entries (Fixed amounts are manual)

---

## 7. Modify `InvoiceResource` Edit Page

### Add "Import Time Entries" Action:

**Location:** In the invoice edit page header actions (next to Send Email)

**Behavior:**
1. Query all unbilled time entries for the invoice's client
2. Show modal with:
   - Table of unbilled entries (date, description, hours, value)
   - Checkboxes to select which entries to import (default: all selected)
   - Date range filter (optional)
   - Summary: Total hours + Total value of selected entries
3. On confirm:
   - **For each selected time entry:**
     - Create one invoice line with:
       - `type` = InvoiceLineType::Hourly
       - `description` from time entry
       - `hourly_rate` from client's current rate
       - `hours` from time entry
       - `date` from time entry
     - Update time_entry: set `invoice_line_id` to link them
   - Refresh invoice lines relation manager
   - Show success notification with count

**Edge Cases:**
- Disable action if client has no unbilled time entries
- Prevent importing if invoice is already paid
- Show warning if client has no hourly_rate set

---

## 8. Update `InvoiceLinesRelationManager` (Additional Updates)

### Add Visual Indicator:
- Show badge/icon if invoice line was created from time entries
- Add "View Source Time Entry" action to see the source entry (only for Hourly type)
- Display count of linked time entries
- Prevent deletion of invoice lines with linked time entries (or warn & unlink)
- Fixed-type invoice lines cannot have time entries linked

### Table Filtering:
- Add filter for invoice line type (Fixed vs. Hourly)
- Add filter for "Has Time Entries" (Yes/No)

---

## 9. Update `ClientResource`

### Add to Table:
- Column showing "Unbilled Hours" count
- Column showing "Unbilled Revenue" value (calculated from time entries)

### Optional Relation Manager:
- `TimeEntriesRelationManager` to view/manage time entries directly on client detail page

---

## 10. Update Invoice Display Logic

### Email Templates & Views:
- Update invoice email templates to handle both types:
  - Show hourly rate and hours for Hourly type
  - Show just the amount for Fixed type
- Ensure subtotals calculate correctly for both

---

## 11. Testing Strategy

### Feature Tests:
- Create invoice lines with Fixed type (amount only)
- Create invoice lines with Hourly type (rate + hours)
- Verify type-based validation (can't have amount + hours on same line)
- Create/update/delete time entries via Livewire component
- Month navigation and filtering
- Import unbilled entries to invoice (verify type = Hourly, date copied)
- Prevent linking time entries to Fixed-type invoice lines
- Calculate totals correctly for both types
- Invoice line date defaults to today

### Unit Tests:
- InvoiceLineType enum methods
- TimeEntry model scopes (unbilled, forMonth, etc.)
- Value calculations with client hourly rates
- Relationship integrity
- Invoice line subtotal calculation for both types
- Invoice line date handling

---

## 12. Implementation Order

1. **Create `InvoiceLineType` enum** (Fixed, Hourly cases with labels)
2. **Migration: Add `date` and `type` to `invoice_lines`** (both nullable, no backfill)
3. **Update `InvoiceLine` model** (add date & type casts, update factory, update subtotal logic)
4. **Update `InvoiceLinesRelationManager`** (add type selection, conditional fields, update table)
5. **Migration: Create `time_entries` table**
6. **Create `TimeEntry` model** (with relationships, scopes, computed attributes)
7. **Update `Client` and `InvoiceLine` models** (add time entries relationships)
8. **Create `TimeTracking` Filament page** (basic structure + navigation)
9. **Build spreadsheet table UI** (Livewire component with grid)
10. **Implement detail modal** (for multiple task entries per day)
11. **Add save functionality** (batch operations with manual save button)
12. **Add summary calculations** (per-client and grand totals)
13. **Create "Import Time Entries" action** (on invoice edit page, sets type = Hourly)
14. **Add visual indicators** (invoice lines linked to time entries, type badges)
15. **Update invoice display/email templates** (handle both types properly)
16. **Update `ClientResource`** (unbilled hours/revenue columns)
17. **Write comprehensive tests** (feature + unit tests for both types)
18. **Run Pint** for code formatting

---

## Key Benefits

✅ **Flexible billing**: Support both fixed-price and hourly billing on same invoice
✅ **Clear distinction**: Type field makes intent explicit and enforces correct fields
✅ **Date tracking**: Every invoice line now has a date for better context
✅ **Quick entry**: Spreadsheet view for fast data entry across clients
✅ **Detailed tracking**: Modal for granular task-level entries
✅ **No double-billing**: Time entries linked to invoice lines prevent re-use
✅ **Financial visibility**: See unbilled revenue at a glance
✅ **Seamless workflow**: One click to add all unbilled time to invoice
✅ **Audit trail**: Always know which invoice billed which time entries
✅ **Date stamping**: Time entry dates automatically transfer to invoice lines
✅ **Type safety**: Can't accidentally mix fixed amounts with hourly billing

---

## Design Decisions

### User Preferences (from planning session):
1. **Table Location**: Dedicated page in navigation
2. **Save Behavior**: Manual save button (batch save all changes)
3. **Rate Handling**: Use current client rate (not snapshotted)
4. **Invoice Line Creation**: One line per task entry (detailed breakdown)

### Additional Features:
- Invoice lines now support two distinct types (Fixed vs. Hourly)
- Date field added to invoice lines for better context
- Time entries can only link to Hourly-type invoice lines
