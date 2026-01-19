<div x-data="{
        showNotification: false,
        notificationMessage: '',
        notificationType: 'success'
    }"
    @notify.window="
        showNotification = true;
        notificationMessage = $event.detail.message || $event.detail[0]?.message || 'Success';
        notificationType = $event.detail.type || $event.detail[0]?.type || 'success';
        setTimeout(() => showNotification = false, 3000);
    ">
    <!-- Trigger Button -->
    <button
        wire:click="openModal"
        class="w-full px-4 py-3.5 text-sm font-medium text-white transition-all duration-300 bg-green-500 rounded-[10px] shadow-lg hover:bg-green-600 hover:shadow-xl">
        Request Quotation
    </button>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-4xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle" style="position: relative; z-index: 10000; margin-top: 7rem;">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                Request Quotation
                            </h3>
                            <button
                                wire:click="closeModal"
                                class="text-white transition-colors hover:text-gray-200">
                                <i class="text-2xl fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-3 space-y-5">
                        <!-- Status Toggle -->
                        <div class="flex gap-3 mb-2">
                            <button
                                wire:click="$set('subscriberStatus', 'active')"
                                class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $subscriberStatus === 'active' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                <i class="mr-2 fas fa-check-circle"></i>Active
                            </button>
                            <button
                                wire:click="$set('subscriberStatus', 'inactive')"
                                class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $subscriberStatus === 'inactive' ? 'bg-red-500 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                <i class="mr-2 fas fa-times-circle"></i>InActive
                            </button>
                        </div>

                        <!-- Subscriber Search -->
                        <div class="relative">
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Select Subscriber <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <div class="relative">
                                    <input
                                        type="text"
                                        wire:model.live="search"
                                        placeholder="Search subscriber name"
                                        value="{{ $selectedSubscriber ? $selectedSubscriber['company_name'] : '' }}"
                                        class="w-full px-4 py-2 pr-20 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                        {{ $selectedSubscriber ? 'readonly' : '' }}>

                                    @if($selectedSubscriber)
                                        <button
                                            type="button"
                                            wire:click="clearSubscriber"
                                            class="absolute inset-y-0 flex items-center pr-3 text-gray-400 transition-colors right-10 hover:text-red-500">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif

                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="text-gray-400 fas fa-chevron-down"></i>
                                    </div>
                                </div>

                                @if(!$selectedSubscriber && $subscribers->count() > 0)
                                    <div class="absolute z-10 w-full mt-1 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-lg max-h-60">
                                        @foreach($subscribers as $subscriber)
                                            <button
                                                type="button"
                                                wire:click="selectSubscriber('{{ $subscriber->f_id }}', '{{ $subscriber->f_company_name }}')"
                                                class="flex items-center justify-between w-full px-4 py-2 text-left transition-colors border-b border-gray-100 hover:bg-indigo-50 last:border-b-0">
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-800" style="text-transform: uppercase;">{{ $subscriber->f_company_name }}</div>
                                                    <div class="text-xs text-gray-500">ID: {{ $subscriber->f_id }}</div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif(!$selectedSubscriber && strlen($search) > 0 && $subscribers->count() == 0)
                                    <div class="absolute z-10 w-full p-3 mt-1 text-center text-gray-500 bg-white border border-gray-300 rounded-lg shadow-lg">
                                        <i class="mr-2 fas fa-search"></i>No subscribers found
                                    </div>
                                @endif
                            </div>
                            @error('selectedSubscriber')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Product Quantities Grid -->
                        <div>
                            <div class="grid grid-cols-4 gap-4">
                                <!-- Attendance -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Attendance
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="attendance"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                        placeholder="0">
                                    @error('attendance')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Leave -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Leave
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="leave"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                        placeholder="0">
                                    @error('leave')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Claim -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Claim
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="claim"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200"
                                        placeholder="0">
                                    @error('claim')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Payroll -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Payroll
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="payroll"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                        placeholder="0">
                                    @error('payroll')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            @if($headcountError)
                                <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        {{ $headcountError }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Reseller Remark -->
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Reseller Remark
                            </label>
                            <textarea
                                wire:model="resellerRemark"
                                rows="4"
                                maxlength="1000"
                                style="text-transform: uppercase;"
                                class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg resize-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                ></textarea>
                            @error('resellerRemark')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-3 bg-gray-50">
                        <button
                            wire:click="closeModal"
                            type="button"
                            class="px-6 py-2.5 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition-all">
                            <i class="mr-2 fas fa-times"></i>Cancel
                        </button>
                        <button
                            wire:click="submitRequest"
                            type="button"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="mr-2 fas fa-paper-plane"></i>Submit Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Success/Notification Message -->
    <div
        x-show="showNotification"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="z-index: 99999; position: fixed; top: 120px; right: 20px;"
        :class="{
            'bg-green-500': notificationType === 'success',
            'bg-red-500': notificationType === 'error',
            'bg-blue-500': notificationType === 'info'
        }"
        class="px-6 py-4 text-white rounded-lg shadow-2xl">
        <div class="flex items-center gap-2">
            <i class="fas" :class="{
                'fa-check-circle': notificationType === 'success',
                'fa-exclamation-circle': notificationType === 'error',
                'fa-info-circle': notificationType === 'info'
            }"></i>
            <span x-text="notificationMessage"></span>
        </div>
    </div>
</div>
