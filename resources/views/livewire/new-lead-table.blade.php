<div class="p-4 bg-white rounded-lg shadow-lg" style="min-height: 450px; height: auto;">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">New Leads</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getPendingLeadsQuery()->count() }})</span>
    </div>
    <br>
    {{ $this->table }}
</div>
