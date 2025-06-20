<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Status Update | {{ $repair_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            /* color: #333; */
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 650px;
            margin: 20px auto;
            text-align: center;
            color: black;
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #0056b3;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .text-left {
            text-align: left;
        }
        .repair-id {
            font-size: 18px;
            font-weight: bold;
            color: #0056b3;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            background-color: #28a745;
            color: white;
            border-radius: 4px;
            font-weight: bold;
        }
        .status.accepted {
            background-color: #28a745;
        }
        .status.in-progress {
            background-color: #ffc107;
            color: #333;
        }
        .status.resolved {
            background-color: #17a2b8;
        }
        .status.closed {
            background-color: #6c757d;
        }
        .details-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            text-align: left;
        }
        .details-table th, .details-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .details-table th {
            background-color: #f2f2f2;
            width: 30%;
        }
        .remark-box {
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f9f9f9;
            border-left: 4px solid #0056b3;
            border-radius: 4px;
            text-align: left;
        }
        .attachments-container {
            margin-top: 10px;
            padding: 8px;
            background-color: #eef2f7;
            border-radius: 4px;
        }
        .attachments-title {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .attachment-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .attachment-item {
            display: inline-block;
            margin: 4px;
        }
        .attachment-link {
            display: inline-block;
            padding: 5px 10px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 12px;
            color: #0056b3;
            text-decoration: none;
        }
        .attachment-link:hover {
            background-color: #f5f5f5;
        }
        .spare-parts-list {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 20px;
            text-align: left;
        }
        .spare-parts-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .spare-parts-list li:last-child {
            border-bottom: none;
        }
        .spare-part-name {
            font-weight: bold;
            color: #0056b3;
        }
        .spare-part-link {
            color: #0056b3;
            text-decoration: none;
            cursor: pointer;
        }
        .spare-part-link:hover {
            text-decoration: underline;
        }
        .spare-part-model {
            color: #666;
            font-size: 0.9em;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            text-align: center;
            background-color: #f9f9f9;
            padding: 10px;
            border-top: 1px solid #eee;
        }
        .section-heading {
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Repair Ticket Status Update</h1>
        </div>

        <div class="content">
            <p class="text-left">Dear Team,</p>

            <p class="text-left">The status of repair ticket <span class="repair-id">{{ $repair_id }}</span> for <strong>{{ $company_name }}</strong> has been updated to <span class="status {{ strtolower($status) }}">{{ $status }}</span>.</p>

            <table class="details-table">
                <tr>
                    <th>Repair ID</th>
                    <td>{{ $repair_id }}</td>
                </tr>
                <tr>
                    <th>Company</th>
                    <td>{{ $company_name }}</td>
                </tr>
                <tr>
                    <th>Device Model</th>
                    <td>{{ $model_id }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="status {{ strtolower($status) }}">{{ $status }}</span></td>
                </tr>
                <tr>
                    <th>Updated By</th>
                    <td>{{ $technician }}</td>
                </tr>
                <tr>
                    <th>Updated At</th>
                    <td>{{ $accepted_at }}</td>
                </tr>
            </table>

            <h3 class="section-heading">Repair Remarks:</h3>

            @if(!empty($repair_remarks))
                @foreach($repair_remarks as $key => $remark)
                    <div class="remark-box">
                        <strong>Remark #{{ $key + 1 }}:</strong>
                        <div>{!! nl2br(e($remark['remark'])) !!}</div>

                        @if(!empty($remark['attachments']))
                            @php
                                $attachments = is_string($remark['attachments']) ? json_decode($remark['attachments'], true) : $remark['attachments'];
                            @endphp
                            @if(!empty($attachments) && is_array($attachments))
                                <div class="attachments-container">
                                    <div class="attachments-title">Attachments:</div>
                                    <div class="attachment-list">
                                        @foreach($attachments as $index => $attachment)
                                            <div class="attachment-item">
                                                <a href="{{ url('storage/' . $attachment) }}" target="_blank" class="attachment-link">
                                                    Attachment {{ $index + 1 }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            @elseif(!empty($remarks_array))
                @foreach($remarks_array as $remark)
                    <div class="remark-box">
                        {!! nl2br(e($remark)) !!}
                    </div>
                @endforeach
            @else
                <p class="text-left">No specific remarks provided.</p>
            @endif

            @if(!empty($spare_parts) && count($spare_parts) > 0)
                <h3 class="section-heading">Spare Parts Required:</h3>
                <ul class="spare-parts-list">
                    @foreach($spare_parts as $part)
                        <li>
                            @if(isset($part['image_url']))
                                <a href="{{ $part['image_url'] }}" target="_blank" class="spare-part-link">
                                    <span class="spare-part-name">{{ $part['name'] }}</span>
                                </a>
                            @else
                                <span class="spare-part-name">{{ $part['name'] }}</span>
                            @endif

                            @if(isset($part['model']))
                                <span class="spare-part-model"> ({{ $part['model'] }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</body>
</html>
