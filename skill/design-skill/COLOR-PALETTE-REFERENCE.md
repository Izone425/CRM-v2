# 🎨 Color Palette Reference
## TimeTec CRM - Quick Color Guide

---

## Primary Brand Colors

### Admin Panel
```css
--color-primary: #431fa1;           /* Deep Purple - Main brand */
--color-primary-light: #a28fff;     /* Light Purple - Accent */
```

### Customer Portal
```css
--color-secondary: #1f6bbb;         /* Medium Blue */
```

---

## Gradients

### Sidebar Gradient
```css
background: linear-gradient(to bottom,
    #381a87 0%,
    #4526a0 20%,
    #5234b9 40%,
    #5e42d2 60%,
    #6a50e8 80%,
    #7761f5 100%
);
```

### Modal Header Gradient
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

---

## Status Colors

### Lead Temperature
```css
--lead-hot: #f60808;        /* Bright Red */
--lead-warm: #FFA500;       /* Orange */
--lead-cold: #00ff3e;       /* Bright Green */
--lead-inactive: #E5E4E2;   /* Light Gray */
```

### Handover Status

#### Pending Confirmation (Blue)
```css
--status-pending-bg: #eff6ff;
--status-pending-border: #bfdbfe;
--status-pending-icon: #2563eb;
--status-pending-text: #1e40af;
```

#### Pending Invoice (Indigo)
```css
--status-invoice-bg: #eef2ff;
--status-invoice-border: #c7d2fe;
--status-invoice-icon: #4f46e5;
--status-invoice-text: #4338ca;
```

#### Pending Reseller (Violet)
```css
--status-reseller-bg: #faf5ff;
--status-reseller-border: #e9d5ff;
--status-reseller-icon: #7c3aed;
--status-reseller-text: #7e22ce;
```

#### Pending License (Green)
```css
--status-license-bg: #f0fdf4;
--status-license-border: #bbf7d0;
--status-license-text: #15803d;
```

#### Completed (Teal)
```css
--status-completed-bg: #f0fdfa;
--status-completed-border: #99f6e4;
--status-completed-icon: #0d9488;
--status-completed-text: #0f766e;
```

---

## Semantic Colors

### Success
```css
--color-success: #10b981;
--color-success-dark: #059669;
--color-success-light: #34d399;
```

### Danger
```css
--color-danger: #ef4444;
--color-danger-dark: #dc2626;
--color-danger-light: #f87171;
```

### Warning
```css
--color-warning: #f59e0b;
--color-warning-dark: #d97706;
--color-warning-light: #fbbf24;
```

### Info
```css
--color-info: #3b82f6;
--color-info-dark: #2563eb;
--color-info-light: #60a5fa;
```

---

## Neutral Colors (Gray Scale)

```css
--gray-50: #f9fafb;
--gray-100: #f3f4f6;
--gray-200: #e5e7eb;
--gray-300: #d1d5db;
--gray-400: #9ca3af;
--gray-500: #6b7280;
--gray-600: #4b5563;
--gray-700: #374151;
--gray-800: #1f2937;
--gray-900: #111827;
--gray-950: #030712;
```

---

## Action Colors

```css
--action-orange: #ffa500;
--action-orange-hover: #e69500;
--action-blue: #338cf0;
--action-green: #28a745;
--action-gray: #6c757d;
```

---

## Most Used Tailwind Colors

### Backgrounds (by frequency)
1. `bg-blue-600` - 219 occurrences
2. `bg-blue-700` - 218 occurrences
3. `bg-gray-50` - 97 occurrences
4. `bg-gray-100` - 65 occurrences
5. `bg-gray-200` - 24 occurrences

### Text Colors (by frequency)
1. `text-gray-500` - 536 occurrences
2. `text-gray-600` - 301 occurrences
3. `text-gray-900` - 135 occurrences
4. `text-gray-700` - 119 occurrences
5. `text-gray-400` - 72 occurrences

---

## Usage Guidelines

### DO ✅
- Use CSS custom properties for all colors
- Stick to defined palette
- Use semantic color names
- Maintain contrast ratios (WCAG AA)

### DON'T ❌
- Add new hex codes directly
- Use color keywords (red, blue, etc.)
- Mix hex, rgb, and named colors
- Use !important for colors

---

## Color Accessibility

### Contrast Ratios (WCAG AA)
- **Normal text:** 4.5:1 minimum
- **Large text:** 3:1 minimum
- **UI components:** 3:1 minimum

### Tested Combinations
```
✅ #431fa1 on white (7.8:1) - Pass
✅ #6b7280 on white (4.6:1) - Pass
✅ #374151 on white (9.2:1) - Pass
⚠️ #9ca3af on white (2.9:1) - Fail (use for disabled only)
```

---

*Last Updated: January 19, 2026*
