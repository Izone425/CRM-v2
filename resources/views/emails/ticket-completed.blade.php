{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/ticket-completed.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Completed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #000;
            margin: 20px;
            font-size: 14px;
        }
        .container {
            max-width: 800px;
        }
        .header {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #000;
        }
        .content-table td {
            border: 1px solid #000;
            padding: 15px;
            vertical-align: top;
        }
        .left-column {
            width: 50%;
        }
        .right-column {
            width: 50%;
        }
        .section-divider {
            margin: 15px 0 10px 0;
            font-weight: bold;
        }
        .field-label {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .field-value {
            margin-bottom: 15px;
            white-space: pre-wrap; /* ✅ Added to preserve line breaks */
            word-wrap: break-word; /* ✅ Added to handle long words */
        }
        .attachment-link {
            color: #0066cc;
            text-decoration: underline;
        }
        .dashed-line {
            border: none;
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Hi {{ $createdByName }}</p>

        <p>Your ticket ID {{ $ticketId }} has been completed.</p>

        <p>Kindly refer to the below details:</p>

        <table class="content-table">
            <tr>
                <td class="left-column">
                    <div class="field-label">Created By:</div>
                    <div class="field-value">{{ $createdByName }}</div>

                    <div class="field-label">Created Date & Time:</div>
                    <div class="field-value">{{ $createdDate }}</div>

                    <div class="field-label">Attention To:</div>
                    <div class="field-value">{{ $attentionToName }}</div>

                    <div class="field-label">Status:</div>
                    <div class="field-value">Completed</div>

                    <div class="section-divider">
                        ------------------------------------<br>
                        Details by Owner Ticket
                    </div>

                    <div class="field-label">Remark:</div>
                    <div class="field-value">{!! nl2br(e($remark ?: 'No remark provided')) !!}</div>

                    @if($attachments && count($attachments) > 0)
                        @foreach($attachments as $index => $attachment)
                            <div class="field-label">Attachment {{ $index + 1 }}:</div>
                            <div class="field-value">
                                <a href="{{ asset('storage/' . $attachment) }}" class="attachment-link">View</a>
                            </div>
                        @endforeach
                    @else
                        <div class="field-label">Attachments:</div>
                        <div class="field-value">No attachments</div>
                    @endif
                </td>

                <td class="right-column">
                    <div class="field-label">Completed By:</div>
                    <div class="field-value">{{ $completedByName }}</div>

                    <div class="field-label">Completed Date & Time:</div>
                    <div class="field-value">{{ $completedDate }}</div>

                    <div class="field-label">Status:</div>
                    <div class="field-value">Completed</div>

                    <div class="field-label">Duration to Complete:</div>
                    <div class="field-value">{{ $duration }}</div>

                    <div class="section-divider">
                        ------------------------------------<br>
                        Details by Admin
                    </div>

                    <div class="field-label">Remark:</div>
                    <div class="field-value">{!! nl2br(e($adminRemark ?: 'No admin remark provided')) !!}</div>

                    @if($adminAttachments && count($adminAttachments) > 0)
                        @foreach($adminAttachments as $index => $attachment)
                            <div class="field-label">Attachment {{ $index + 1 }}:</div>
                            <div class="field-value">
                                <a href="{{ asset('storage/' . $attachment) }}" class="attachment-link">View</a>
                            </div>
                        @endforeach
                    @else
                        <div class="field-label">Admin Attachments:</div>
                        <div class="field-value">No admin attachments</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
