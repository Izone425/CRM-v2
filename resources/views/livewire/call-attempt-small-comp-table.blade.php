<div class="p-4 bg-white rounded-lg shadow-lg" style="height: 480px;">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold">Call Attempt (1-24)</h3>
        <span class="text-lg font-bold text-gray-500">(Count: {{ $this->getFollowUpSmallCompanyLeads()->count() }})</span>
    </div>
    <br>
    {{ $this->table }}
</div>
