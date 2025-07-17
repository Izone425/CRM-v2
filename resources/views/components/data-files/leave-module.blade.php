<style>
    .module-container {
        padding: 1.5rem;
        margin-bottom: 1rem;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .module-title {
        margin-bottom: 0.75rem;
        font-size: 1.125rem;
        font-weight: 500;
        color: #1a202c;
    }

    .section-container {
        margin-bottom: 1.5rem;
    }

    .section-title {
        margin-bottom: 0.75rem;
        font-weight: 500;
        color: #38a169; /* Green for leave module */
        font-size: 0.875rem;
    }

    .files-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    @media (min-width: 768px) {
        .files-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .file-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.25rem;
        background-color: #f9fafb;
    }

    .file-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.875rem;
    }

    .file-actions {
        display: flex;
        gap: 0.5rem;
    }

    .action-view {
        color: #3182ce;
        transition: color 0.15s ease-in-out;
    }

    .action-view:hover {
        color: #2c5282;
    }

    .action-download {
        color: #38a169;
        transition: color 0.15s ease-in-out;
    }

    .action-download:hover {
        color: #276749;
    }

    .no-files {
        font-style: italic;
        color: #718096;
        font-size: 0.875rem;
    }
</style>

@php
    $lead = $this->record; // Accessing Filament's record

    $files = [];
    if ($lead && isset($lead->id)) {
        $files = \App\Models\DataFile::where('lead_id', $lead->id)
            ->where('category', 'leave_module')
            ->get();
    }

    $leaveBalanceFiles = collect($files)->where('subcategory', 'user_leave_balance');
    $leaveTakenFiles = collect($files)->where('subcategory', 'user_leave_taken');
    $leavePolicyFiles = collect($files)->where('subcategory', 'leave_policy_template');
@endphp

<div class="section-container">
    <h4 class="section-title">USER LEAVE BALANCE</h4>
    @if($leaveBalanceFiles->count() > 0)
        <div class="files-grid">
            @foreach($leaveBalanceFiles as $file)
                <div class="file-item">
                    <div class="file-name">
                        @php
                            $originalName = basename($file->filename);
                            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                            $shortName = "Leave Balance " . $loop->iteration;
                        @endphp
                        <div class="font-medium">{{ $shortName }}</div>
                        <div class="text-xs text-gray-500">
                            {{ strtoupper($extension) }} · {{ date('M d, Y', strtotime($file->created_at)) }}
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="{{ Storage::url($file->filename) }}" target="_blank" class="action-view">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="{{ Storage::url($file->filename) }}" download class="action-download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="no-files">No files uploaded yet</p>
    @endif
</div>

<div class="section-container">
    <h4 class="section-title">USER LEAVE TAKEN</h4>
    @if($leaveTakenFiles->count() > 0)
        <div class="files-grid">
            @foreach($leaveTakenFiles as $file)
                <div class="file-item">
                    <div class="file-name">
                        @php
                            $originalName = basename($file->filename);
                            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                            $shortName = "Leave Taken " . $loop->iteration;
                        @endphp
                        <div class="font-medium">{{ $shortName }}</div>
                        <div class="text-xs text-gray-500">
                            {{ strtoupper($extension) }} · {{ date('M d, Y', strtotime($file->created_at)) }}
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="{{ Storage::url($file->filename) }}" target="_blank" class="action-view">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="{{ Storage::url($file->filename) }}" download class="action-download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="no-files">No files uploaded yet</p>
    @endif
</div>

<div class="section-container">
    <h4 class="section-title">LEAVE POLICY TEMPLATE</h4>
    @if($leavePolicyFiles->count() > 0)
        <div class="files-grid">
            @foreach($leavePolicyFiles as $file)
                <div class="file-item">
                    <div class="file-name">
                        @php
                            $originalName = basename($file->filename);
                            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                            $shortName = "Leave Policy " . $loop->iteration;
                        @endphp
                        <div class="font-medium">{{ $shortName }}</div>
                        <div class="text-xs text-gray-500">
                            {{ strtoupper($extension) }} · {{ date('M d, Y', strtotime($file->created_at)) }}
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="{{ Storage::url($file->filename) }}" target="_blank" class="action-view">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="{{ Storage::url($file->filename) }}" download class="action-download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="no-files">No files uploaded yet</p>
    @endif
</div>
