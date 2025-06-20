<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Software Handover Assignment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 650px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background-color: #0056b3;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .emphasis {
            font-weight: bold;
            color: #0056b3;
        }
        .summary {
            background-color: #f5f9ff;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0056b3;
            border-radius: 4px;
        }
        .handover-list {
            margin-bottom: 25px;
        }
        .handover-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            background-color: #fafafa;
        }
        .handover-header {
            background-color: #f2f2f2;
            padding: 10px;
            margin: -15px -15px 15px;
            border-bottom: 1px solid #ddd;
            border-radius: 4px 4px 0 0;
            display: flex;
            justify-content: space-between;
        }
        .company-name {
            font-weight: bold;
            font-size: 16px;
            color: #0056b3;
        }
        .handover-id {
            color: #666;
        }
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .module-item {
            padding: 5px;
            text-align: center;
            background-color: #e9f5ff;
            border-radius: 3px;
            font-size: 12px;
        }
        .module-active {
            background-color: #d4edda;
            color: #155724;
        }
        .module-inactive {
            background-color: #f8d7da;
            color: #721c24;
            text-decoration: line-through;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .info-value {
            flex: 1;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-top: 10px;
            color: #fff;
            background-color: #0056b3;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Batch Software Handover Assignment</h1>
        </div>

        <div class="summary">
            <p>Dear <span class="emphasis">{{ $emailContent['implementer']['name'] }}</span>,</p>

            <p>
                You have been assigned as the implementer for <span class="emphasis">{{ $emailContent['total_count'] }} {{ Str::plural('software handover', $emailContent['total_count']) }}</span>
                by {{ $emailContent['updated_by'] }} on {{ $emailContent['updated_at'] }}.
            </p>
        </div>

        <h2>Assigned Handovers:</h2>

        <div class="handover-list">
            @foreach($emailContent['handovers'] as $handover)
                <div class="handover-item">
                    <div class="handover-header">
                        <span class="company-name">{{ $handover['company_name'] }}</span>
                        <span class="handover-id">{{ $handover['handover_id'] }}</span>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Date Created:</div>
                        <div class="info-value">{{ $handover['date_created'] }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Previous Implementer:</div>
                        <div class="info-value">{{ $handover['old_implementer'] }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Salesperson:</div>
                        <div class="info-value">{{ $handover['salesperson'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
