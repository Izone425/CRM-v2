<!-- filepath: /var/www/html/timeteccrm/resources/views/components/service-forms-list.blade.php -->
<div>
    @php
        $lead = $this->record;
        $implementerForms = $lead->implementerForms ?? collect();
    @endphp

    <style>
        .service-forms-container {
            margin-bottom: 1.5rem;
        }
        .service-form-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
            border: 1px solid #f0f0f0;
        }
        .service-form-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .service-form-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        .service-form-icon {
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }
        .service-form-info {
            flex: 1;
        }
        .service-form-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .service-form-meta {
            display: flex;
            align-items: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .service-form-ext {
            background-color: #4f46e5;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        .service-form-date {
            display: flex;
            align-items: center;
        }
        .service-form-date svg {
            height: 0.875rem;
            width: 0.875rem;
            margin-right: 0.25rem;
        }
        .service-form-actions {
            display: flex;
            gap: 0.5rem;
        }
        .service-form-button {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #374151;
            background-color: white;
            transition: all 0.2s;
            text-decoration: none;
        }
        .service-form-button:hover {
            background-color: #f9fafb;
            color: #111827;
        }
        .service-form-button.primary {
            background-color: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        .service-form-button.primary:hover {
            background-color: #4338ca;
        }
        .service-form-button.secondary {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        .service-form-button.secondary:hover {
            background-color: #e5e7eb;
        }
        .service-form-button svg {
            height: 0.875rem;
            width: 0.875rem;
            margin-right: 0.375rem;
        }
        .service-form-notes {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 0.75rem;
        }
        .service-form-notes-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        .service-form-notes-content {
            font-size: 0.875rem;
            color: #6b7280;
            white-space: pre-line;
            line-height: 1.5;
        }
        .service-form-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #6b7280;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            border: 1px dashed #d1d5db;
        }
        .service-form-empty svg {
            height: 3rem;
            width: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        .service-form-empty-text {
            font-size: 0.875rem;
            text-align: center;
        }
    </style>

    <div class="service-forms-container">
        @if($implementerForms->count())
            @foreach($implementerForms as $form)
                <div class="service-form-card">
                    <div class="service-form-header">
                        <div class="flex items-center">
                            <div class="service-form-icon">
                                @php
                                    $fileName = basename($form->filepath);
                                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                                    $iconColor = match($extension) {
                                        'pdf' => '#f87171',
                                        'doc', 'docx' => '#60a5fa',
                                        'jpg', 'jpeg', 'png', 'gif' => '#34d399',
                                        default => '#8b5cf6'
                                    };
                                @endphp

                                @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                @elseif($extension === 'pdf')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                @endif
                            </div>

                            <div class="service-form-info">
                                <h4 class="service-form-title">Service Form</h4>
                                <div class="service-form-meta">
                                    <span class="service-form-ext">{{ strtoupper($extension) }}</span>
                                    <div class="service-form-date">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $form->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="service-form-actions">
                            <a href="{{ Storage::url($form->filepath) }}" target="_blank" class="service-form-button secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View
                            </a>
                            <a href="{{ Storage::url($form->filepath) }}" download class="service-form-button primary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>

                    @if($form->notes)
                        <div class="service-form-notes">
                            <div class="service-form-notes-title">Notes:</div>
                            <div class="service-form-notes-content">{{ $form->notes }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="service-form-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div class="service-form-empty-text">
                    No service forms have been uploaded yet.
                </div>
            </div>
        @endif
    </div>
</div>
