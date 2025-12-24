{{-- Contact Details Modal Component --}}
<style>
    .contact-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }

    .contact-table th,
    .contact-table td {
        border: 1px solid #d1d5db;
        padding: 0.75rem;
        text-align: left;
        font-size: 0.875rem;
    }

    .contact-table th {
        background-color: #f3f4f6;
        font-weight: 600;
        color: #374151;
    }

    .contact-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .contact-table tbody tr:hover {
        background-color: #f3f4f6;
    }

    .no-contacts {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
        font-style: italic;
    }

    .contact-modal-container {
        padding: 1rem;
        background-color: #fff;
        border-radius: 0.5rem;
    }
</style>

<div class="contact-modal-container">
    @if(count($contactDetails) > 0)
        <table class="contact-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>HP Number</th>
                    <th>Email Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contactDetails as $index => $contact)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $contact['pic_name'] ?? '-' }}</td>
                        <td>{{ $contact['pic_phone'] ?? '-' }}</td>
                        <td>{{ $contact['pic_email'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-contacts">
            <p>No contact details available for this hardware handover.</p>
        </div>
    @endif
</div>
