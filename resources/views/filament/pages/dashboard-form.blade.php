<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @if (auth()->user()->role_id == 1)
            @include('filament.pages.leadowner')

        @elseif (auth()->user()->role_id == 2)
            @include('filament.pages.salesperson')

        @elseif (auth()->user()->role_id == 3)
            <div class="space-y-4">
                <!-- Dropdown for Selecting a User -->
                <div class="flex items-center space-x-8"> <!-- Use space-x-8 for horizontal spacing -->
                    <!-- Dropdown -->
                    <div>
                        <select
                            wire:model.live="selectedUser"
                            id="userFilter"
                            class="mt-1 border-gray-300 rounded-md shadow-sm"
                        >
                        <option value="{{ auth()->id() }}">Your Own Dashboard</option>
                            <optgroup label="Lead Owner">
                                @foreach ($users->where('role_id', 1) as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Salesperson">
                                @foreach ($users->where('role_id', 2) as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp

                    @if ($selectedUser == 1 || $selectedUser == 14 || $selectedUser == null)
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                            <!-- Lead Owner Button -->
                            <button
                                wire:click="toggleDashboard('LeadOwner')"
                                style="
                                    padding: 10px 15px;
                                    font-size: 14px;
                                    font-weight: bold;
                                    border: none;
                                    border-radius: 20px;
                                    background: {{ $currentDashboard === 'LeadOwner' ? '#431fa1' : 'transparent' }};
                                    color: {{ $currentDashboard === 'LeadOwner' ? '#ffffff' : '#555' }};
                                    cursor: pointer;
                                "
                            >
                                Lead Owner
                            </button>

                            <!-- Salesperson Button -->
                            <button
                                wire:click="toggleDashboard('Salesperson')"
                                style="
                                    padding: 10px 15px;
                                    font-size: 14px;
                                    font-weight: bold;
                                    border: none;
                                    border-radius: 20px;
                                    background: {{ $currentDashboard === 'Salesperson' ? '#431fa1' : 'transparent' }};
                                    color: {{ $currentDashboard === 'Salesperson' ? '#ffffff' : '#555' }};
                                    cursor: pointer;
                                "
                            >
                                Salesperson
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <br>
            {{-- <div class="mt-8"> --}}
                @if ($selectedUserRole == 1)

                    @include('filament.pages.leadowner')

                @elseif ($selectedUserRole == 2)

                    @include('filament.pages.salesperson')

                @else
                    @if ($currentDashboard === 'LeadOwner')
                        @include('filament.pages.leadowner')
                    @elseif ($currentDashboard === 'Salesperson')
                        @include('filament.pages.salesperson')
                    @endif
                @endif
            {{-- </div> --}}
        @endif
    </div>
</x-filament::page>
