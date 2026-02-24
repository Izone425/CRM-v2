# 038 - Official Receipt Table Column Width Fix

## Summary
Fixed horizontal scrolling on the Official Receipt table by adding CSS nth-child column width percentages. Long emails in "Created By" column now wrap with word-break instead of overflowing.

## Files Changed

### `resources/views/livewire/hr-admin-dashboard/hr-official-receipt-table.blade.php` (MODIFIED)
- Added nth-child column width rules: O/R No(12%), Date(9%), Company(18%), Description(20%), Currency(6%), Amount(10%), Status(7%), Created By(18%)
- Created By column uses `word-break: break-all` for long emails
