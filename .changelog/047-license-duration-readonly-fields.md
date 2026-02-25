# 047 - Create License Duration: Read-Only Fields from Trial/Pending License

## Summary
Made three fields in the Implementer > Create License Duration modal read-only with values auto-populated from previously saved Trial License and Pending License data:
- **Buffer License Duration** ← Trial License (`type_1_pi_invoice_data['buffer_month']`)
- **Paid License Years** ← Pending License (`floor(type_2_pi_invoice_data['billing_cycle'] / 12)`)
- **Paid License Months** ← Pending License (`type_2_pi_invoice_data['billing_cycle'] % 12`)

## Plain English Explanation
When the implementer opens the "Create License Duration" modal, three of the four top fields are now greyed out (disabled). The system reads the buffer month value that was set during the "Create DB + Trial License" step, and reads the billing cycle that was set during the "Create Pending License" step. These values are split into years and months and pre-filled automatically. Only the "Confirmed Kick-off Date" remains editable. The disabled fields still submit their values when the form is submitted, so the existing license calculation logic continues to work without changes.

## Files Changed

### `app/Livewire/ImplementerDashboard/ImplementerLicense.php`

#### BEFORE - `buffer_months` Select (lines 278-296)
```php
\Filament\Forms\Components\Select::make('buffer_months')
    ->label('Buffer License Duration')
    ->options([...])
    ->required()
    ->default('1')
    ->columnSpan(1),
```

#### AFTER - `buffer_months` Select
```php
\Filament\Forms\Components\Select::make('buffer_months')
    ->label('Buffer License Duration')
    ->options([...])
    ->required()
    ->default(function (SoftwareHandover $record = null) {
        if ($record && !empty($record->type_1_pi_invoice_data)) {
            $data = $record->type_1_pi_invoice_data;
            if (isset($data['buffer_month'])) {
                return (string) $data['buffer_month'];
            }
        }
        return '1';
    })
    ->disabled()
    ->dehydrated(true)
    ->columnSpan(1),
```

#### BEFORE - `paid_license_years` Select (lines 298-315)
```php
\Filament\Forms\Components\Select::make('paid_license_years')
    ->label('Paid License Years')
    ->options([...])
    ->required()
    ->default('1')
    ->columnSpan(1),
```

#### AFTER - `paid_license_years` Select
```php
\Filament\Forms\Components\Select::make('paid_license_years')
    ->label('Paid License Years')
    ->options([...])
    ->required()
    ->default(function (SoftwareHandover $record = null) {
        if ($record && !empty($record->type_2_pi_invoice_data)) {
            $data = $record->type_2_pi_invoice_data;
            if (isset($data['billing_cycle'])) {
                return (string) floor((int) $data['billing_cycle'] / 12);
            }
        }
        return '1';
    })
    ->disabled()
    ->dehydrated(true)
    ->columnSpan(1),
```

#### BEFORE - `paid_license_months` Select (lines 317-335)
```php
\Filament\Forms\Components\Select::make('paid_license_months')
    ->label('Paid License Months')
    ->options([...])
    ->required()
    ->default('0')
    ->columnSpan(1),
```

#### AFTER - `paid_license_months` Select
```php
\Filament\Forms\Components\Select::make('paid_license_months')
    ->label('Paid License Months')
    ->options([...])
    ->required()
    ->default(function (SoftwareHandover $record = null) {
        if ($record && !empty($record->type_2_pi_invoice_data)) {
            $data = $record->type_2_pi_invoice_data;
            if (isset($data['billing_cycle'])) {
                return (string) ((int) $data['billing_cycle'] % 12);
            }
        }
        return '0';
    })
    ->disabled()
    ->dehydrated(true)
    ->columnSpan(1),
```

## Data Flow
- Trial License buffer_month (1-6) → Buffer License Duration dropdown (read-only)
- Pending License billing_cycle (12/24/36/48/60) → Paid License Years (1/2/3/4/5 years, read-only) + Paid License Months (0 months, read-only)

## Fallback
When Trial/Pending License data doesn't exist: Buffer=1 month, Years=1 year, Months=0 months (same as previous defaults).

## Migration Steps
None required. No database changes.

## Rollback Plan
Remove `->disabled()`, `->dehydrated(true)`, and revert `->default()` closures back to static string defaults ('1', '1', '0').
