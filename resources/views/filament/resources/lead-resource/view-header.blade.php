<h1 class="text-lg font-bold">
    {{ $record->companyDetail->company_name ?? 'Lead Details' }}
    <span
        style="
            @if($record->lead_status === 'Hot')
                background-color: #f60808;
            @elseif($record->lead_status === 'Warm')
                background-color: #FFA500;
            @elseif($record->lead_status === 'Cold')
                background-color: #00ff3e;
            @elseif($record->categories === 'New')
                background-color: #FFA500;
            @elseif($record->categories === 'Active')
                background-color: #00ff3e;
            @elseif($record->categories === 'Inactive')
                background-color: #E5E4E2;
            @else
                background-color: #00ff3e;
            @endif
            border-radius: 200px;
            color: white;
            padding: 4px 8px;
            font-weight: bold;
        "
    >
        {{ $record->lead_status }}
    </span>
</h1>
