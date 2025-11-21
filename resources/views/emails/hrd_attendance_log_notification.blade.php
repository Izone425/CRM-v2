<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRDF Attendance Log - {{ $log->company_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .email-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .content {
            font-size: 14px;
            margin-bottom: 20px;
        }
        .grant-id {
            background-color: #fef3c7;
            padding: 10px 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 15px;
        }
        .grant-id strong {
            color: #92400e;
        }
        .attachments-box {
            background-color: #eff6ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            margin: 20px 0;
        }
        .attachments-box h4 {
            margin: 0 0 15px 0;
            color: #1e40af;
            font-size: 16px;
        }
        .view-link {
            display: block;
            padding: 12px 15px;
            margin: 8px 0;
            background-color: #dbeafe;
            border-radius: 6px;
            text-decoration: none;
            color: #1e40af;
            font-weight: 600;
            transition: background-color 0.2s;
            border: 1px solid #bfdbfe;
        }
        .view-link:hover {
            background-color: #bfdbfe;
        }
        .view-link::before {
            content: "📄 ";
            margin-right: 8px;
        }
        .footer {
            text-align: center;
            padding: 20px 0 0 0;
            color: #6b7280;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="greeting">
            HI {{ strtoupper($salesperson->name) }}
        </div>

        <div class="content">
            <p>PLEASE REFER TO THE ATTACHMENT FOR THE HRDF ATTENDANCE LOG + JD14 FORM DOCUMENT.</p>
        </div>

        <div class="grant-id">
            <strong>GRANT ID:</strong> {{ $log->grant_id }}
        </div>

        <div class="attachments-box">
            <h4>📎 Documents</h4>

            @php
                $documents = is_array($log->document_paths) ? $log->document_paths : json_decode($log->document_paths, true);
            @endphp

            @if($documents && is_array($documents))
                @foreach($documents as $index => $document)
                    <a href="{{ url('storage/' . $document) }}" class="view-link" target="_blank">
                        {{ 'Document ' . ($index + 1) }}
                    </a>
                @endforeach
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} TimeTec CRM System</p>
        </div>
    </div>
</body>
</html>
