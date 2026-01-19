# 📏 Spacing Reference
## TimeTec CRM - Standardized Spacing System

---

## Recommended 8px-Based Scale

### CSS Custom Properties
```css
:root {
    /* Spacing Scale (8px base) */
    --space-0: 0;
    --space-1: 0.25rem;   /* 4px */
    --space-2: 0.5rem;    /* 8px */
    --space-3: 0.75rem;   /* 12px */
    --space-4: 1rem;      /* 16px */
    --space-5: 1.25rem;   /* 20px */
    --space-6: 1.5rem;    /* 24px */
    --space-8: 2rem;      /* 32px */
    --space-10: 2.5rem;   /* 40px */
    --space-12: 3rem;     /* 48px */
    --space-16: 4rem;     /* 64px */
    --space-20: 5rem;     /* 80px */
    --space-24: 6rem;     /* 96px */
}
```

---

## Current Spacing Usage

### Padding (Component-Specific)

#### Sidebar
```css
Sidebar Item:     12px 15px         /* Non-standard */
Sidebar Logo:     15px 0            /* Non-standard */
Tooltip:          6px 12px          /* Irregular */
```

**Recommended:**
```css
Sidebar Item:     var(--space-3) var(--space-4)  /* 12px 16px */
Sidebar Logo:     var(--space-4) 0               /* 16px 0 */
Tooltip:          var(--space-2) var(--space-3)  /* 8px 12px */
```

#### Modals
```css
Modal Header:     1rem 1.5rem       /* Good */
Modal Body:       1.5rem            /* Good */
Info Box:         1rem              /* Good */
```

**Already Aligned:**
```css
Modal Header:     var(--space-4) var(--space-6)
Modal Body:       var(--space-6)
Info Box:         var(--space-4)
```

#### Tables
```css
Table Header:     1rem 1.5rem       /* Good */
Table Cell:       1rem 1.5rem       /* Good */
Table Row:        0.313rem          /* Non-standard! */
```

**Recommended:**
```css
Table Header:     var(--space-4) var(--space-6)
Table Cell:       var(--space-4) var(--space-6)
Table Row:        var(--space-1)                 /* 4px */
```

#### Buttons
```css
Badge:            0.375rem 0.75rem  /* Good */
Modal Button:     0.625rem 1.5rem   /* Non-standard */
```

**Recommended:**
```css
Badge:            var(--space-1) var(--space-3)  /* 4px 12px */
Modal Button:     var(--space-2) var(--space-6)  /* 8px 24px */
```

---

## Margin Patterns

### Common Margins
```css
Modal Margin:     2rem auto         /* Centering - Good */
Title Section:    margin-bottom: 1.5rem   /* Good */
Title Paragraph:  0.25rem 0 0 0    /* Good */
Sidebar Nav:      padding-top: 0   /* Reset */
Sidebar Item:     margin: 0        /* No gaps */
```

**Using Variables:**
```css
Modal Margin:     var(--space-8) auto
Title Section:    margin-bottom: var(--space-6)
Title Paragraph:  var(--space-1) 0 0 0
```

---

## Gap (Flexbox/Grid)

### Grid/Flex Gaps
```css
Modal Container:  3rem              /* 48px - Good */
Modal Grid:       1.5rem            /* 24px - Good */
Modal Column:     1rem              /* 16px - Good */
Search Dropdown:  0.5rem            /* 8px - Good */
```

**Using Variables:**
```css
Modal Container:  var(--space-12)   /* 48px */
Modal Grid:       var(--space-6)    /* 24px */
Modal Column:     var(--space-4)    /* 16px */
Search Dropdown:  var(--space-2)    /* 8px */
```

---

## Tailwind Spacing Classes

### Commonly Used (Aligned with Scale)
```
p-1   = 4px   = var(--space-1)
p-2   = 8px   = var(--space-2)
p-3   = 12px  = var(--space-3)
p-4   = 16px  = var(--space-4)
p-6   = 24px  = var(--space-6)
p-8   = 32px  = var(--space-8)
p-12  = 48px  = var(--space-12)

m-1   = 4px   = var(--space-1)
m-2   = 8px   = var(--space-2)
m-3   = 12px  = var(--space-3)
m-4   = 16px  = var(--space-4)
m-6   = 24px  = var(--space-6)
m-8   = 32px  = var(--space-8)

gap-2 = 8px   = var(--space-2)
gap-4 = 16px  = var(--space-4)
gap-6 = 24px  = var(--space-6)
```

---

## Component Spacing Guidelines

### Cards
```css
Padding:      var(--space-6)     /* 24px */
Gap:          var(--space-4)     /* 16px */
Margin:       var(--space-4)     /* 16px */
```

### Forms
```css
Input Padding:    var(--space-3) var(--space-4)  /* 12px 16px */
Label Margin:     var(--space-2)                  /* 8px */
Field Gap:        var(--space-6)                  /* 24px */
```

### Buttons
```css
Small:       var(--space-2) var(--space-4)   /* 8px 16px */
Medium:      var(--space-3) var(--space-6)   /* 12px 24px */
Large:       var(--space-4) var(--space-8)   /* 16px 32px */
```

### Tables
```css
Cell Padding:     var(--space-4) var(--space-6)  /* 16px 24px */
Header Padding:   var(--space-4) var(--space-6)  /* 16px 24px */
Row Gap:          0 (borders separate)
```

### Modals
```css
Outer Margin:     var(--space-8)     /* 32px */
Header Padding:   var(--space-4) var(--space-6)
Body Padding:     var(--space-6)
Footer Padding:   var(--space-4) var(--space-6)
```

---

## Inconsistencies Found

### Non-Standard Values (To Fix)
```css
❌ 0.313rem (5px)   - Use var(--space-1) (4px)
❌ 0.625rem (10px)  - Use var(--space-3) (12px) or var(--space-2) (8px)
❌ 15px             - Use var(--space-4) (16px)
❌ 6px              - Use var(--space-2) (8px)
❌ 10px             - Use var(--space-3) (12px) or var(--space-2) (8px)
```

### Mixed Units (To Standardize)
```css
❌ Mixing px and rem
❌ Mixing Tailwind classes and custom CSS values
```

**Solution:**
- Always use CSS custom properties in custom CSS
- Use Tailwind classes in templates
- Never mix units in same component

---

## Migration Guide

### Step 1: Add Variables
```css
/* Add to /public/css/core/variables.css */
:root {
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-12: 3rem;
}
```

### Step 2: Replace in Custom CSS
```css
/* Before */
padding: 12px 15px;

/* After */
padding: var(--space-3) var(--space-4);
```

### Step 3: Update Templates
```html
<!-- Before -->
<div style="padding: 15px;">

<!-- After -->
<div class="p-4">
```

---

## Usage Rules

### DO ✅
- Use CSS custom properties in custom CSS files
- Use Tailwind spacing classes in templates
- Stick to 8px-based scale
- Use consistent units (rem preferred)

### DON'T ❌
- Use pixel values directly
- Create non-standard spacing values
- Mix px and rem in same component
- Use inline styles for spacing

---

## Quick Reference

```
4px   → var(--space-1)  → p-1
8px   → var(--space-2)  → p-2
12px  → var(--space-3)  → p-3
16px  → var(--space-4)  → p-4
24px  → var(--space-6)  → p-6
32px  → var(--space-8)  → p-8
48px  → var(--space-12) → p-12
64px  → var(--space-16) → p-16
```

---

*Last Updated: January 19, 2026*
