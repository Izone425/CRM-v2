<div class="text-left">
    <p>COMPANY NAME: {{ $lead->companyDetail->company_name ?? 'N/A' }}</p>
    <p>PIC NAME: {{ $lead->companyDetail->name ?? $lead->companyDetail->company_name }}</p>
    <p>PIC CONTACT NO: {{ $lead->companyDetail->contact_no ?? $lead->phone }}</p>
    <p>PIC EMAIL ADDRESS: {{ $lead->companyDetail->email ?? $lead->email }}</p>
    <p>LEADS CREATED: {{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y g:ia') }}</p>
    <p>COMPANY SIZE: {{ $lead->company_size_label}} </p>
    <p>PENDING DAYS: {{ $pending_days }} days</p> <!-- Display Pending Days -->
</div>
