<div class="p-4 bg-white rounded-lg shadow-lg" style="height: 480px;">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Call Attempt (25 Above)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getFollowUpBigCompanyLeads()->count() }})</span>
    </div>
    <br>
    {{ $this->table }}
</div>
