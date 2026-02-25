# 042 - Software Handover Dummy Seeder for New Task Records

## Summary
Created a seeder to populate 8 dummy "New Task" records in the Admin Software Handover table. Each record has the full data chain: Lead → CompanyDetail → Quotation → 5 QuotationDetails → SoftwareHandover. The quotation items match the SW+HW Proforma Invoice pattern (4 software products + 1 onboarding fee).

## Files Changed

### `database/seeders/SoftwareHandoverDummySeeder.php` (NEW)
- Creates 5 products via `firstOrCreate`: TIMETEC-TA, TIMETEC-TL, TIMETEC-TC, TIMETEC-TP, TIMETEC-ONBOARD
- Seeds 8 dummy companies with varying headcounts (10-100), license types (new sales / addon module), speaker categories
- Each company gets: Lead, CompanyDetail, Quotation with PI reference, 5 QuotationDetails, SoftwareHandover
- All SoftwareHandover records: `status='New'`, `hr_version=1`, `training_type='online_webinar_training'`, modules ta/tl/tc/tp enabled
- `proforma_invoice_product` stores JSON array of Quotation IDs for PI linking

## Migration
```bash
php artisan db:seed --class=SoftwareHandoverDummySeeder
```
