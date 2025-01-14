<div>
    <h2 class="text-lg font-bold">
        {{ $this->companyName }} &nbsp;&nbsp;&nbsp;
        <span style="
            @if($this->lead_status == 'Hot')
                background-color: #f60808;
            @elseif($this->lead_status == 'Warm')
                background-color: #FFA500;
            @elseif($this->lead_status == 'Cold')
                background-color: #00ff3e;
            @elseif($this->categories == 'New')
                background-color: #FFA500;
            @elseif($this->categories == 'Active')
                background-color: #00ff3e;
            @elseif($this->categories == 'Inactive')
                background-color: #E5E4E2;
            @else
                background-color: #00ff3e;
            @endif
            border-radius: 200px
        "
        class="text-white font-semibold py-1 px-2 rounded"
        >
            {{ $this->lead_status }}
        </span>
    </h2>
</div>
