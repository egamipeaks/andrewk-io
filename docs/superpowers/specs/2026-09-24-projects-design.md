# Projects for Time Entries

## Goal

Let a client have projects, optionally tag logged hours with one of them, and track hours used against an optional budget. Invoice emails group lines by project when projects are present.

Primary use: tracking hours against an estimate or budget. Secondary use: manually reporting project hours to a client when they ask.

## Decisions

- Hours never require a project.
- A project belongs to exactly one client.
- Budgets are stored in hours and are optional.
- Budget figures are internal and never appear in client emails.
- Totals are viewed on a top level Projects page with a date range filter.
- Emails group invoice lines under project headings with subtotals.

## Data Model

### `projects` table (new)

| Column | Type | Notes |
|---|---|---|
| `id` | id | |
| `client_id` | foreignId | constrained, cascade on delete |
| `name` | string | |
| `budget_hours` | decimal(8, 2) | nullable |
| `is_active` | boolean | default true |
| timestamps | | |

Index on `client_id`.

### `time_entries.project_id` (new column)

Nullable foreignId, constrained, null on delete. Indexed.

### `invoice_lines.project_id` (new column)

Nullable foreignId, constrained, null on delete. Invoice lines store their own project because the email renders from lines, and lines can be edited or created manually (including fixed price lines).

### Models

- `Project`: `belongsTo(Client)`, `hasMany(TimeEntry)`, `hasMany(InvoiceLine)`, `scopeActive`. Casts `budget_hours` as `decimal:2` and `is_active` as boolean. Factory included.
- `Client`: `hasMany(Project)`.
- `TimeEntry`: `belongsTo(Project)`, `project_id` fillable.
- `InvoiceLine`: `belongsTo(Project)`, `project_id` fillable.

### Integrity

A time entry's project must belong to the entry's client. The same applies to an invoice line's project and its invoice's client. The UI only offers matching projects. A `saving` model hook on `TimeEntry` and `InvoiceLine` throws if a mismatched project is set, so bad data cannot be written from anywhere else either.

### Hours Calculations

- Hours used: sum of `time_entries.hours` for the project, plus `hours` on the project's hourly invoice lines that have no time entries behind them (lines typed directly onto an invoice). Lines created from time entries are skipped so their hours are not counted twice. All time.
- Hours left: `budget_hours` minus hours used. Null when there is no budget. Can be negative.
- Hours in range: the same two sums, limited to entries and lines whose `date` is within the selected range. Equals hours used when no range is set.

Computed in the table query with `withSum` (the `Project::withHours` scope) so there is no N+1.

Changing an invoice line's project also moves the time entries behind that line to the new project, so the email grouping and the project totals agree.

## Admin UI

### Projects Resource (new)

Follows the existing Clients resource layout (`Pages`, `Schemas`, `Tables` folders).

Form fields: client (select, active clients plus the current value), name, budget hours (numeric, optional, suffix "hrs"), active toggle.

Table columns: client, project, budget, used, left (danger colour when negative), in range. Sortable by client and name.

Filters:

- Client (select)
- Active (ternary, defaults to active only)
- Date range (from and to date pickers). Affects only the "in range" column, not which rows are listed.

Edit page includes a time entries relation manager (read only): date, hours, description, billed status. Sorted newest first.

### Time Entry Form

`app/Filament/Pages/TimeTracking/Schema/TimeEntryForm.php` gains a Project select column in the repeater:

- Options: active projects for the cell's client, plus the entry's current project if it is inactive.
- Optional.
- Disabled when the entry is billed, like the other fields.

The save logic in `TimeEntryService` persists `project_id`. The grid cell display is unchanged.

## Invoices

### Importing Time Entries

`EditInvoice::importTimeEntries` copies `project_id` from each time entry onto the invoice line it creates.

### Invoice Lines Relation Manager

- Form: Project select, limited to the invoice client's projects, optional.
- Table: Project column (toggleable).

### Merge Hourly Lines

The `mergeHourlyLines` bulk action only merges lines with the same `project_id` (null counts as its own group). If the selection spans more than one project, it does nothing and shows a warning notification. The merged line keeps the shared project.

## Invoice Email

`resources/views/emails/invoice.blade.php` and the preview route:

- No line has a project: renders exactly as today.
- At least one line has a project: lines are grouped under a heading per project, ordered by project name. Each group ends with a subtotal row showing total hours (when the group has hourly lines) and the amount in the client's currency. Lines without a project appear last under "Other". Grand totals are unchanged.

Grouping logic lives on the `Invoice` model (a method returning lines grouped by project) so the view stays simple and the logic is testable. Budget information is never rendered.

## Testing

Pest feature tests:

- Project model: relationships, hours used, hours left (including null budget and negative values), hours in range.
- Integrity hook rejects a project from another client for both time entries and invoice lines.
- Projects table: lists projects, client filter, active filter, date range changes the in range total.
- Time entry form saves and clears `project_id`, and only offers the client's active projects.
- Import copies `project_id` to invoice lines.
- Merge action merges same project lines and refuses mixed projects.
- Email renders flat when no projects are set, grouped with subtotals when they are, and never shows budget.

## Out of Scope

- Client facing project reports or exports.
- Budget alerts or notifications.
- Budgets in money rather than hours.
- Project level hourly rates.
