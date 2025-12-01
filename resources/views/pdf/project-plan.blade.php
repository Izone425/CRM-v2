<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Project Plan - {{ $company_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: avoid; /* Prevent table from breaking inside */
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .module-header {
            background-color: #00B0F0;
            color: white;
            font-weight: bold;
        }
        .plan-header {
            background-color: #ffff00b9;
            font-weight: bold;
        }
        .actual-header {
            background-color: #00FF00;
            font-weight: bold;
        }
        .remarks-header {
            background-color: #FFE699;
            font-weight: bold;
        }
        .task-row td {
            text-align: left;
        }
        .task-row .center {
            text-align: center;
        }
        .remarks-cell {
            max-width: 200px;
            word-wrap: break-word;
            text-align: left;
        }
        /* Remove automatic page breaks, let it flow naturally */
        .module-table {
            page-break-inside: avoid;
        }
        /* Only break before table if it's too large */
        .large-table {
            page-break-before: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Project Implementation Plan</h1>
    </div>

    <div class="company-info">
        <div class="info-row">
            <span class="info-label">Company Name:</span>
            <span>{{ $company_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Implementer Name:</span>
            <span>{{ $implementer_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Generated At:</span>
            <span>{{ $generated_at }}</span>
        </div>
    </div>

    @foreach($project_data as $moduleIndex => $moduleData)
        <table class="module-table {{ count($moduleData['plans']) > 15 ? 'large-table' : '' }}">
            <!-- Plan/Actual Headers Row -->
            <tr>
                <th width="5%">#</th>
                <th width="25%">Task Name</th>
                <th width="8%">Status</th>
                <th width="6%">%</th>
                <th colspan="3" class="plan-header">Plan</th>
                <th colspan="3" class="actual-header">Actual</th>
                <th width="20%" class="remarks-header">Remarks</th>
            </tr>

            <!-- Module Info + Sub-headers Row -->
            <tr class="module-header">
                <td>{{ $moduleData['module_code'] }}</td>
                <td>{{ $moduleData['module_name'] }}</td>
                <td>Status</td>
                <td>{{ $moduleData['module_percentage'] }}%</td>
                <td class="plan-header">Start Date</td>
                <td class="plan-header">End Date</td>
                <td class="plan-header">Duration</td>
                <td class="actual-header">Start Date</td>
                <td class="actual-header">End Date</td>
                <td class="actual-header">Duration</td>
                <td class="remarks-header">Remarks</td>
            </tr>

            <!-- Task Rows -->
            @foreach($moduleData['plans'] as $index => $plan)
                <tr class="task-row">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $plan['task_name'] }}</td>
                    <td class="center">{{ $plan['status'] }}</td>
                    <td class="center">{{ $plan['task_percentage'] }}</td>
                    <td class="center">{{ $plan['plan_start_date'] }}</td>
                    <td class="center">{{ $plan['plan_end_date'] }}</td>
                    <td class="center">{{ $plan['plan_duration'] }}</td>
                    <td class="center">{{ $plan['actual_start_date'] }}</td>
                    <td class="center">{{ $plan['actual_end_date'] }}</td>
                    <td class="center">{{ $plan['actual_duration'] }}</td>
                    <td class="remarks-cell">{{ $plan['remarks'] }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach
</body>
</html>
