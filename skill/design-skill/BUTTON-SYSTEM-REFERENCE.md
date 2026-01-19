# 🔘 Button System Reference
## TimeTec CRM - Unified Button Component Guide

---

## Overview

**Current Issues:**
- 6 different button styling patterns
- Inconsistent padding, radius, hover states
- No clear usage guidelines

**Solution:**
- Unified button system with 4 variants
- 3 size options
- Consistent styling and behavior

---

## Button Variants

### 1. Primary Button (Default)
```css
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: var(--space-3) var(--space-6);  /* 12px 24px */
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
```

**Usage:**
```html
<button class="btn-primary">Submit</button>
<button class="btn-primary" disabled>Loading...</button>
```

**When to use:**
- Main call-to-action
- Form submissions
- Primary actions on page

---

### 2. Secondary Button (Outline)
```css
.btn-secondary {
    background: transparent;
    color: #667eea;
    padding: var(--space-3) var(--space-6);
    border: 2px solid #667eea;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.btn-secondary:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.btn-secondary:active {
    transform: translateY(0);
}

.btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

**Usage:**
```html
<button class="btn-secondary">Cancel</button>
<button class="btn-secondary">View Details</button>
```

**When to use:**
- Secondary actions
- Cancel buttons
- Non-critical actions
- Multiple actions in same context

---

### 3. Success Button
```css
.btn-success {
    background: #10b981;
    color: white;
    padding: var(--space-3) var(--space-6);
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-success:active {
    transform: translateY(0);
}

.btn-success:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

**Usage:**
```html
<button class="btn-success">Approve</button>
<button class="btn-success">Complete</button>
```

**When to use:**
- Confirmation actions
- Approval actions
- Completion states
- Success confirmations

---

### 4. Danger Button
```css
.btn-danger {
    background: #ef4444;
    color: white;
    padding: var(--space-3) var(--space-6);
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-danger:active {
    transform: translateY(0);
}

.btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

**Usage:**
```html
<button class="btn-danger">Delete</button>
<button class="btn-danger">Reject</button>
```

**When to use:**
- Destructive actions
- Delete operations
- Rejection actions
- Critical warnings

---

## Button Sizes

### Small (.btn-sm)
```css
.btn-sm {
    padding: var(--space-2) var(--space-4);  /* 8px 16px */
    font-size: 0.875rem;  /* 14px */
}
```

**Usage:**
```html
<button class="btn-primary btn-sm">Small Button</button>
```

---

### Medium (.btn-md) - Default
```css
.btn-md {
    padding: var(--space-3) var(--space-6);  /* 12px 24px */
    font-size: 1rem;  /* 16px */
}
```

**Usage:**
```html
<button class="btn-primary">Medium Button</button>
<button class="btn-primary btn-md">Medium Button</button>
```

---

### Large (.btn-lg)
```css
.btn-lg {
    padding: var(--space-4) var(--space-8);  /* 16px 32px */
    font-size: 1.125rem;  /* 18px */
}
```

**Usage:**
```html
<button class="btn-primary btn-lg">Large Button</button>
```

---

## Button States

### Loading State
```css
.btn-loading {
    position: relative;
    color: transparent;
    pointer-events: none;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid white;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
    to { transform: rotate(360deg); }
}
```

**Usage:**
```html
<button class="btn-primary btn-loading">Loading...</button>
```

---

### Icon Buttons
```css
.btn-icon {
    padding: var(--space-3);  /* Square padding */
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-icon svg {
    width: 20px;
    height: 20px;
}
```

**Usage:**
```html
<button class="btn-primary btn-icon">
    <svg>...</svg>
</button>
```

---

### Full Width
```css
.btn-block {
    width: 100%;
    justify-content: center;
}
```

**Usage:**
```html
<button class="btn-primary btn-block">Full Width Button</button>
```

---

## Tailwind Alternatives

### Using Tailwind Classes
```html
<!-- Primary Button -->
<button class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:-translate-y-0.5 hover:shadow-lg transition-all">
    Primary Action
</button>

<!-- Secondary Button -->
<button class="border-2 border-indigo-500 text-indigo-500 px-6 py-3 rounded-lg font-semibold hover:bg-indigo-500 hover:text-white transition-all">
    Secondary Action
</button>

<!-- Success Button -->
<button class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 hover:-translate-y-0.5 hover:shadow-lg transition-all">
    Approve
</button>

<!-- Danger Button -->
<button class="bg-red-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-600 hover:-translate-y-0.5 hover:shadow-lg transition-all">
    Delete
</button>
```

---

## Migration from Old Patterns

### Old Pattern 1: Filament Standard
```html
<!-- Before -->
<button class="filament-button" style="padding: 10px 15px; border-radius: 8px;">

<!-- After -->
<button class="btn-primary">
```

### Old Pattern 2: Livewire Refresh
```html
<!-- Before -->
<button class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded-md text-sm font-medium">

<!-- After -->
<button class="btn-primary btn-sm">
```

### Old Pattern 3: Modal Buttons
```html
<!-- Before (Close) -->
<button class="modal-button close">

<!-- After -->
<button class="btn-secondary">

<!-- Before (Confirm) -->
<button class="modal-button confirm">

<!-- After -->
<button class="btn-success">
```

### Old Pattern 4: Gradient Buttons
```html
<!-- Before -->
<button style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); ...">

<!-- After -->
<button class="btn-primary">
```

### Old Pattern 5: Auth Buttons
```html
<!-- Before -->
<button class="bg-gradient-to-r from-[#31c6f6] to-[#107eff] rounded-full py-3 px-4 hover:opacity-90">

<!-- After -->
<button class="btn-primary btn-lg">
```

---

## Button Groups

### Horizontal Group
```html
<div class="btn-group">
    <button class="btn-secondary">Option 1</button>
    <button class="btn-secondary">Option 2</button>
    <button class="btn-secondary">Option 3</button>
</div>
```

```css
.btn-group {
    display: inline-flex;
    gap: var(--space-2);
}

.btn-group .btn-secondary:not(:last-child) {
    border-radius: 8px 0 0 8px;
}

.btn-group .btn-secondary:not(:first-child) {
    border-radius: 0 8px 8px 0;
}
```

---

## Accessibility

### Required Attributes
```html
<!-- Descriptive text -->
<button class="btn-primary" aria-label="Submit form">
    Submit
</button>

<!-- Loading state -->
<button class="btn-primary btn-loading" aria-busy="true" aria-label="Loading">
    Loading...
</button>

<!-- Disabled state -->
<button class="btn-primary" disabled aria-disabled="true">
    Disabled
</button>
```

### Keyboard Navigation
- All buttons focusable via Tab
- Enter/Space activates button
- Focus visible via outline

---

## Usage Guidelines

### DO ✅
- Use semantic button variants (primary, secondary, success, danger)
- Include descriptive text or aria-label
- Use appropriate size for context
- Maintain consistent spacing between buttons
- Show loading state for async actions
- Disable during loading

### DON'T ❌
- Create custom button styles
- Use `<div>` or `<a>` for buttons
- Stack multiple hover effects
- Use more than 3 buttons in one context
- Forget disabled/loading states
- Use buttons for navigation (use links)

---

## Complete CSS File

Save as `/public/css/components/buttons.css`:

```css
/* Button Base */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
    user-select: none;
}

/* Variants */
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: transparent;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

/* Sizes */
.btn-sm {
    padding: var(--space-2) var(--space-4);
    font-size: 0.875rem;
    border-radius: 6px;
}

.btn-md, .btn {
    padding: var(--space-3) var(--space-6);
    font-size: 1rem;
    border-radius: 8px;
}

.btn-lg {
    padding: var(--space-4) var(--space-8);
    font-size: 1.125rem;
    border-radius: 8px;
}

/* Hover States */
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* States */
.btn:active {
    transform: translateY(0);
}

.btn:disabled,
.btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-loading {
    position: relative;
    color: transparent;
    pointer-events: none;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin: -8px 0 0 -8px;
    border: 2px solid currentColor;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.6s linear infinite;
}

/* Modifiers */
.btn-block {
    width: 100%;
}

.btn-icon {
    padding: var(--space-3);
}

/* Animation */
@keyframes spinner {
    to { transform: rotate(360deg); }
}
```

---

*Last Updated: January 19, 2026*
