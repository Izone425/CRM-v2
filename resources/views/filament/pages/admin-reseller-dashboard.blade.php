<x-filament-panels::page>
    <style>
        /* Container styling */
        .reseller-container {
            grid-column: 1 / -1;
            width: 100%;
        }

        /* Main layout with grid setup */
        .dashboard-layout {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 15px;
        }

        /* Group column styling */
        .group-column {
            padding-right: 10px;
            width: 230px;
        }

        .group-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px 15px;
            cursor: pointer;
            transition: all 0.2s;
            border-top: 4px solid transparent;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
            max-height: 82px;
            max-width: 220px;
        }

        .group-box:hover {
            background-color: #f9fafb;
            transform: translateX(3px);
        }

        .group-box.selected {
            background-color: #f9fafb;
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .group-info {
            display: flex;
            flex-direction: column;
        }

        .group-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
            text-align: left;
        }

        .group-count {
            font-size: 24px;
            font-weight: bold;
        }

        /* GROUP COLORS */
        .group-reseller { border-top-color: #3b82f6; }
        .group-reseller .group-count { color: #3b82f6; }

        /* Group container layout */
        .group-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            border-right: none;
            padding-right: 0;
            padding-bottom: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Category container */
        .category-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            border-right: 1px solid #e5e7eb;
            padding-right: 10px;
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Stat box styling */
        .stat-box {
            background-color: white;
            width: 100%;
            min-height: 65px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            margin-bottom: 8px;
        }

        .stat-box:hover {
            background-color: #f9fafb;
            transform: translateX(3px);
        }

        .stat-box.selected {
            background-color: #f9fafb;
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
        }

        .stat-count {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.2;
        }

        /* Content area */
        .content-column {
            min-height: 600px;
        }

        .content-area {
            min-height: 600px;
        }

        .content-area .fi-ta {
            margin-top: 0;
        }

        .content-area .fi-ta-content {
            padding: 0.75rem !important;
        }

        /* Hint message */
        .hint-message {
            text-align: center;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            border: 1px dashed #d1d5db;
            height: 530px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hint-message h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .hint-message p {
            color: #6b7280;
        }

        /* STAT BOX COLOR CODING */
        .reseller-all { border-left: 4px solid #3b82f6; }
        .reseller-all .stat-count { color: #3b82f6; }

        .reseller-new { border-left: 4px solid #10b981; }
        .reseller-new .stat-count { color: #10b981; }

        .reseller-pending-invoice { border-left: 4px solid #f59e0b; }
        .reseller-pending-invoice .stat-count { color: #f59e0b; }

        .reseller-pending-license { border-left: 4px solid #8b5cf6; }
        .reseller-pending-license .stat-count { color: #8b5cf6; }

        .reseller-completed { border-left: 4px solid #06b6d4; }
        .reseller-completed .stat-count { color: #06b6d4; }

        /* Selected states */
        .stat-box.selected.reseller-all { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }
        .stat-box.selected.reseller-new { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
        .stat-box.selected.reseller-pending-invoice { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
        .stat-box.selected.reseller-pending-license { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
        .stat-box.selected.reseller-completed { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .dashboard-layout {
                grid-template-columns: 100%;
                grid-template-rows: auto auto;
            }

            .group-container {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 15px;
                margin-bottom: 15px;
            }

            .category-container {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 15px;
                margin-bottom: 15px;
                max-height: none;
            }

            .stat-box:hover, .stat-box.selected {
                transform: translateY(-3px);
            }
        }

        @media (max-width: 640px) {
            .group-container,
            .category-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $allCount = \App\Models\ResellerHandover::count();
        $newCount = \App\Models\ResellerHandover::where('status', 'new')->count();
        $pendingInvoiceCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_invoice')->count();
        $pendingLicenseCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_license')->count();
        $completedCount = \App\Models\ResellerHandover::where('status', 'completed')->count();
    @endphp

    <div class="reseller-container"
        x-data="{
            selectedGroup: 'reseller-handover',
            selectedStat: null,
            allCount: {{ $allCount }},
            newCount: {{ $newCount }},
            pendingInvoiceCount: {{ $pendingInvoiceCount }},
            pendingLicenseCount: {{ $pendingLicenseCount }},
            completedCount: {{ $completedCount }},

            setSelectedGroup(value) {
                if (this.selectedGroup === value) {
                    this.selectedGroup = null;
                    this.selectedStat = null;
                } else {
                    this.selectedGroup = value;
                    this.selectedStat = 'reseller-all';
                }
            },

            setSelectedStat(value) {
                if (this.selectedStat === value) {
                    this.selectedStat = null;
                } else {
                    this.selectedStat = value;
                }
            }
        }"
        @refresh-leadowner-tables.window="
            fetch('{{ route('admin.reseller-handover.counts') }}')
                .then(response => response.json())
                .then(data => {
                    allCount = data.new + data.pending_timetec_invoice + data.pending_timetec_license + data.completed;
                    newCount = data.new;
                    pendingInvoiceCount = data.pending_timetec_invoice;
                    pendingLicenseCount = data.pending_timetec_license;
                    completedCount = data.completed;
                })
                .catch(error => console.error('Error fetching counts:', error));
        ">

        <div class="dashboard-layout" wire:poll.300s>
            <!-- Group Column -->
            <div class="group-column">
                <div class="group-container">
                    <div class="group-box group-reseller"
                            :class="{'selected': selectedGroup === 'reseller-handover'}"
                            @click="setSelectedGroup('reseller-handover')">
                        <div class="group-title">Reseller Handover</div>
                        <div class="group-count" x-text="allCount"></div>
                    </div>
                </div>
            </div>

            <!-- Content Column -->
            <div class="content-column">
                <!-- Category Container -->
                <div class="category-container" x-show="selectedGroup === 'reseller-handover'">
                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'reseller-all'}"
                            @click="setSelectedStat('reseller-all')">
                        <div class="stat-info">
                            <div class="stat-count" x-text="allCount"></div>
                            <div class="stat-label">All Handovers</div>
                        </div>
                    </div>

                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'reseller-new'}"
                            @click="setSelectedStat('reseller-new')">
                        <div class="stat-info">
                            <div class="stat-count" x-text="newCount"></div>
                            <div class="stat-label">New</div>
                        </div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'reseller-pending-invoice'}"
                            @click="setSelectedStat('reseller-pending-invoice')">
                        <div class="stat-info">
                            <div class="stat-count" x-text="pendingInvoiceCount"></div>
                            <div class="stat-label">Pending TimeTec Invoice</div>
                        </div>
                    </div>

                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'reseller-pending-license'}"
                            @click="setSelectedStat('reseller-pending-license')">
                        <div class="stat-info">
                            <div class="stat-count" x-text="pendingLicenseCount"></div>
                            <div class="stat-label">Pending TimeTec License</div>
                        </div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'reseller-completed'}"
                            @click="setSelectedStat('reseller-completed')">
                        <div class="stat-info">
                            <div class="stat-count" x-text="completedCount"></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="content-area">
                    <!-- Hint message when nothing is selected -->
                    <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                        <h3>Select a Status</h3>
                        <p>Click on a status box above to view the handovers</p>
                    </div>

                    <!-- All Handovers -->
                    <div x-show="selectedStat === 'reseller-all'" x-transition>
                        <livewire:admin-reseller-handover-all />
                    </div>

                    <!-- New -->
                    <div x-show="selectedStat === 'reseller-new'" x-transition>
                        <livewire:reseller-handover-new />
                    </div>

                    <!-- Pending Invoice -->
                    <div x-show="selectedStat === 'reseller-pending-invoice'" x-transition>
                        <livewire:reseller-handover-pending-timetec-invoice />
                    </div>

                    <!-- Pending License -->
                    <div x-show="selectedStat === 'reseller-pending-license'" x-transition>
                        <livewire:reseller-handover-pending-timetec-license />
                    </div>

                    <!-- Completed -->
                    <div x-show="selectedStat === 'reseller-completed'" x-transition>
                        <livewire:admin-reseller-handover-completed />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-leadowner-tables', () => {
                console.log('Refresh event received');
            });
        });
    </script>
</x-filament-panels::page>
