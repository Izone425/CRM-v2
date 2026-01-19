# Admin Portal Dashboard - Data Summary

## 📊 Current Dashboard Metrics (January 2026)

### Metric Cards
1. **Trial to Paid Conversion**: 20 conversions (+66.7% vs last month ↑)
2. **Total Active Resellers**: 21 partners
3. **Total Active Distributors**: 21 networks
4. **New Sign Ups This Month**: 56 signups (+60% vs last month ↑)

### Product Performance
- **TimeTec TA**: 13 licenses
- **TimeTec Leave**: 11 licenses
- **TimeTec Claim**: 9 licenses
- **TimeTec Payroll**: 12 licenses

## 🎨 Dashboard Features

✅ **Interactive Metric Cards**
- Gradient backgrounds with blur effects
- Hover animations with glow
- Pulsing status indicators
- Trend comparison badges

✅ **Charts & Visualizations**
- ApexCharts donut chart for products
- Animated bar charts for signups
- SVG circular progress for customers
- Smooth transitions and hover effects

✅ **Filters & Controls**
- Month/Year selectors
- Compare with previous month toggle
- Refresh button
- Export to CSV

## 🔄 To Reseed Data

Run this command anytime to refresh the sample data:
```bash
php artisan db:seed --class=DashboardSampleDataSeeder
```

## 📈 Data Sources

- **Conversions**: `software_handovers` (status='completed', current month)
- **Resellers**: `reseller_v2` (status='active')
- **Sign Ups**: `license_certificates` (paid_license_start in current month)
- **Products**: `software_handovers` (ta, tl, tc, tp boolean flags)

## 🎯 Dashboard URL

Access the dashboard at:
**Sidebar → Timetec Hr-Admin Portal → Dashboard**

Route: `/admin/hr-product-dashboard`
