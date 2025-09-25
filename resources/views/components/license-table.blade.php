{{-- filepath: /var/www/html/timeteccrm/resources/views/components/license-table.blade.php --}}
<div class="license-summary-table">
    <style>
        .license-summary-table table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .license-summary-table th,
        .license-summary-table td {
            padding: 12px 16px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .license-summary-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .license-summary-table td {
            font-size: 18px;
            font-weight: 500;
            color: #1f2937;
        }
        .license-summary-table .attendance { background-color: #fef3c7; }
        .license-summary-table .leave { background-color: #d1fae5; }
        .license-summary-table .claim { background-color: #dbeafe; }
        .license-summary-table .payroll { background-color: #fce7f3; }
    </style>
    <table>
        <thead>
            <tr>
                <th class="attendance">Attendance</th>
                <th class="leave">Leave</th>
                <th class="claim">Claim</th>
                <th class="payroll">Payroll</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="attendance">{{ $licenseData['attendance'] }}</td>
                <td class="leave">{{ $licenseData['leave'] }}</td>
                <td class="claim">{{ $licenseData['claim'] }}</td>
                <td class="payroll">{{ $licenseData['payroll'] }}</td>
            </tr>
        </tbody>
    </table>
</div>
