<div class="p-4 bg-white rounded-lg shadow-lg">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Active (25 Above)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getActiveBigCompanyLeads()->count() }})</span>
    </div>
    <br>
    {{ $this->table }}
</div>
