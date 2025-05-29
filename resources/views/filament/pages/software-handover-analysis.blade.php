<x-filament::page>
    <div class="mb-4">
        <x-filament::input.wrapper>
            <x-filament::input
                type="month"
                wire:model.live="selectedMonth"
                placeholder="Select Month"
            />
        </x-filament::input.wrapper>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
        <!-- COUNT BY CATEGORIES -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-yellow-200">
                COUNT BY CATEGORIES
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openCategorySlideOver('small')"
                                    class="text-blue-600 hover:underline">SMALL</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $categoryCounts['small'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openCategorySlideOver('medium')"
                                    class="text-blue-600 hover:underline">MEDIUM</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $categoryCounts['medium'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openCategorySlideOver('large')"
                                    class="text-blue-600 hover:underline">LARGE</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $categoryCounts['large'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openCategorySlideOver('enterprise')"
                                    class="text-blue-600 hover:underline">ENTERPRISE</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $categoryCounts['enterprise'] }}</td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $categoryCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COUNT BY MODULES -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-blue-200">
                COUNT BY MODULES
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openModuleSlideOver('ta')"
                                    class="text-blue-600 hover:underline">TA COUNT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $modulesCounts['ta_count'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openModuleSlideOver('tl')"
                                    class="text-blue-600 hover:underline">TL COUNT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $modulesCounts['tl_count'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openModuleSlideOver('tc')"
                                    class="text-blue-600 hover:underline">TC COUNT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $modulesCounts['tc_count'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openModuleSlideOver('tp')"
                                    class="text-blue-600 hover:underline">TP COUNT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $modulesCounts['tp_count'] }}</td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $modulesCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COUNT BY SALES -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-red-200">
                COUNT BY SALES
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['muim', 'jia_jun', 'yasmin', 'edward', 'sulaiman', 'natt',
                                'tamy', 'faza', 'tina', 'jonathan', 'wirson', 'fatimah',
                                'farhanah', 'joshua', 'aziz', 'bari', 'vince'] as $person)
                            <tr>
                                <td class="px-2 py-1 border">
                                    <button
                                        wire:click="openSalesSlideOver('{{ $person }}')"
                                        class="text-blue-600 uppercase hover:underline">
                                        {{ in_array($person, ['jia_jun', 'edward', 'sulaiman', 'natt', 'tamy', 'faza', 'tina']) ? '(R) ' : '' }}
                                        {{ str_replace('_', ' ', $person) }}
                                    </button>
                                </td>
                                <td class="px-2 py-1 text-center border">{{ $salesCounts[$person] ?? 0 }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $salesCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COUNT BY IMPLEMENTER -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-yellow-200">
                COUNT BY IMPLEMENTER
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['amirul', 'bari', 'zulhilmie', 'adzzim', 'azrul',
                                'najwa', 'syazana', 'hanif', 'aiman', 'hanis', 'john',
                                'alif_faisal', 'shaoinur', 'syamim', 'siew_ling'] as $person)
                            <tr class="{{ in_array($person, ['amirul', 'najwa', 'syazana']) ? 'bg-blue-100' :
                                       (in_array($person, ['bari', 'adzzim']) ? 'bg-gray-200' : '') }}">
                                <td class="px-2 py-1 border">
                                    <button
                                        wire:click="openImplementerSlideOver('{{ $person }}')"
                                        class="text-blue-600 uppercase hover:underline">
                                        {{ str_replace('_', ' ', $person) }}
                                    </button>
                                </td>
                                <td class="px-2 py-1 text-center border">{{ $implementerCounts[$person] ?? 0 }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $implementerCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- STATUS -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-orange-200">
                STATUS
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">CLOSED</th>
                            <th class="px-2 py-1 border">ONGOING</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 text-center border">{{ $statusOngoingCounts['closed'] }}</td>
                            <td class="px-2 py-1 text-center border">{{ $statusOngoingCounts['ongoing'] }}</td>
                        </tr>
                        <!-- More rows if needed -->
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-2 mt-4 font-bold text-center text-white bg-blue-500">
                STATUS - ONGOING
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">OPEN</th>
                            <th class="px-2 py-1 border">DELAY</th>
                            <th class="px-2 py-1 border">INACTIVE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['open'] }}</td>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['delay'] }}</td>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['inactive'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- <!-- Second row -->
    <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
        <!-- COUNT BY STATUS -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-green-200">
                COUNT BY STATUS
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openStatusSlideOver('open')"
                                    class="text-blue-600 uppercase hover:underline">OPEN</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['open'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openStatusSlideOver('closed')"
                                    class="text-blue-600 uppercase hover:underline">CLOSED</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['closed'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openStatusSlideOver('delay')"
                                    class="text-blue-600 uppercase hover:underline">DELAY</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['delay'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openStatusSlideOver('inactive')"
                                    class="text-blue-600 uppercase hover:underline">INACTIVE</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $statusCounts['inactive'] }}</td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $statusCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PENDING PAYMENT -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center text-white bg-orange-500">
                PENDING PAYMENT
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openPaymentSlideOver('full_payment')"
                                    class="text-blue-600 uppercase hover:underline">FULL PAYMENT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $paymentCounts['full_payment'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openPaymentSlideOver('partial_payment')"
                                    class="text-blue-600 uppercase hover:underline">PARTIAL PAYMENT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $paymentCounts['partial_payment'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openPaymentSlideOver('unpaid')"
                                    class="text-blue-600 uppercase hover:underline">UNPAID</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $paymentCounts['unpaid'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openPaymentSlideOver('hrdf_payment')"
                                    class="text-blue-600 uppercase hover:underline">HRDF PAYMENT</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $paymentCounts['hrdf_payment'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openPaymentSlideOver('bad_debtor')"
                                    class="text-blue-600 uppercase hover:underline">BAD DEBTOR</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $paymentCounts['bad_debtor'] }}</td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $paymentCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Third row -->
    <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-1">
        <!-- PENDING ADMIN TASK -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="px-4 py-2 font-bold text-center bg-pink-200">
                PENDING ADMIN TASK
            </div>
            <div class="p-2">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-1 border">FROM</th>
                            <th class="px-2 py-1 border">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 border">
                                <button
                                    wire:click="openAdminTaskSlideOver('kick_off_meeting')"
                                    class="text-blue-600 uppercase hover:underline">KICK OFF MEETING</button>
                            </td>
                            <td class="px-2 py-1 text-center border">{{ $adminTaskCounts['kick_off_meeting'] }}</td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td class="px-2 py-1 font-bold border">TOTAL</td>
                            <td class="px-2 py-1 font-bold text-center text-red-500 border">{{ $adminTaskCounts['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div> --}}

    <!-- Slide over for details -->
    <x-filament::modal
        width="4xl"
        wire:model.live="showSlideOver"
    >
        <x-slot name="heading">
            {{ $slideOverTitle }}
        </x-slot>

        <div class="overflow-y-auto max-h-96">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border">Company Name</th>
                        <th class="px-4 py-2 border">Salesperson</th>
                        <th class="px-4 py-2 border">Implementer</th>
                        <th class="px-4 py-2 border">Status</th>
                        <th class="px-4 py-2 border">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($handoverList as $handover)
                        <tr>
                            <td class="px-4 py-2 border">
                                {{ $handover->lead->companyDetail->company_name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border">
                                {{ \App\Models\User::find($handover->salesperson_id)->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border">
                                {{ $handover->implementer ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border">
                                {{ $handover->status ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 border">
                                {{ $handover->created_at?->format('d/m/Y') ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center border">No records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::modal>
</x-filament::page>
