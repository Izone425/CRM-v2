# Change #004: Add "By Module" Collapsible List with Priority Breakdown to Ticket Analysis

> **Date**: 2026-02-03
> **Type**: New Feature
> **Status**: Completed

---

## Summary

**What**: Added a new "By Module" collapsible list section to the Ticket Analysis page that mirrors the existing "By Priority" section. The new section groups tickets by module first, then shows priority breakdown within each module when expanded.

**Why**: Users needed to analyze ticket distribution by module with the ability to drill down into priority breakdown per module, complementing the existing "By Priority" view.

**Impact**: Ticket Analysis page now has two parallel collapsible list views - "By Priority" (with module breakdown) on the left, and "By Module" (with priority breakdown) on the right.

**Breaking Change**: No

---

## What It Does (Plain English)

### The Flow

1. **When this happens**: Admin navigates to Ticket Analysis page
2. **The system shows**: Two collapsible list sections side by side:
   - LEFT: "By Priority" - shows priorities with module breakdown (existing)
   - RIGHT: "By Module" - shows modules with priority breakdown (NEW)
3. **When user clicks a module row**: Expands to show priority breakdown with counts and percentages
4. **When user clicks a priority item within a module**: Opens slide-over panel with filtered tickets

### Before vs After (Behavior)

| Before | After |
|--------|-------|
| Only "By Priority" collapsible list existed | Both "By Priority" and "By Module" collapsible lists exist |
| No way to see priority breakdown per module in list format | Can expand any module to see priority distribution |
| "By Module" section only had a donut chart | "By Module" now has both donut chart AND collapsible list |

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Filament/Pages/TicketAnalysis.php` | Modified | Added `openModuleBarSlideOver()` method for handling click on priority items within module breakdown |
| `resources/views/filament/pages/ticket-analysis.blade.php` | Modified | Added "By Module" collapsible list section, swapped positions of the two list sections |

---

## Code Changes (BEFORE -> AFTER)

---

### File: `app/Filament/Pages/TicketAnalysis.php`

**Change Type**: Modified (added new method)

**What Changed**: Added `openModuleBarSlideOver()` method after `openPriorityBarSlideOver()` (around line 790)

#### BEFORE (Old Code)
```php
// Only openPriorityBarSlideOver() existed for Priority -> Module drill-down
public function openPriorityBarSlideOver($priorityId, $moduleId = null)
{
    // ... handles click on module within priority breakdown
}

public function openStatusSlideOver($status)
{
    // ...
}
```

#### AFTER (New Code)
```php
public function openPriorityBarSlideOver($priorityId, $moduleId = null)
{
    // ... existing code unchanged
}

// NEW: Method for Module -> Priority drill-down
public function openModuleBarSlideOver($moduleId, $priorityId = null)
{
    $query = $this->getBaseQuery();
    $module = TicketModule::find($moduleId);

    // Build query for this module
    $ticketQuery = (clone $query)->where('module_id', $moduleId);

    // If priority specified, filter by priority too
    if ($priorityId) {
        $ticketQuery->where('priority_id', $priorityId);
    }

    // Get tickets with priority relationship
    $tickets = $ticketQuery
        ->with('priority:id,name')
        ->select('id', 'ticket_id', 'title', 'company_name', 'status', 'created_date', 'priority_id')
        ->orderByDesc('created_at')
        ->limit(100)
        ->get();

    // Store minimal ticket data for flat list fallback
    $this->ticketList = $tickets->map(function ($ticket) {
        return [
            'id' => $ticket->id,
            'ticket_id' => $ticket->ticket_id,
            'title' => $ticket->title,
            'company_name' => $ticket->company_name,
            'status' => $ticket->status,
            'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
        ];
    })->toArray();

    // Priority colors mapping
    $priorityColors = [
        'Software Bugs' => '#EF4444',
        'Back End Assistance' => '#F59E0B',
        'Critical Enhancement' => '#8B5CF6',
        'Non-Critical Enhancement' => '#10B981',
        'Paid Customization' => '#3B82F6',
    ];

    // Group tickets by priority
    $grouped = $tickets->groupBy(function ($ticket) {
        return $ticket->priority ? $ticket->priority->id : 0;
    });

    $this->ticketsByPriority = $grouped->map(function ($ticketGroup, $priorityIdKey) use ($priorityColors) {
        $firstTicket = $ticketGroup->first();
        $priorityName = $firstTicket && $firstTicket->priority ? $firstTicket->priority->name : 'Unknown';
        $color = $priorityColors[$priorityName] ?? '#6B7280';
        return [
            'id' => $priorityIdKey,
            'name' => $priorityName,
            'color' => $color,
            'count' => $ticketGroup->count(),
            'tickets' => $ticketGroup->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->ticket_id,
                    'title' => $ticket->title,
                    'company_name' => $ticket->company_name,
                    'status' => $ticket->status,
                    'created_date' => $ticket->created_date ? $ticket->created_date->format('Y-m-d') : null,
                ];
            })->values()->toArray(),
        ];
    })->sortByDesc('count')->values()->toArray();

    // Set focus for auto-scroll/expand
    $this->focusPriorityId = $priorityId;

    $this->slideOverTitle = ($module ? $module->name : 'Module');
    $this->showSlideOver = true;
}

public function openStatusSlideOver($status)
{
    // ...
}
```

---

### File: `resources/views/filament/pages/ticket-analysis.blade.php`

**Change Type**: Modified (added new section + swapped positions)

**What Changed**:
1. Added new "By Module" collapsible list section
2. Swapped positions so "By Priority" is on LEFT, "By Module" is on RIGHT

#### BEFORE (Old Code - around line 496)
```blade
<!-- Only "By Priority" collapsible list existed here -->
<!-- Priority Distribution with Module Breakdown -->
<div class="chart-container">
    <div class="chart-title">
        <i class="fa fa-list-alt text-gray-500"></i>
        <span>By Priority</span>
    </div>
    @if(count($priorityModuleData) > 0)
        <!-- Priority list with module breakdown -->
    @endif
</div>
```

#### AFTER (New Code - around line 496)
```blade
<!-- Priority Distribution with Module Breakdown (NOW FIRST - LEFT POSITION) -->
<div class="chart-container">
    <div class="chart-title">
        <i class="fa fa-list-alt text-gray-500"></i>
        <span>By Priority</span>
    </div>

    @if(count($priorityModuleData) > 0)
        @php
            $priorityColors = ['#EF4444', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'];
        @endphp
        <div class="priority-list">
            @foreach($priorityModuleData as $index => $item)
                @php $priorityColor = $priorityColors[$index % count($priorityColors)]; @endphp
                <div class="priority-item" x-data="{ showBreakdown: false }" style="border-left: 4px solid {{ $priorityColor }};">
                    <div class="priority-header cursor-pointer" @click="showBreakdown = !showBreakdown">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 transition-transform text-gray-400" :class="showBreakdown ? 'rotate-90' : ''" ...>
                                <path ... d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="font-medium text-gray-700">{{ $item['name'] }}</span>
                        </span>
                        <span class="font-semibold text-gray-900">{{ $item['count'] }}</span>
                    </div>
                    <!-- Module Breakdown Details (expandable) -->
                    <div x-show="showBreakdown" x-collapse class="module-breakdown">
                        @foreach($item['breakdown'] as $module)
                            <div class="module-item cursor-pointer hover:bg-gray-100"
                                 wire:click="openPriorityBarSlideOver({{ $item['id'] }}, {{ $module['module_id'] }})">
                                <!-- module color, name, count, percentage -->
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- NEW: Module Distribution with Priority Breakdown (List) - RIGHT POSITION -->
<div class="chart-container">
    <div class="chart-title">
        <i class="fa fa-list-alt text-gray-500"></i>
        <span>By Module</span>
    </div>

    @if(count($moduleData) > 0)
        @php
            $moduleColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'];
        @endphp
        <div class="priority-list">
            @foreach($moduleData as $index => $item)
                @php $moduleColor = $moduleColors[$index % count($moduleColors)]; @endphp
                <div class="priority-item" x-data="{ showBreakdown: false }" style="border-left: 4px solid {{ $moduleColor }};">
                    <div class="priority-header cursor-pointer" @click="showBreakdown = !showBreakdown">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 transition-transform text-gray-400" :class="showBreakdown ? 'rotate-90' : ''" ...>
                                <path ... d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="font-medium text-gray-700">{{ $item['name'] }}</span>
                        </span>
                        <span class="font-semibold text-gray-900">{{ $item['count'] }}</span>
                    </div>
                    <!-- Priority Breakdown Details (expandable) -->
                    <div x-show="showBreakdown" x-collapse class="module-breakdown">
                        @foreach($item['breakdown'] as $priority)
                            <div class="module-item cursor-pointer hover:bg-gray-100"
                                 wire:click="openModuleBarSlideOver({{ $item['id'] }}, {{ $priority['priority_id'] }})">
                                <!-- priority color, name, count, percentage -->
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
```

---

## Visual Layout

### Final Layout (After All Changes)

```
┌─────────────────────────────┐  ┌─────────────────────────────┐
│ By Priority                 │  │ By Module                   │
├─────────────────────────────┤  ├─────────────────────────────┤
│ > Back End Assistance   73  │  │ > Payroll              75   │
│ > Software Bugs         61  │  │   ■ Back End Assist.   43   │
│ > Critical Enhancement   8  │  │   ■ Software Bugs      26   │
│ > Non-Critical Enh.      2  │  │ > Time Attendance      52   │
│ > RFQ Customization      1  │  │ > Leave                38   │
└─────────────────────────────┘  └─────────────────────────────┘
        (LEFT)                           (RIGHT)
```

### Color Schemes

**Priority Colors** (used for "By Priority" left borders):
| Index | Color Name | Hex |
|-------|------------|-----|
| 0 | Red | #EF4444 |
| 1 | Amber | #F59E0B |
| 2 | Blue | #3B82F6 |
| 3 | Green | #10B981 |
| 4 | Purple | #8B5CF6 |

**Module Colors** (used for "By Module" left borders):
| Index | Color Name | Hex |
|-------|------------|-----|
| 0 | Blue | #3B82F6 |
| 1 | Green | #10B981 |
| 2 | Amber | #F59E0B |
| 3 | Red | #EF4444 |
| 4 | Purple | #8B5CF6 |

---

## Testing Notes

### How to Test

1. Navigate to **Ticket Analysis** page (`/admin/ticket-analysis`)
2. Verify two collapsible list sections appear side by side:
   - LEFT: "By Priority" with module breakdown
   - RIGHT: "By Module" with priority breakdown
3. Click on a module row in "By Module" section → should expand to show priority breakdown
4. Verify priority breakdown items have correct colors (Red for Software Bugs, Amber for Back End Assistance, etc.)
5. Click a priority item within the expanded module → slide-over should open with filtered tickets
6. Test with V1/V2 product filter → both sections should update
7. Test with date range filter → both sections should reflect filtered data
8. Compare "By Module" list colors with "By Module" donut chart → should match

### Cache Clearing

If changes don't appear, clear cache:
```
https://192.168.1.31:22300/clear-opcache.php
```

---

## Completion Checklist

- [x] Code changes reviewed
- [x] BEFORE/AFTER code documented
- [x] Tested locally
- [x] Ready for developer handoff
