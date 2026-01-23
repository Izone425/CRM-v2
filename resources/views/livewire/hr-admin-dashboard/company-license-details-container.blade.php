<div>
    <style>
        .company-tab-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            border: 2px solid;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background: none;
            outline: none;
            position: relative;
            overflow: hidden;
        }

        .company-tab-button:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .company-tab-button.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-color: #2563eb;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4), 0 4px 6px -2px rgba(59, 130, 246, 0.05);
        }

        .company-tab-button.active:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.5), 0 10px 10px -5px rgba(59, 130, 246, 0.1);
        }

        .company-tab-button.inactive {
            background: white;
            border-color: #d1d5db;
            color: #374151;
        }

        .company-tab-button.inactive:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }

        .company-tab-container {
            display: flex;
            justify-content: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .company-content-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: visible;
            border: 1px solid #e5e7eb;
            animation: fadeIn 0.3s ease-in-out;
        }

        .company-tab-icon {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            flex-shrink: 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .company-tab-container {
                flex-direction: column;
                gap: 8px;
            }

            .company-tab-button {
                padding: 10px 16px;
                font-size: 13px;
                justify-content: center;
            }
        }

        .company-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 20px 24px;
            margin-bottom: 24px;
            border-radius: 12px;
        }

        .company-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .company-header-meta {
            display: flex;
            gap: 24px;
            margin-top: 8px;
            font-size: 14px;
            opacity: 0.9;
        }

        .company-header-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>

    {{-- Company Header --}}
    <div class="company-header">
        <h2>{{ $companyData['company_name'] ?? 'Unknown Company' }}</h2>
        <div class="company-header-meta">
            @if($companyData['handover_id'])
                <span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    {{ $companyData['handover_id'] }}
                </span>
            @endif
            @if($companyData['hr_company_id'])
                <span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Backend ID: {{ $companyData['hr_company_id'] }}
                </span>
            @endif
        </div>
    </div>

    {{-- Tab Buttons --}}
    <div class="company-tab-container">
        <button
            wire:click="switchToTab('profile')"
            class="company-tab-button {{ $activeTab === 'profile' ? 'active' : 'inactive' }}"
        >
            <svg class="company-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Profile
        </button>

        <button
            wire:click="switchToTab('users')"
            class="company-tab-button {{ $activeTab === 'users' ? 'active' : 'inactive' }}"
        >
            <svg class="company-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            Users
        </button>

        <button
            wire:click="switchToTab('products')"
            class="company-tab-button {{ $activeTab === 'products' ? 'active' : 'inactive' }}"
        >
            <svg class="company-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Products
        </button>

        <button
            wire:click="switchToTab('invoice')"
            class="company-tab-button {{ $activeTab === 'invoice' ? 'active' : 'inactive' }}"
        >
            <svg class="company-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Invoice
        </button>

        <button
            wire:click="switchToTab('account_setting')"
            class="company-tab-button {{ $activeTab === 'account_setting' ? 'active' : 'inactive' }}"
        >
            <svg class="company-tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Account Setting
        </button>
    </div>

    {{-- Tab Content --}}
    <div class="company-content-container">
        @if($activeTab === 'users')
            <livewire:hr-admin-dashboard.company-users-tab
                :software-handover-id="$softwareHandoverId"
                :company-data="$companyData"
                wire:key="users-tab-{{ $softwareHandoverId }}"
            />
        @elseif($activeTab === 'profile')
            <livewire:hr-admin-dashboard.company-profile-tab
                :software-handover-id="$softwareHandoverId"
                :company-data="$companyData"
                wire:key="profile-tab-{{ $softwareHandoverId }}"
            />
        @elseif($activeTab === 'products')
            <livewire:hr-admin-dashboard.company-products-tab
                :software-handover-id="$softwareHandoverId"
                :company-data="$companyData"
                wire:key="products-tab-{{ $softwareHandoverId }}"
            />
        @elseif($activeTab === 'invoice')
            <livewire:hr-admin-dashboard.company-invoice-tab
                :software-handover-id="$softwareHandoverId"
                :company-data="$companyData"
                wire:key="invoice-tab-{{ $softwareHandoverId }}"
            />
        @elseif($activeTab === 'account_setting')
            <livewire:hr-admin-dashboard.company-account-setting-tab
                :software-handover-id="$softwareHandoverId"
                :company-data="$companyData"
                wire:key="account-setting-tab-{{ $softwareHandoverId }}"
            />
        @endif
    </div>
</div>
