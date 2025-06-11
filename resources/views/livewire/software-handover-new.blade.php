<div class="p-4 bg-white rounded-lg shadow-lg" style="height: auto;">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">
            @if(auth()->user()->role_id === 2)
                Software Handover - Pending
            @elseif(auth()->user()->role_id === 3)
                @if($selectedUser === 'all-salespersons')
                    Software Handover - Pending
                @elseif(is_numeric($selectedUser))
                    Software Handover - Pending
                @else
                    New Task / Accepted
                @endif
            @else
                New Task / Accepted
            @endif
        </h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getTableRecords()->total() }})</span>
    </div>
    <br>
    {{ $this->table }}
    @if ($this->getTableRecords()->total() > 0 && $this->getTableRecords()->lastPage() > 1)
        <div class="mt-4 text-sm text-center text-gray-600">
            Page {{ $this->getTableRecords()->currentPage() }} of {{ $this->getTableRecords()->lastPage() }}
        </div>
    @endif
</div>
