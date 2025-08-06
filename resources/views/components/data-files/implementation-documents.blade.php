<!-- filepath: /var/www/html/timeteccrm/resources/views/components/data-files/implementation-documents.blade.php -->
<div>
    @php
        $lead = $this->record;
        $implementationDocuments = App\Models\DataFile::where('lead_id', $lead->id)
            ->where('category', 'implementation_documents')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('subcategory');

        // Define the specific order and layout for document categories
        $leftColumnCategories = ['kickoff_meeting_slide', 'uat_form'];
        $rightColumnCategories = ['project_plan', 'handover_form'];

        // Helper function to get category label
        function getCategoryLabel($subcategory) {
            switch($subcategory) {
                case 'kickoff_meeting_slide': return 'KICK-OFF MEETING SLIDE';
                case 'project_plan': return 'PROJECT PLAN';
                case 'uat_form': return 'USER ACCEPTANCE TEST FORM';
                case 'handover_form': return 'PROJECT GO-LIVE HANDOVER FORM';
                default: return strtoupper(str_replace('_', ' ', $subcategory));
            }
        }
    @endphp

    <style>
        .implementation-docs-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .implementation-docs-column {
            flex: 1;
            min-width: 300px;
        }

        .implementation-docs-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 16px;
            margin-bottom: 20px;
        }

        .implementation-docs-heading {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .implementation-docs-heading-label {
            font-size: 11px;
            font-weight: normal;
            color: #6b7280;
            background-color: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .implementation-docs-files {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 12px;
        }

        .implementation-docs-file {
            display: flex;
            align-items: flex-start;
            padding: 12px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .implementation-docs-file:hover {
            background-color: #f3f4f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .implementation-docs-file-icon {
            flex-shrink: 0;
            margin-right: 12px;
        }

        .implementation-docs-file-content {
            flex: 1;
            min-width: 0;
        }

        .implementation-docs-file-name {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .implementation-docs-file-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .implementation-docs-file-actions {
            display: flex;
            gap: 8px;
        }

        .implementation-docs-file-btn {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }

        .implementation-docs-file-btn.view {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .implementation-docs-file-btn.view:hover {
            background-color: #bae6fd;
        }

        .implementation-docs-file-btn.download {
            background-color: #dcfce7;
            color: #15803d;
        }

        .implementation-docs-file-btn.download:hover {
            background-color: #bbf7d0;
        }

        .implementation-docs-file-btn.share {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .implementation-docs-file-btn.share:hover {
            background-color: #e5e7eb;
        }

        .implementation-docs-file-btn svg {
            width: 12px;
            height: 12px;
            margin-right: 4px;
        }

        .implementation-docs-empty {
            padding: 12px;
            text-align: center;
            color: #6b7280;
            background-color: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
        }
    </style>

    <div class="implementation-docs-container">
        <!-- Left Column -->
        <div class="implementation-docs-column">
            @foreach($leftColumnCategories as $category)
                @if(isset($implementationDocuments[$category]) && count($implementationDocuments[$category]) > 0)
                    <div class="implementation-docs-card">
                        <div class="implementation-docs-heading">
                            {{ getCategoryLabel($category) }}
                        </div>
                        <div class="implementation-docs-files">
                            @foreach($implementationDocuments[$category] as $file)
                                @php
                                    $extension = pathinfo(Storage::path($file->filename), PATHINFO_EXTENSION);
                                    $iconColor = match(strtolower($extension)) {
                                        'pdf' => '#f87171',
                                        'doc', 'docx' => '#60a5fa',
                                        'xls', 'xlsx', 'csv' => '#34d399',
                                        'ppt', 'pptx' => '#fb923c',
                                        default => '#8b5cf6'
                                    };

                                    $displayName = basename($file->filename);
                                    $shortName = strlen($displayName) > 35 ? substr($displayName, 0, 32) . '...' : $displayName;
                                    $uploadedBy = App\Models\User::find($file->uploaded_by);
                                    $uploadedByName = $uploadedBy ? $uploadedBy->name : 'System';
                                @endphp

                                <div class="implementation-docs-file">
                                    <div class="implementation-docs-file-icon">
                                        @if(in_array(strtolower($extension), ['pdf']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @elseif(in_array(strtolower($extension), ['doc', 'docx']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        @elseif(in_array(strtolower($extension), ['xls', 'xlsx', 'csv']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        @elseif(in_array(strtolower($extension), ['ppt', 'pptx']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                            </svg>
                                        @else
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="implementation-docs-file-content">
                                        <div class="implementation-docs-file-name" title="{{ $displayName }}">
                                            {{ $shortName }}
                                        </div>
                                        <div class="implementation-docs-file-meta">
                                            Uploaded by {{ $uploadedByName }} on {{ $file->created_at->format('d F Y, H:i:s') }}
                                        </div>
                                        <div class="implementation-docs-file-actions">
                                            <a href="{{ Storage::url($file->filename) }}" target="_blank" class="implementation-docs-file-btn view">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                            <a href="{{ Storage::url($file->filename) }}" download class="implementation-docs-file-btn download">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                                Download
                                            </a>
                                            <a href="#" onclick="copyShareableLink('{{ Storage::url($file->filename) }}')" class="implementation-docs-file-btn share">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                                </svg>
                                                Share
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="implementation-docs-card">
                        <div class="implementation-docs-heading">
                            {{ getCategoryLabel($category) }}
                        </div>
                        <div class="implementation-docs-empty">
                            No files have been uploaded yet.
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Right Column -->
        <div class="implementation-docs-column">
            @foreach($rightColumnCategories as $category)
                @if(isset($implementationDocuments[$category]) && count($implementationDocuments[$category]) > 0)
                    <div class="implementation-docs-card">
                        <div class="implementation-docs-heading">
                            {{ getCategoryLabel($category) }}
                        </div>
                        <div class="implementation-docs-files">
                            @foreach($implementationDocuments[$category] as $file)
                                @php
                                    $extension = pathinfo(Storage::path($file->filename), PATHINFO_EXTENSION);
                                    $iconColor = match(strtolower($extension)) {
                                        'pdf' => '#f87171',
                                        'doc', 'docx' => '#60a5fa',
                                        'xls', 'xlsx', 'csv' => '#34d399',
                                        'ppt', 'pptx' => '#fb923c',
                                        default => '#8b5cf6'
                                    };

                                    $displayName = basename($file->filename);
                                    $shortName = strlen($displayName) > 35 ? substr($displayName, 0, 32) . '...' : $displayName;
                                    $uploadedBy = App\Models\User::find($file->uploaded_by);
                                    $uploadedByName = $uploadedBy ? $uploadedBy->name : 'System';
                                @endphp

                                <div class="implementation-docs-file">
                                    <div class="implementation-docs-file-icon">
                                        @if(in_array(strtolower($extension), ['pdf']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @elseif(in_array(strtolower($extension), ['doc', 'docx']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        @elseif(in_array(strtolower($extension), ['xls', 'xlsx', 'csv']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        @elseif(in_array(strtolower($extension), ['ppt', 'pptx']))
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                            </svg>
                                        @else
                                            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="{{ $iconColor }}">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="implementation-docs-file-content">
                                        <div class="implementation-docs-file-name" title="{{ $displayName }}">
                                            {{ $shortName }}
                                        </div>
                                        <div class="implementation-docs-file-meta">
                                            Uploaded by {{ $uploadedByName }} on {{ $file->created_at->format('d F Y, H:i:s') }}
                                        </div>
                                        <div class="implementation-docs-file-actions">
                                            <a href="{{ Storage::url($file->filename) }}" target="_blank" class="implementation-docs-file-btn view">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                            <a href="{{ Storage::url($file->filename) }}" download class="implementation-docs-file-btn download">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                                Download
                                            </a>
                                            <a href="#" onclick="copyShareableLink('{{ Storage::url($file->filename) }}')" class="implementation-docs-file-btn share">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                                </svg>
                                                Share
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="implementation-docs-card">
                        <div class="implementation-docs-heading">
                            {{ getCategoryLabel($category) }}
                        </div>
                        <div class="implementation-docs-empty">
                            No files have been uploaded yet.
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <script>
        function copyShareableLink(url) {
            // Extract the relative path from the Storage URL
            const path = url.split('/storage/')[1];

            // Create a URL without "storage" in the path
            const fullUrl = 'https://crm.timeteccloud.com/file/' + path;

            navigator.clipboard.writeText(fullUrl).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy link: ', err);
                alert('Failed to copy link to clipboard');
            });
        }
    </script>
</div>
