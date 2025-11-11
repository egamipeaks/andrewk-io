# Currency Architecture Redesign - Store USD, Display Client Currency

## Status
**Planning** - Ready for implementation

## Overview
Redesign the currency system to store all hourly rates in USD as the source of truth, but display/invoice clients in their preferred currency using a conversion rate that's locked in when the invoice is created.

## User Requirements
Based on conversation on 2025-11-10:

### Core Principle
- **All hourly rates stored in USD** (single source of truth)
- **Client currency is display preference only** (for invoices/emails)
- **Conversion rates locked at invoice creation** (preserves historical accuracy)
- **Invoice lines store rates in USD** (calculate display amounts on the fly)

### Use Case Example
- Client has `currency=CAD`, `hourly_rate=$100 USD`
- Current CAD rate: `1 USD = 1.408 CAD` (inverse of 0.71)
- When creating invoice:
  - Invoice: `currency=CAD`, `conversion_rate=1.408`
  - InvoiceLine: `hourly_rate=$100 USD`, `hours=5`
  - Client sees: `C$140.80/hr × 5 hours = C$704.00`
  - Database stores: `$100 USD rate`, `5 hours`, uses conversion_rate for display

### User Notes
- "I'll need to run a conversion script that adjusts old invoices to be correct"
- "The .71 number may not be right for historical invoices"
- "My rate with a client is 100 USD but I bill them at the current CAD conversion rate"
- "In previous invoices that was 140 CAD" (meaning 1 USD = 1.40 CAD at that time)

## Current System vs New System

### Current Architecture
```
Client:
  currency: CAD
  hourly_rate: 140 CAD (stored in client currency)

Invoice:
  currency: CAD

InvoiceLine:
  hourly_rate: 140 CAD (copied from client)
  hours: 5
  subtotal: 700 CAD

Display: C$700
```

### New Architecture
```
Client:
  currency: CAD (display preference)
  hourly_rate: 100 USD (always USD!)

Invoice:
  currency: CAD (display preference)
  conversion_rate: 1.408 (locked at creation)

InvoiceLine:
  hourly_rate: 100 USD (always USD!)
  hours: 5
  subtotal: 500 USD (calculated)

Display: C$704 (500 USD × 1.408 conversion_rate)
```

## Implementation Steps

### 1. Database Migration - Add conversion_rate to Invoices
**File**: `database/migrations/YYYY_MM_DD_add_conversion_rate_to_invoices_table.php`

```php
Schema::table('invoices', function (Blueprint $table) {
    $table->decimal('conversion_rate', 10, 6)->nullable()->after('currency');
    $table->index('conversion_rate');
});
```

**Field Details:**
- `decimal(10, 6)` allows rates like `1.408451`
- Nullable for existing invoices (will need data migration)
- Indexed for query performance

### 2. Enhance Currency Enum
**File**: `app/Enums/Currency.php`

Add two new methods for USD → Client Currency conversion:

```php
public function fromUsdRate(): float
{
    return match ($this) {
        self::USD => 1.0,
        self::CAD => 1.0 / 0.71, // ~1.408 (inverse of toUsdRate)
    };
}

public function fromUsd(float $amountInUsd): float
{
    return round($amountInUsd * $this->fromUsdRate(), 2);
}
```

**Explanation:**
- `fromUsdRate()`: Returns multiplier to convert USD → Client Currency
- `fromUsd($amount)`: Converts a USD amount to client currency
- Rounds to 2 decimal places for display

### 3. Update Invoice Model
**File**: `app/Models/Invoice.php`

**Add to $fillable:**
```php
'conversion_rate',
```

**Add cast:**
```php
'conversion_rate' => 'float',
```

**Update/Add Methods:**
```php
// Keep existing total (sums USD subtotals)
public function getTotalAttribute()
{
    return $this->invoiceLines->sum('subtotal');
}

// NEW: Get total in client currency
public function totalInClientCurrency(): float
{
    $rate = $this->conversion_rate ?? $this->currency->fromUsdRate();
    return round($this->total * $rate, 2);
}

// UPDATE: Format total in client currency (for invoices/emails)
public function formattedTotal(): string
{
    $currency = $this->currency ?? Currency::USD;
    return $currency->format($this->totalInClientCurrency());
}

// NEW: For admin display - formatted in client currency
public function formattedTotalInClientCurrency(): string
{
    $currency = $this->currency ?? Currency::USD;
    return $currency->format($this->totalInClientCurrency());
}

// Keep existing USD methods for admin analytics
public function totalUsd(): float { ... }
public function formattedTotalUsd(): string { ... }
```

### 4. Update InvoiceLine Model
**File**: `app/Models/InvoiceLine.php`

**Add Methods:**
```php
// Get subtotal in client currency
public function subtotalInClientCurrency(): float
{
    $rate = $this->invoice->conversion_rate ?? $this->invoice->currency->fromUsdRate();
    return round($this->subtotal * $rate, 2);
}

// Get hourly rate in client currency
public function hourlyRateInClientCurrency(): float
{
    if (!$this->hourly_rate) {
        return 0;
    }

    $rate = $this->invoice->conversion_rate ?? $this->invoice->currency->fromUsdRate();
    return round($this->hourly_rate * $rate, 2);
}
```

**Update Existing Methods:**
```php
// UPDATE: Format in client currency
public function formattedSubTotal(): string
{
    $currency = $this->invoice->currency ?? Currency::USD;
    return $currency->format($this->subtotalInClientCurrency());
}

// UPDATE: Format in client currency
public function formattedHourlyRate(): string
{
    $currency = $this->invoice->currency ?? Currency::USD;
    return $currency->format($this->hourlyRateInClientCurrency());
}
```

### 5. Update Invoice Creation - Auto-Set Conversion Rate
**File**: `app/Filament/Resources/Invoices/Schemas/InvoiceForm.php`

**Update client selection callback (lines 25-32):**
```php
->afterStateUpdated(function ($set, $state) {
    if ($state) {
        $client = Client::find($state);
        if ($client) {
            $currency = $client->currency;
            $set('currency', $currency->value);
            $set('conversion_rate', $currency->fromUsdRate()); // NEW!
        }
    }
})
```

**Add hidden field to form:**
```php
Forms\Components\Hidden::make('conversion_rate'),
```

**Or make it visible/editable (optional):**
```php
Forms\Components\TextInput::make('conversion_rate')
    ->label('Conversion Rate (USD to Client Currency)')
    ->numeric()
    ->step(0.000001)
    ->helperText('Locked at invoice creation. Override if needed for historical accuracy.')
    ->required(),
```

### 6. Update Client Form - Clarify USD
**File**: `app/Filament/Resources/Clients/Schemas/ClientForm.php`

**Update hourly_rate field (lines 33-50):**
```php
Forms\Components\TextInput::make('hourly_rate')
    ->label('Hourly Rate (USD)')
    ->numeric()
    ->prefix('$')  // Always USD symbol, not dynamic
    ->step(0.01)
    ->placeholder('100.00')
    ->helperText('All hourly rates are stored in USD. Invoices will be sent in the client\'s preferred currency using the current exchange rate.'),
```

**Remove the dynamic prefix function** since all rates are USD now.

### 7. Update Invoice Lines Relation Manager
**File**: `app/Filament/Resources/Invoices/RelationManagers/InvoiceLinesRelationManager.php`

**Update hourly_rate prefix (around line 70):**
```php
Forms\Components\TextInput::make('hourly_rate')
    ->label('Hourly Rate (USD)')
    ->numeric()
    ->prefix('$')  // Always USD, not dynamic
    ->step(0.01)
    ->default(function ($livewire) {
        return $livewire->getOwnerRecord()->client->hourly_rate ?? null;
    })
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === InvoiceLineType::Hourly->value),
```

### 8. Revert Invoice Table to Client Currency
**File**: `app/Filament/Resources/Invoices/Tables/InvoicesTable.php`

**Change from USD back to client currency:**
```php
TextColumn::make('total')
    ->formatStateUsing(fn ($record): string => $record->formattedTotalInClientCurrency()),
```

### 9. Update Email Template (Already Correct!)
**File**: `resources/views/emails/invoice.blade.php`

**No changes needed!** Already uses:
- `{{ $invoice->formattedTotal() }}` - Will now show client currency
- `{{ $line->formattedSubTotal() }}` - Will now show client currency
- `{{ $line->formattedHourlyRate() }}` - Will now show client currency

### 10. TimeTracking Page (No Changes Needed!)
**File**: `app/Filament/Pages/TimeTracking.php`

Since `client.hourly_rate` will now be in USD:
- `getTotalRevenueForClient()` calculates: `hours × client.hourly_rate` → USD
- `getGrandTotalRevenue()` already converts to USD (no-op for USD values)
- Everything continues to work correctly!

### 11. Create Comprehensive Tests
**File**: `tests/Feature/InvoiceConversionRateTest.php`

Test cases:
- Invoice creation from USD client sets conversion_rate to 1.0
- Invoice creation from CAD client sets conversion_rate to ~1.408
- Invoice total displays in client currency (USD client)
- Invoice total displays in client currency (CAD client)
- Invoice lines display hourly rate in client currency
- Invoice lines display subtotal in client currency
- Changing conversion_rate updates all display amounts
- Email template shows amounts in client currency
- Historical invoice with locked conversion_rate displays correctly
- Two CAD invoices with different conversion_rates display differently

### 12. Update Existing Tests
**Files to update:**
- `tests/Feature/InvoiceCurrencyTest.php` - Add conversion_rate to test setup
- `tests/Feature/InvoiceEmailTest.php` - Verify client currency display
- `tests/Feature/Filament/InvoiceLinesRelationManagerTest.php` - Update for USD rates

### 13. Code Formatting
Run `vendor/bin/pint --dirty`

## Data Migration Strategy

### For New Invoices
Automatic via invoice creation form - conversion_rate populated from `currency->fromUsdRate()`

### For Existing Invoices
**Separate Artisan Command** (user will customize):

```php
php artisan invoices:migrate-conversion-rates
```

The command should:
1. Find all invoices without conversion_rate
2. For each invoice, calculate rate from existing data or prompt user
3. Options:
   - Use current rate: `currency->fromUsdRate()`
   - Calculate from existing invoice_line.hourly_rate (if client rate is known)
   - Prompt user for custom rate per invoice
4. Update invoice.conversion_rate
5. User mentioned historical rates may differ (e.g., old invoices at 1.40 CAD per USD)

**User will handle client.hourly_rate conversion separately** (convert CAD values to USD externally)

## Technical Details

### Conversion Rate Math
```
USD to CAD:
  toUsdRate = 0.71 (to convert CAD → USD)
  fromUsdRate = 1 / 0.71 = 1.408 (to convert USD → CAD)

Example:
  Client hourly_rate: $100 USD
  CAD conversion_rate: 1.408
  Display rate: $100 × 1.408 = C$140.80
```

### Display Logic Flow
```
1. InvoiceLine stores: hourly_rate=$100, hours=5
2. Subtotal calculated: $100 × 5 = $500 (in USD)
3. Invoice has: conversion_rate=1.408, currency=CAD
4. Display converts: $500 × 1.408 = C$704.00
5. Format with symbol: "C$704"
```

### Why Lock Conversion Rates?
- Exchange rates fluctuate daily
- Historical invoices must maintain original amounts
- Client was billed C$704, not recalculated amount
- Preserves financial accuracy and audit trail

## Files to Create
1. `database/migrations/YYYY_MM_DD_add_conversion_rate_to_invoices_table.php`
2. `tests/Feature/InvoiceConversionRateTest.php`
3. `app/Console/Commands/MigrateInvoiceConversionRates.php` (optional, for data migration)

## Files to Modify
1. `app/Enums/Currency.php` - Add fromUsdRate(), fromUsd()
2. `app/Models/Invoice.php` - Add conversion_rate field and methods
3. `app/Models/InvoiceLine.php` - Update formatting methods
4. `app/Filament/Resources/Invoices/Schemas/InvoiceForm.php` - Auto-set conversion_rate
5. `app/Filament/Resources/Clients/Schemas/ClientForm.php` - Clarify USD, remove dynamic prefix
6. `app/Filament/Resources/Invoices/Tables/InvoicesTable.php` - Show client currency
7. `app/Filament/Resources/Invoices/RelationManagers/InvoiceLinesRelationManager.php` - Fix USD prefix
8. `tests/Feature/InvoiceCurrencyTest.php` - Update for conversion_rate
9. `tests/Feature/InvoiceEmailTest.php` - Verify client currency
10. `tests/Feature/Filament/InvoiceLinesRelationManagerTest.php` - Update for USD

## Breaking Changes & Migration Tasks

### Before Implementation
1. **Backup database** - This is a significant schema change
2. **Export client hourly rates** - Will need manual conversion to USD
3. **Document current conversion rates** - For historical invoice accuracy

### After Implementation
1. **Convert client hourly_rates to USD** - Manual SQL or script
2. **Set conversion_rates on existing invoices** - Run migration command
3. **Verify historical invoice totals** - Ensure they match original amounts
4. **Update any custom reports/queries** - That reference currency fields

### Example Client Rate Conversion
```sql
-- If client had CAD rate of $140, convert to USD:
UPDATE clients
SET hourly_rate = 140 / 1.40  -- Assuming historical rate of 1.40
WHERE currency = 'CAD' AND id = 123;
-- Result: $100 USD
```

## Future Enhancements (Not in Scope)
- Fetch live conversion rates from API
- Auto-update `fromUsdRate()` values daily
- Add `conversion_rate_updated_at` timestamp
- Currency rate history table for audit trail
- Admin UI to manage conversion rates
- Bulk invoice creation with rate locking

## Testing Checklist
- [ ] Invoice creation sets conversion_rate automatically
- [ ] USD invoices display correctly (conversion_rate = 1.0)
- [ ] CAD invoices display correctly (conversion_rate = ~1.408)
- [ ] Invoice email shows client currency amounts
- [ ] Invoice lines show client currency rates
- [ ] TimeTracking totals remain in USD
- [ ] Invoice table shows client currency
- [ ] Client form clarifies USD
- [ ] Historical invoices keep locked rates
- [ ] Manual conversion_rate override works
- [ ] All existing tests pass with updates

## Notes
- **Preserves audit trail**: Historical invoices locked at creation
- **Simplified analytics**: All rates in USD internally
- **Client-friendly**: Invoices in their preferred currency
- **Future-proof**: Can add more currencies easily
- **Manual migration required**: User handles historical data conversion
