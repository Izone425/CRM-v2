@php
    // Get the current lead ID from the form context
    $lead = null;

    // Check if we're in a Filament form context
    if (isset($getRecord) && method_exists($getRecord, 'subsidiaries')) {
        // Direct model record from Filament
        $lead = $getRecord;
    } elseif (isset($getRecord) && is_numeric($getRecord)) {
        // We have an ID instead of a model
        $lead = \App\Models\Lead::find($getRecord);
    } elseif (isset($record) && method_exists($record, 'subsidiaries')) {
        // Alternative Filament naming
        $lead = $record;
    } else {
        // Try to get from URL param if not in livewire component
        $urlSegments = request()->segments();
        $leadId = end($urlSegments);
        if (is_numeric($leadId)) {
            $lead = \App\Models\Lead::find($leadId);
        } elseif (is_string($leadId) && strlen($leadId) > 5) {
            // It might be an encrypted ID
            try {
                $decryptedId = \App\Classes\Encryptor::decrypt($leadId);
                if (is_numeric($decryptedId)) {
                    $lead = \App\Models\Lead::find($decryptedId);
                }
            } catch (\Exception $e) {
                // Decryption failed, just continue
            }
        }
    }

    // Safety check before trying to call subsidiaries()
    $subsidiaries = collect([]);
    if ($lead instanceof \App\Models\Lead) {
        $subsidiaries = $lead->subsidiaries()->orderBy('created_at', 'desc')->get();
    }
@endphp

<div class="overflow-hidden bg-white rounded-lg shadow-sm">
    @if($subsidiaries->isEmpty())
        <div class="p-6 text-center text-gray-500">
            <div class="text-lg font-semibold">No subsidiaries found</div>
            <p class="mt-1">Use the "Add Subsidiary" button to add a new subsidiary company.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Company Name</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Reg. Number</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Contact Name</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Contact Number</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Email</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">State</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Industry</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Added On</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($subsidiaries as $subsidiary)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $subsidiary->company_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->register_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->contact_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->state }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->industry }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $subsidiary->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
