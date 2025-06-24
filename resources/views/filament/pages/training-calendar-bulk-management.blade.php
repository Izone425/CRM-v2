<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/training-calendar-bulk-management.blade.php -->
<x-filament::page>
    <div class="space-y-6">
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-xl font-bold">Bulk Update Training Dates</h2>

            <form wire:submit.prevent="saveBulkSettings" class="space-y-4">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input wire:model="startDate" type="date" id="startDate" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('startDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input wire:model="endDate" type="date" id="endDate" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('endDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="status" id="status" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="open">Open for Training</option>
                            <option value="closed">Closed</option>
                        </select>
                        @error('status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity</label>
                        <input wire:model="capacity" type="number" id="capacity" min="1" max="100" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('capacity') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Select Days of Week</label>
                    <div class="grid grid-cols-4 gap-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="0" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Sunday</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="1" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Monday</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="2" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Tuesday</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="3" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Wednesday</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="4" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Thursday</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="5" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Friday</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedDays" value="6" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2">Saturday</span>
                        </label>
                    </div>
                    @error('selectedDays') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition bg-blue-600 border border-transparent rounded-md hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-300 disabled:opacity-25">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-filament::page>
