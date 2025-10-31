{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/resources/lead-resource/tabs/project-plan-tabs.blade.php --}}
<div>
    {{-- Module Selection Header --}}
    <div class="p-4 mb-6 rounded-lg bg-gray-50">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Select Modules</h3>
        <div class="grid grid-cols-5 gap-4">
            @foreach(['general' => 'General', 'attendance' => 'Attendance', 'leave' => 'Leave', 'claim' => 'Claim', 'payroll' => 'Payroll'] as $key => $label)
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="selectedModules"
                        value="{{ $key }}"
                        class="border-gray-300 rounded shadow-sm text-primary-600 focus:border-primary-500 focus:ring-primary-500"
                    >
                    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Progress Overview --}}
    @if(!empty($selectedModules))
        <div class="mb-6">
            {!! $this->getProgressOverview() !!}
        </div>

        {{-- Task Details --}}
        <div class="space-y-6">
            @foreach($selectedModules as $module)
                @if(isset($projectTasks[$module]))
                    <div class="p-6 bg-white border rounded-lg shadow-sm">
                        <h4 class="mb-4 font-semibold text-gray-900 capitalize text-md">
                            {{ str_replace('_', ' ', $module) }} Module Tasks
                        </h4>
                        <div class="space-y-3">
                            @foreach($projectTasks[$module] as $task)
                                <div class="flex items-center justify-between p-3 rounded bg-gray-50">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $task['phase_name'] }}</div>
                                        <div class="text-sm text-gray-600">{{ $task['task_name'] }}</div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm font-medium text-gray-700">{{ $task['percentage'] }}%</div>
                                        <div class="w-20 h-2 bg-gray-200 rounded-full">
                                            <div class="h-2 bg-blue-600 rounded-full" style="width: {{ $task['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="py-12 text-center text-gray-500">
            <p>Please select at least one module to view project tasks.</p>
        </div>
    @endif
</div>
