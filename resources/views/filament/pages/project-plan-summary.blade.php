<x-filament-panels::page>
    <style>
        /* Container */
        .project-plan-summary {
            padding: 1.5rem 0;
        }

        .project-plan-summary .header-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 640px) {
            .project-plan-summary .header-section {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .project-plan-summary .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            line-height: 1.2;
        }

        .project-plan-summary .page-subtitle {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* View Toggle Buttons */
        .view-toggle-group {
            display: inline-flex;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }

        .view-toggle-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid #d1d5db;
            background-color: white;
            color: #374151;
            transition: all 0.2s;
            cursor: pointer;
        }

        .view-toggle-btn:hover:not(.active):not(:disabled) {
            background-color: #f9fafb;
            color: #111827;
        }

        .view-toggle-btn.active {
            background-color: #4f46e5;
            color: white;
            border-color: #4f46e5;
            z-index: 10;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .view-toggle-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .view-toggle-btn:first-child {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }

        .view-toggle-btn:last-child {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            margin-left: -1px;
        }

        .view-toggle-btn svg {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        /* Card Styles */
        .summary-card {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        /* Table Header */
        .table-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(to right, #f9fafb, #f3f4f6);
            border-bottom: 1px solid #e5e7eb;
        }

        .table-header-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #4b5563;
        }

        .table-header-grid > div:nth-child(2),
        .table-header-grid > div:nth-child(3),
        .table-header-grid > div:nth-child(4) {
            text-align: center;
        }

        .table-header-grid > div:last-child {
            text-align: right;
        }

        /* Implementer Row */
        .implementer-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #f3f4f6;
            cursor: pointer;
            transition: all 0.2s;
        }

        .implementer-row:hover {
            background-color: #eff6ff;
            box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.06);
        }

        .implementer-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .implementer-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);
            color: #1d4ed8;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s;
        }

        .implementer-row:hover .implementer-avatar {
            background: linear-gradient(to bottom right, #bfdbfe, #93c5fd);
        }

        .implementer-name {
            font-weight: 600;
            color: #111827;
        }

        .implementer-row:hover .implementer-name {
            color: #1d4ed8;
        }

        /* Count Badges */
        .count-badge-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            font-size: 1.125rem;
            font-weight: 700;
            border-radius: 0.5rem;
        }

        .count-badge.blue {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .count-badge.orange {
            background-color: #ffedd5;
            color: #c2410c;
        }

        .count-badge.gray {
            background-color: #f3f4f6;
            color: #374151;
        }

        /* Progress */
        .progress-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .progress-content {
            text-align: right;
        }

        .progress-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .progress-percentage.green {
            color: #059669;
        }

        .progress-percentage.red {
            color: #dc2626;
        }

        .progress-fraction {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .arrow-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #9ca3af;
            transition: transform 0.2s;
        }

        .implementer-row:hover .arrow-icon {
            transform: translateX(0.25rem);
            color: #4f46e5;
        }

        /* Empty State */
        .empty-state {
            padding: 4rem 1.5rem;
            text-align: center;
        }

        .empty-state svg {
            width: 4rem;
            height: 4rem;
            margin: 0 auto;
            color: #9ca3af;
        }

        .empty-state-title {
            margin-top: 1rem;
            font-size: 1.125rem;
            font-weight: 500;
            color: #6b7280;
        }

        .empty-state-description {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #9ca3af;
        }

        /* Tier 2 Stats Card */
        .stats-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(to right, #4f46e5, #4338ca);
        }

        .stats-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stats-implementer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
        }

        .stats-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4338ca;
            background-color: white;
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            cursor: pointer;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background-color: #f9fafb;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        .back-btn svg {
            width: 1rem;
            height: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            padding: 1.25rem 1.5rem;
            background-color: #f9fafb;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
        }

        .stat-value.blue {
            color: #2563eb;
        }

        .stat-value.orange {
            color: #ea580c;
        }

        .stat-value.gray {
            color: #374151;
        }

        .stat-label {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
        }

        /* Company Row */
        .company-row {
            padding: 1rem 1.5rem;
            border-top: 1px solid #f3f4f6;
            cursor: pointer;
            transition: all 0.2s;
        }

        .company-row:hover {
            opacity: 0.9;
        }

        .company-row.active {
            border-left: 4px solid #4f46e5;
        }

        /* ✅ Status-based background colors */
        .company-row.status-open {
            background-color: #d1fae5; /* Light green */
        }

        .company-row.status-delay {
            background-color: #fee2e2; /* Light red */
        }

        .company-row.status-open:hover {
            background-color: #a7f3d0; /* Darker green on hover */
        }

        .company-row.status-delay:hover {
            background-color: #fecaca; /* Darker red on hover */
        }

        .company-row.status-open.active {
            background-color: #a7f3d0;
            border-left: 4px solid #059669;
        }

        .company-row.status-delay.active {
            background-color: #fecaca;
            border-left: 4px solid #dc2626;
        }

        .company-row-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .company-row-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .company-row-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            background: linear-gradient(to bottom right, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }

        .company-row-details {
            flex: 1;
        }

        .company-row-name {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        .company-row-code {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .company-row-progress {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .company-progress-bar {
            width: 120px;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .company-progress-fill {
            height: 100%;
            background: linear-gradient(to right, #10b981, #059669);
            transition: width 0.3s;
        }

        .company-progress-text {
            font-size: 0.875rem;
            font-weight: 600;
            color: #059669;
            min-width: 45px;
        }

        .expand-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #9ca3af;
            transition: transform 0.2s;
        }

        .company-row.active .expand-icon {
            transform: rotate(180deg);
        }

        /* ✅ PROJECT PROGRESS STYLES (from CustomerProjectPlan) */
        .project-progress-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .overall-progress-card {
            padding: 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .overall-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
        }

        .overall-title-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .overall-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #1e40af;
        }

        .overall-stats {
            text-align: right;
        }

        .overall-percentage {
            font-size: 24px;
            font-weight: 700;
            color: #1e40af;
            line-height: 1;
        }

        .overall-label {
            font-size: 13px;
            color: #6b7280;
            margin: 4px 0;
        }

        .overall-meta {
            font-size: 11px;
            color: #9ca3af;
        }

        .progress-timeline {
            position: relative;
            overflow-x: auto;
            overflow-y: visible;
            padding-left: 16px;
            padding-bottom: 16px;
            padding-top: 16px;
        }

        .timeline-container {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            min-width: max-content;
            gap: 8px;
            padding-top: 10px;
        }

        .timeline-task {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
            min-width: 0;
        }

        .timeline-task:hover {
            z-index: 999;
        }

        .timeline-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .timeline-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .timeline-circle.completed {
            background-color: #10b981;
            border-color: #10b981;
        }

        .timeline-circle.pending {
            background-color: white;
            border-color: #d1d5db;
        }

        .timeline-circle.in_progress {
            background-color: #fbbf24;
            border-color: #f59e0b;
        }

        .timeline-icon-completed {
            width: 24px;
            height: 24px;
            color: white;
            pointer-events: none;
        }

        .timeline-dot {
            width: 12px;
            height: 12px;
            background-color: #d1d5db;
            border-radius: 50%;
            pointer-events: none;
        }

        .timeline-info {
            margin-top: 12px;
            text-align: center;
            max-width: 180px;
            min-width: 120px;
        }

        .timeline-percentage {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .timeline-percentage.completed { color: #059669; }
        .timeline-percentage.in_progress { color: #d97706; }
        .timeline-percentage.pending { color: #6b7280; }

        .timeline-task-name {
            font-size: 11px;
            color: #6b7280;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.4;
            max-width: 130px;
            margin-bottom: 4px;
        }

        .timeline-status {
            margin-top: 4px;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .timeline-status.completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .timeline-status.in_progress {
            background-color: #fef3c7;
            color: #92400e;
        }

        .timeline-status.pending {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .timeline-line {
            flex: 1;
            height: 2px;
            border-top: 2px solid;
            margin-top: 18px;
            min-width: 32px;
            max-width: 30px;
            flex-shrink: 0;
        }

        .timeline-line.completed { border-color: #10b981; }
        .timeline-line.pending { border-color: #d1d5db; }

        .progress-overview-card {
            padding: 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: none;
            margin-bottom: 10px;
        }

        .progress-overview-card.show {
            display: block;
        }

        .module-header-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
        }

        .module-title-wrapper {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .module-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
        }

        .module-stats {
            text-align: right;
        }

        .module-percentage {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
            line-height: 1;
        }

        .module-label {
            font-size: 13px;
            color: #6b7280;
            margin: 4px 0;
        }

        .module-meta {
            font-size: 11px;
            color: #9ca3af;
        }

        /* Hide original tooltips */
        .timeline-circle > .task-tooltip {
            display: none !important;
        }

        /* Tooltip Container - Fixed Position */
        #tooltip-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 99999;
        }

        #tooltip-container .task-tooltip {
            position: fixed;
            background-color: #1f2937;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            min-width: 220px;
            pointer-events: none;
            z-index: 99999;
        }

        #tooltip-container .task-tooltip.show {
            opacity: 1 !important;
            visibility: visible !important;
        }

        #tooltip-container .task-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 8px solid transparent;
            border-top-color: #1f2937;
        }

        .tooltip-task-name {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 6px;
            color: #93c5fd;
        }

        .tooltip-divider {
            height: 1px;
            background-color: #374151;
            margin: 8px 0;
        }

        .tooltip-status {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .tooltip-status.in-progress {
            color: #fbbf24;
        }

        .tooltip-status.completed {
            color: #10b981;
        }

        .tooltip-status.pending {
            color: #9ca3af;
        }

        .tooltip-date-label {
            font-size: 10px;
            color: #9ca3af;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .tooltip-date-value {
            font-size: 11px;
            color: #d1d5db;
            margin-bottom: 6px;
        }

        .tooltip-progress {
            font-size: 11px;
            font-weight: 600;
            color: #93c5fd;
        }

        .tooltip-day-counter {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 6px;
            display: inline-block;
        }

        .tooltip-day-counter.overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .tooltip-day-counter.urgent {
            background-color: #fef3c7;
            color: #92400e;
        }

        .tooltip-day-counter.normal {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .tooltip-day-counter.completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Hide original tooltips inside circles */
        .timeline-circle .task-tooltip {
            display: none !important;
        }

        .sort-controls-wrapper {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .sort-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .sort-header-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .sort-header-title svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #818cf8;
        }

        .sort-clear-btn {
            font-size: 0.75rem;
            font-weight: 600;
            color: #ef4444;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sort-clear-btn:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* Active Rules - Compact Horizontal Layout */
        .sort-active-rules {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .sort-rule-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.75rem;
            background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
            border-radius: 2rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: white;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
            transition: all 0.2s;
        }

        .sort-rule-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }

        .sort-chip-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .sort-chip-field {
            flex: 1;
        }

        .sort-chip-actions {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.25rem;
            padding-left: 0.5rem;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sort-chip-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }

        .sort-chip-btn:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        .sort-chip-btn svg {
            width: 0.875rem;
            height: 0.875rem;
        }

        /* Add Sort Buttons - Compact */
        .sort-add-section {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f3f4f6;
        }

        .sort-add-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #6b7280;
        }

        .sort-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #4f46e5;
            background: white;
            border: 1px solid #e0e7ff;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sort-add-btn:hover {
            background: #f5f3ff;
            border-color: #c7d2fe;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
        }

        .sort-add-btn svg {
            width: 1rem;
            height: 1rem;
        }

        /* Empty State */
        .sort-empty-state {
            text-align: center;
            padding: 1rem;
            color: #9ca3af;
            font-size: 0.8125rem;
            font-style: italic;
        }

        .sort-chip-btn.direction-btn {
            background: rgba(255, 255, 255, 0.3);
        }

        .sort-chip-btn.direction-btn.desc {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .sort-chip-btn.direction-btn.asc {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }

        .sort-chip-btn.direction-btn:hover {
            transform: scale(1.1);
        }

        .sort-chip-btn.direction-btn.desc:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 3px 6px rgba(16, 185, 129, 0.4);
        }

        .sort-chip-btn.direction-btn.asc:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            box-shadow: 0 3px 6px rgba(245, 158, 11, 0.4);
        }

        /* ✅ Add visual indicator for direction */
        .sort-chip-btn svg {
            width: 0.875rem;
            height: 0.875rem;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }

        /* Responsive */
        @media (max-width: 640px) {
            .sort-active-rules {
                flex-direction: column;
            }

            .sort-rule-chip {
                width: 100%;
                justify-content: space-between;
            }
        }

        .category-mode-toggle {
            display: inline-flex;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }

        .category-mode-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid #d1d5db;
            background-color: white;
            color: #374151;
            transition: all 0.2s;
            cursor: pointer;
        }

        .category-mode-btn:hover:not(.active) {
            background-color: #f9fafb;
            color: #111827;
        }

        .category-mode-btn.active {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
            z-index: 10;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .category-mode-btn:first-child {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }

        .category-mode-btn:last-child {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            margin-left: -1px;
        }

        .category-mode-btn svg {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        /* Update header layout */
        .header-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .header-top {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .header-controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        @media (min-width: 640px) {
            .header-top {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .header-controls {
                flex-direction: row;
                align-items: center;
                gap: 1.5rem;
            }
        }
    </style>

    @script
    <script>
        function toggleModuleDetails(moduleKey) {
            const moduleCard = document.getElementById('module-' + moduleKey);
            if (moduleCard) {
                moduleCard.classList.toggle('show');
            }
        }

        function initTooltips() {
            const container = document.getElementById('tooltip-container');
            if (!container) {
                console.warn('Tooltip container not found');
                return;
            }

            // Clear existing tooltips
            container.innerHTML = '';

            // Find all timeline circles
            const circles = document.querySelectorAll('.timeline-circle');
            console.log('Found circles:', circles.length);

            circles.forEach((circle, idx) => {
                const tooltipOriginal = circle.querySelector('.task-tooltip');
                if (!tooltipOriginal) {
                    console.warn(`No tooltip in circle ${idx}`);
                    return;
                }

                const tooltipHtml = tooltipOriginal.innerHTML;

                // Remove old listeners by cloning
                const newCircle = circle.cloneNode(true);
                circle.parentNode.replaceChild(newCircle, circle);

                // Add event listeners to the new circle
                newCircle.addEventListener('mouseenter', function(e) {
                    // Clear any existing tooltips
                    container.innerHTML = '';

                    const rect = this.getBoundingClientRect();

                    const tooltip = document.createElement('div');
                    tooltip.className = 'task-tooltip show';
                    tooltip.innerHTML = tooltipHtml;

                    // Position above the circle
                    tooltip.style.bottom = (window.innerHeight - rect.top + 12) + 'px';
                    tooltip.style.left = (rect.left + rect.width / 2) + 'px';
                    tooltip.style.transform = 'translateX(-50%)';

                    container.appendChild(tooltip);
                    this.tooltipElement = tooltip;

                    console.log('Tooltip shown');
                });

                newCircle.addEventListener('mouseleave', function() {
                    if (this.tooltipElement) {
                        this.tooltipElement.remove();
                        this.tooltipElement = null;
                        console.log('Tooltip hidden');
                    }
                });
            });
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, initializing tooltips...');
            setTimeout(initTooltips, 100);
        });

        // Re-initialize after Livewire updates
        Livewire.hook('morph.updated', () => {
            console.log('Livewire updated, re-initializing tooltips...');
            setTimeout(initTooltips, 100);
        });

        // Also listen for wire:navigate
        document.addEventListener('livewire:navigated', () => {
            console.log('Livewire navigated, re-initializing tooltips...');
            setTimeout(initTooltips, 100);
        });

        // Listen for custom event from component
        $wire.on('init-tooltips', () => {
            console.log('Custom event received, re-initializing tooltips...');
            setTimeout(initTooltips, 100);
        });

        // Fallback: try every 2 seconds for first 10 seconds
        let attempts = 0;
        const intervalId = setInterval(() => {
            attempts++;
            console.log(`Fallback initialization attempt ${attempts}`);
            initTooltips();

            if (attempts >= 5) {
                clearInterval(intervalId);
            }
        }, 2000);
    </script>
    @endscript

    <div class="project-plan-summary">
        {{-- Header with View Toggle --}}
        <div class="header-section">
            <div>
                <h1 class="page-title">PROJECT PLAN SUMMARY</h1>
                <p class="page-subtitle">Open & Delay Projects Only</p>
            </div>

            <div class="header-controls">
                {{-- NEW: Category Mode Toggle --}}
                <div class="category-mode-toggle" role="group">
                    <button
                        wire:click="switchCategoryMode('implementer')"
                        class="category-mode-btn {{ $categoryMode === 'implementer' ? 'active' : '' }}"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        By Implementer
                    </button>
                    <button
                        wire:click="switchCategoryMode('salesperson')"
                        class="category-mode-btn {{ $categoryMode === 'salesperson' ? 'active' : '' }}"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        By Salesperson
                    </button>
                </div>

                {{-- View Toggle Buttons --}}
                <div class="view-toggle-group" role="group">
                    <button
                        wire:click="switchView('tier1')"
                        class="view-toggle-btn {{ $activeView === 'tier1' ? 'active' : '' }}"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Overview
                    </button>
                    <button
                        wire:click="switchView('tier2')"
                        class="view-toggle-btn {{ $activeView === 'tier2' ? 'active' : '' }}"
                        {{ (!$selectedImplementer && !$selectedSalesperson) ? 'disabled' : '' }}
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Details
                    </button>
                </div>
            </div>
        </div>

        <div id="tooltip-container"></div>

        {{-- Tier 1 View --}}
        @if($activeView === 'tier1')
            <div class="summary-card">
                <div class="table-header">
                    <div class="table-header-grid">
                        {{-- ✅ Dynamic header based on category mode --}}
                        <div>{{ $categoryMode === 'salesperson' ? 'Salesperson Name' : 'Implementer Name' }}</div>
                        <div>Open</div>
                        <div>Delay</div>
                        <div>Total</div>
                        <div>Progress</div>
                    </div>
                </div>

                <div>
                    {{-- ✅ Show different data based on category mode --}}
                    @if($categoryMode === 'salesperson')
                        @forelse($this->getSalespersonTier1Data() as $row)
                            {{-- Salesperson Row --}}
                            <div
                                wire:click="selectSalesperson({{ $row['salesperson_id'] }})"
                                class="implementer-row"
                            >
                                <div class="implementer-info">
                                    <div class="implementer-avatar">
                                        {{ substr($row['salesperson_name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="implementer-name">
                                            {{ $row['salesperson_name'] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="count-badge-wrapper">
                                    <span class="count-badge blue">{{ $row['open_count'] }}</span>
                                </div>

                                <div class="count-badge-wrapper">
                                    <span class="count-badge orange">{{ $row['delay_count'] }}</span>
                                </div>

                                <div class="count-badge-wrapper">
                                    <span class="count-badge gray">{{ $row['total_projects'] }}</span>
                                </div>

                                <div class="progress-wrapper">
                                    <div class="progress-content">
                                        <div class="progress-percentage {{ $row['average_percentage'] >= 60 ? 'green' : 'red' }}">
                                            {{ $row['average_percentage'] }}%
                                        </div>
                                        <div class="progress-fraction">
                                            {{ $row['total_progress'] }}/{{ $row['total_tasks'] }}
                                        </div>
                                    </div>
                                    <svg class="arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <p class="empty-state-title">No salesperson data available</p>
                                <p class="empty-state-description">There are no open or delayed projects assigned to salespersons.</p>
                            </div>
                        @endforelse
                    @else
                        @forelse($this->getTier1Data() as $row)
                            {{-- Implementer Row --}}
                            <div
                                wire:click="selectImplementer('{{ $row['implementer_name'] }}')"
                                class="implementer-row"
                            >
                                <div class="implementer-info">
                                    <div class="implementer-avatar">
                                        {{ substr($row['implementer_name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="implementer-name">
                                            {{ $row['implementer_name'] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="count-badge-wrapper">
                                    <span class="count-badge blue">{{ $row['open_count'] }}</span>
                                </div>

                                <div class="count-badge-wrapper">
                                    <span class="count-badge orange">{{ $row['delay_count'] }}</span>
                                </div>

                                <div class="count-badge-wrapper">
                                    <span class="count-badge gray">{{ $row['total_projects'] }}</span>
                                </div>

                                <div class="progress-wrapper">
                                    <div class="progress-content">
                                        <div class="progress-percentage {{ $row['average_percentage'] >= 60 ? 'green' : 'red' }}">
                                            {{ $row['average_percentage'] }}%
                                        </div>
                                        <div class="progress-fraction">
                                            {{ $row['total_progress'] }}/{{ $row['total_tasks'] }}
                                        </div>
                                    </div>
                                    <svg class="arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="empty-state-title">No project data available</p>
                                <p class="empty-state-description">There are no open or delayed projects at the moment.</p>
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>
        @endif

        {{-- Tier 2 View --}}
        @if($activeView === 'tier2' && ($selectedImplementer || $selectedSalesperson))
            <div class="project-progress-container">
                {{-- Header Stats Card --}}
                @if($categoryMode === 'salesperson' && $this->getSalespersonStats())
                    <div class="summary-card" style="margin-bottom: 1.5rem;">
                        <div class="stats-header">
                            <div class="stats-header-content">
                                <div class="stats-implementer-info">
                                    <div class="stats-avatar">
                                        {{ substr($this->getSalespersonStats()['name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <h2 class="stats-name">{{ $this->getSalespersonStats()['name'] }}</h2>
                                    </div>
                                </div>
                                <button wire:click="switchView('tier1')" class="back-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Back to Overview
                                </button>
                            </div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value blue">{{ $this->getSalespersonStats()['open_count'] }}</div>
                                <div class="stat-label">Open Projects</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value orange">{{ $this->getSalespersonStats()['delay_count'] }}</div>
                                <div class="stat-label">Delayed Projects</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value gray">{{ $this->getSalespersonStats()['total_projects'] }}</div>
                                <div class="stat-label">Total Projects</div>
                            </div>
                        </div>
                    </div>
                @elseif($categoryMode === 'implementer' && $this->getImplementerStats())
                    <div class="summary-card" style="margin-bottom: 1.5rem;">
                        <div class="stats-header">
                            <div class="stats-header-content">
                                <div class="stats-implementer-info">
                                    <div class="stats-avatar">
                                        {{ substr($this->getImplementerStats()['name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <h2 class="stats-name">{{ $this->getImplementerStats()['name'] }}</h2>
                                    </div>
                                </div>
                                <button wire:click="switchView('tier1')" class="back-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Back to Overview
                                </button>
                            </div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value blue">{{ $this->getImplementerStats()['open_count'] }}</div>
                                <div class="stat-label">Open Projects</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value orange">{{ $this->getImplementerStats()['delay_count'] }}</div>
                                <div class="stat-label">Delayed Projects</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value gray">{{ $this->getImplementerStats()['total_projects'] }}</div>
                                <div class="stat-label">Total Projects</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="sort-controls-wrapper">
                    {{-- Header --}}
                    <div class="sort-header">
                        <div class="sort-header-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                            </svg>
                            Sorting Rules
                        </div>
                        @if(count($sortRules) > 1)
                            <button wire:click="clearAllSorts" class="sort-clear-btn">
                                Clear All
                            </button>
                        @endif
                    </div>

                    {{-- Active Sort Rules --}}
                    @if(count($sortRules) > 0)
                        <div class="sort-active-rules">
                            @foreach($sortRules as $index => $rule)
                                <div class="sort-rule-chip">
                                    {{-- Priority Badge --}}
                                    <span class="sort-chip-badge">{{ $index + 1 }}</span>

                                    {{-- Field Name --}}
                                    <span class="sort-chip-field">
                                        @if($rule['field'] === 'percentage') Progress %
                                        @elseif($rule['field'] === 'headcount') Headcount
                                        @elseif($rule['field'] === 'status') Status
                                        @elseif($rule['field'] === 'company_name') Company
                                        @endif
                                    </span>

                                    {{-- Actions --}}
                                    <div class="sort-chip-actions">
                                        {{-- ✅ UPDATED: Direction Toggle with Color Coding --}}
                                        <button
                                            wire:click="toggleSort('{{ $rule['field'] }}')"
                                            class="sort-chip-btn direction-btn {{ $rule['direction'] }}"
                                            title="Toggle direction (Currently: {{ strtoupper($rule['direction']) }})">
                                            @if($rule['direction'] === 'desc')
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @else
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @endif
                                        </button>

                                        {{-- Move Up --}}
                                        @if($index > 0)
                                            <button wire:click="moveSortUp({{ $index }})" class="sort-chip-btn" title="Move up">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            </button>
                                        @endif

                                        {{-- Move Down --}}
                                        @if($index < count($sortRules) - 1)
                                            <button wire:click="moveSortDown({{ $index }})" class="sort-chip-btn" title="Move down">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        @endif

                                        {{-- Remove --}}
                                        @if(count($sortRules) > 1)
                                            <button wire:click="removeSort({{ $index }})" class="sort-chip-btn" title="Remove">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sort-empty-state">
                            No sorting rules applied. Add a rule below to sort the list.
                        </div>
                    @endif

                    {{-- Add Sort Buttons --}}
                    <div class="sort-add-section">
                        <span class="sort-add-label">Add Sort:</span>

                        <button wire:click="toggleSort('percentage')" class="sort-add-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Progress %
                        </button>

                        <button wire:click="toggleSort('headcount')" class="sort-add-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Headcount
                        </button>

                        <button wire:click="toggleSort('status')" class="sort-add-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Status
                        </button>

                        <button wire:click="toggleSort('company_name')" class="sort-add-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Company
                        </button>
                    </div>
                </div>

                {{-- Company List --}}
                <div class="summary-card">
                    {{-- ✅ Use different data source based on category mode --}}
                    @forelse($categoryMode === 'salesperson' ? $this->getSalespersonTier2Data() : $this->getTier2Data() as $company)
                        <div>
                            {{-- Company Row --}}
                            <div
                                wire:click="selectCompany({{ $company['sw_id'] }})"
                                class="company-row
                                    {{ $selectedSwId === $company['sw_id'] ? 'active' : '' }}
                                    {{ strtolower($company['status']) === 'open' ? 'status-open' : 'status-delay' }}"
                            >
                                <div class="company-row-content">
                                    <div class="company-row-info">
                                        <div class="company-row-icon">
                                            <svg fill="currentColor" viewBox="0 0 20 20" style="width: 1.5rem; height: 1.5rem;">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="company-row-details">
                                            <div class="company-row-name">{{ $company['company_name'] }}</div>
                                            <div class="company-row-code">
                                                {{ $company['project_code'] }}
                                                {{-- ✅ Show implementer info when viewing by salesperson --}}
                                                @if($categoryMode === 'salesperson' && !empty($company['implementer']))
                                                    <span style="color: #6b7280; font-size: 0.6875rem; margin-left: 0.5rem;">
                                                        • Impl: {{ $company['implementer'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="company-row-progress">
                                        <div class="company-progress-bar">
                                            <div class="company-progress-fill" style="width: {{ $company['project_progress'] }}%"></div>
                                        </div>
                                        <div class="company-progress-text">{{ $company['project_progress'] }}%</div>
                                        <svg class="expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Project Plan Details (Tier 3) - Only for THIS sw_id --}}
                            @if($selectedSwId === $company['sw_id'] && $this->getProjectPlanData())
                                @php
                                    $planData = $this->getProjectPlanData();
                                    $overallSummary = $planData['overallSummary'];
                                    $progressOverview = $planData['progressOverview'];
                                @endphp

                                {{-- ✅ OVERALL PROJECT PROGRESS OVERVIEW --}}
                                <div class="overall-progress-card">
                                    <div class="overall-header">
                                        <div class="overall-title-section">
                                            <h4 class="overall-title">Project Progress Overview</h4>
                                            <div class="overall-meta">{{ $overallSummary['completedTasks'] }}/{{ $overallSummary['totalTasks'] }} tasks completed</div>
                                        </div>
                                        <div class="overall-stats">
                                            <div class="overall-percentage">{{ $overallSummary['overallProgress'] }}%</div>
                                            <div class="overall-label">Overall Completion</div>
                                        </div>
                                    </div>

                                    {{-- Modules Timeline --}}
                                    <div class="progress-timeline">
                                        <div class="timeline-container">
                                            @foreach($overallSummary['modules'] as $index => $moduleSummary)
                                                @php
                                                    $moduleProgress = $moduleSummary['progress'];
                                                    $moduleStatus = 'pending';
                                                    if ($moduleProgress == 100) {
                                                        $moduleStatus = 'completed';
                                                    } elseif ($moduleProgress > 0) {
                                                        $moduleStatus = 'in_progress';
                                                    }
                                                    $isCompleted = $moduleStatus === 'completed';
                                                    $isInProgress = $moduleStatus === 'in_progress';

                                                    $moduleKey = str_replace([' ', ':'], '-', $moduleSummary['module_name']);
                                                @endphp

                                                <div class="timeline-task">
                                                    <div class="timeline-circle {{ $moduleStatus }}" onclick="toggleModuleDetails('{{ $moduleKey }}-{{ $company['sw_id'] }}')" style="width: 48px; height: 48px;">
                                                        {{-- Module Tooltip --}}
                                                        <div class="task-tooltip">
                                                            <div class="tooltip-task-name">{{ $moduleSummary['module_name'] }}</div>
                                                            <div class="tooltip-divider"></div>
                                                            <div class="tooltip-progress">{{ $moduleSummary['completed'] }}/{{ $moduleSummary['total'] }} tasks completed</div>
                                                        </div>

                                                        @if($isCompleted)
                                                            <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @elseif($isInProgress)
                                                            <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @else
                                                            <div class="timeline-dot"></div>
                                                        @endif
                                                    </div>

                                                    <div class="timeline-info">
                                                        <div class="timeline-percentage {{ $moduleStatus }}">{{ $moduleProgress }}%</div>
                                                        <div class="timeline-task-name">{{ $moduleSummary['module_name'] }}</div>
                                                        <div class="timeline-task-name">{{ $moduleSummary['completed'] }}/{{ $moduleSummary['total'] }} tasks</div>
                                                        <div class="timeline-status {{ $moduleStatus }}">
                                                            {{ str_replace('_', ' ', $moduleStatus) }}
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($index < count($overallSummary['modules']) - 1)
                                                    @php
                                                        $nextModule = $overallSummary['modules'][$index + 1];
                                                        $nextModuleStatus = $nextModule['progress'] == 100 ? 'completed' : ($nextModule['progress'] > 0 ? 'in_progress' : 'pending');
                                                        $lineCompleted = $isCompleted && $nextModuleStatus === 'completed';
                                                    @endphp
                                                    <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}" style="margin-top: 24px; max-width: 60px;"></div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Module by Module Details --}}
                                @foreach($progressOverview as $moduleName => $moduleData)
                                    @php
                                        $moduleKey = str_replace([' ', ':'], '-', $moduleName);
                                        $moduleProgress = $moduleData['overallProgress'];

                                        // Auto-open logic
                                        $showByDefault = false;
                                        if ($moduleProgress > 0 && $moduleProgress < 100) {
                                            $showByDefault = true;
                                        } elseif ($moduleProgress == 0) {
                                            static $firstPendingFound = [];
                                            if (!isset($firstPendingFound[$company['sw_id']])) {
                                                $showByDefault = true;
                                                $firstPendingFound[$company['sw_id']] = true;
                                            }
                                        }
                                    @endphp

                                    <div class="progress-overview-card {{ $showByDefault ? 'show' : '' }}" id="module-{{ $moduleKey }}-{{ $company['sw_id'] }}">
                                        <div class="module-header-section">
                                            <div class="module-title-wrapper">
                                                <h4 class="module-title">{{ $moduleName }}</h4>
                                                <div class="module-meta">{{ $moduleData['completedTasks'] }}/{{ $moduleData['totalTasks'] }} tasks completed</div>
                                            </div>
                                            <div class="module-stats">
                                                <div class="module-percentage">{{ $moduleData['overallProgress'] }}%</div>
                                                <div class="module-label">Module Completion</div>
                                            </div>
                                        </div>

                                        {{-- Progress Timeline --}}
                                        <div class="progress-timeline">
                                            <div class="timeline-container">
                                                @foreach($moduleData['tasks'] as $index => $task)
                                                    @php
                                                        $taskStatus = $task['status'] ?? 'pending';
                                                        $planStartDate = $task['plan_start_date'] ? \Carbon\Carbon::parse($task['plan_start_date']) : null;
                                                        $planEndDate = $task['plan_end_date'] ? \Carbon\Carbon::parse($task['plan_end_date']) : null;
                                                        $actualStartDate = $task['actual_start_date'] ? \Carbon\Carbon::parse($task['actual_start_date']) : null;
                                                        $actualEndDate = $task['actual_end_date'] ? \Carbon\Carbon::parse($task['actual_end_date']) : null;

                                                        $visualStatus = $taskStatus;
                                                        $isCompleted = false;
                                                        $isInProgress = false;

                                                        if ($taskStatus === 'completed' || $actualEndDate) {
                                                            $visualStatus = 'completed';
                                                            $isCompleted = true;
                                                        } elseif ($taskStatus === 'in_progress' || $actualStartDate || ($planStartDate && $planEndDate)) {
                                                            $visualStatus = 'in_progress';
                                                            $isInProgress = true;
                                                        } else {
                                                            $visualStatus = 'pending';
                                                        }

                                                        $tooltipStatusText = ucfirst(str_replace('_', ' ', $visualStatus));
                                                        $tooltipStatusClass = $visualStatus === 'completed' ? 'completed' : ($visualStatus === 'in_progress' ? 'in-progress' : 'pending');

                                                        $plannedPeriod = '';
                                                        if ($planStartDate && $planEndDate) {
                                                            $plannedPeriod = $planStartDate->format('d M Y') . ' - ' . $planEndDate->format('d M Y');
                                                        }

                                                        $actualPeriod = '';
                                                        if ($actualStartDate && $actualEndDate) {
                                                            $actualPeriod = $actualStartDate->format('d M Y') . ' - ' . $actualEndDate->format('d M Y');
                                                        } elseif ($actualStartDate) {
                                                            $actualPeriod = $actualStartDate->format('d M Y') . ' - Now';
                                                        }

                                                        // Calculate days left
                                                        $daysLeft = null;
                                                        $dayCounterClass = 'normal';
                                                        $dayCounterText = '';

                                                        if ($isCompleted) {
                                                            $dayCounterText = 'Completed';
                                                            $dayCounterClass = 'completed';
                                                        } elseif ($planEndDate) {
                                                            $today = \Carbon\Carbon::now();
                                                            $daysLeft = $today->diffInDays($planEndDate, false);

                                                            if ($daysLeft < 0) {
                                                                $dayCounterText = abs($daysLeft) . ' days overdue';
                                                                $dayCounterClass = 'overdue';
                                                            } elseif ($daysLeft == 0) {
                                                                $dayCounterText = 'Due today';
                                                                $dayCounterClass = 'urgent';
                                                            } elseif ($daysLeft <= 3) {
                                                                $dayCounterText = $daysLeft . ' days left';
                                                                $dayCounterClass = 'urgent';
                                                            } else {
                                                                $dayCounterText = $daysLeft . ' days left';
                                                                $dayCounterClass = 'normal';
                                                            }
                                                        }
                                                    @endphp

                                                    <div class="timeline-task">
                                                        <div class="timeline-circle {{ $visualStatus }}">
                                                            {{-- Task Tooltip --}}
                                                            <div class="task-tooltip">
                                                                <div class="tooltip-task-name">{{ $task['task_name'] ?? 'N/A' }}</div>
                                                                <div class="tooltip-status {{ $tooltipStatusClass }}">
                                                                    Status: {{ $tooltipStatusText }}
                                                                </div>

                                                                @if($plannedPeriod)
                                                                    <div class="tooltip-divider"></div>
                                                                    <div class="tooltip-date-label">📅 Planned Period:</div>
                                                                    <div class="tooltip-date-value">{{ $plannedPeriod }}</div>
                                                                @endif

                                                                @if($actualPeriod)
                                                                    <div class="tooltip-date-label">✅ Actual Period:</div>
                                                                    <div class="tooltip-date-value">{{ $actualPeriod }}</div>
                                                                @endif

                                                                @if($dayCounterText)
                                                                    <div class="tooltip-divider"></div>
                                                                    <div class="tooltip-day-counter {{ $dayCounterClass }}">
                                                                        ⏰ {{ $dayCounterText }}
                                                                    </div>
                                                                @endif

                                                                <div class="tooltip-divider"></div>
                                                                <div class="tooltip-progress">Percentage: {{ $task['percentage'] ?? 0 }}%</div>

                                                                @if(!empty($task['remarks']))
                                                                    <div class="tooltip-divider"></div>
                                                                    <div class="tooltip-date-label">💬 Remarks:</div>
                                                                    <div class="tooltip-date-value" style="white-space: normal; max-width: 200px;">{{ $task['remarks'] }}</div>
                                                                @endif
                                                            </div>

                                                            @if($isCompleted)
                                                                <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            @elseif($isInProgress)
                                                                <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            @else
                                                                <div class="timeline-dot"></div>
                                                            @endif
                                                        </div>

                                                        <div class="timeline-info">
                                                            <div class="timeline-percentage {{ $visualStatus }}">{{ $task['percentage'] ?? 0 }}%</div>
                                                            <div class="timeline-task-name">{{ $task['task_name'] ?? 'N/A' }}</div>
                                                            <div class="timeline-status {{ $visualStatus }}">
                                                                {{ str_replace('_', ' ', $visualStatus) }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if($index < count($moduleData['tasks']) - 1)
                                                        @php
                                                            $nextTask = $moduleData['tasks'][$index + 1];
                                                            $nextTaskActualEndDate = $nextTask['actual_end_date'] ?? null;
                                                            $lineCompleted = $isCompleted && $nextTaskActualEndDate !== null;
                                                        @endphp
                                                        <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}"></div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @empty
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="empty-state-title">No projects found</p>
                            <p class="empty-state-description">No projects available for this {{ $categoryMode === 'salesperson' ? 'salesperson' : 'implementer' }}.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <div id="tooltip-container"></div>
    </div>
</x-filament-panels::page>
