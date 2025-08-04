<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f9f9f9;
        }
        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #1a5276;
            padding: 25px;
            text-align: center;
            color: white;
        }
        .content {
            padding: 30px;
        }
        h2 {
            color: #1a5276;
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 10px;
            margin-top: 0;
            text-align: center;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 14px 18px;
            text-align: left;
        }
        th {
            background-color: #f4f7fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        td {
            font-size: 14px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #e0e0e0;
        }
        p {
            margin: 20px 0;
            line-height: 1.8;
            color: #555;
        }
        .highlight {
            font-weight: bold;
            color: #1a5276;
        }
        .status {
            display: inline-block;
            padding: 6px 12px;
            background-color: #ffc107;
            color: #333;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TimeTec Implementer Request</h1>
        </div>
        <div class="content">
            <h2>IMPLEMENTER REQUEST</h2>

            <table>
                <tr>
                    <th>IMPLEMENTER ID</th>
                    <td>{{ $content['implementerId'] }}</td>
                </tr>
                <tr>
                    <th>IMPLEMENTER NAME</th>
                    <td class="highlight">{{ $content['implementerName'] }}</td>
                </tr>
                <tr>
                    <th>REQUEST DATE & TIME</th>
                    <td>{{ $content['requestDateTime'] }}</td>
                </tr>
                <tr>
                    <th>COMPANY NAME</th>
                    <td class="highlight">{{ $content['companyName'] }}</td>
                </tr>
                <tr>
                    <th>SESSION TYPE REQUEST</th>
                    <td>{{ $content['sessionType'] }}</td>
                </tr>
                <tr>
                    <th>DATE & DAY TYPE</th>
                    <td>{{ $content['dateAndDay'] }}</td>
                </tr>
                <tr>
                    <th>IMPLEMENTATION SESSION</th>
                    <td>{{ $content['implementationSession'] }}</td>
                </tr>
                <tr>
                    <th>STATUS</th>
                    <td><span class="status">{{ $content['status'] }}</span></td>
                </tr>
            </table>

            <p>
                Please review and approve this implementer request. You can contact the implementer if you need additional information.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} TimeTec Cloud | This is an automated email. Please do not reply to this message.
        </div>
    </div>
</body>
</html>
