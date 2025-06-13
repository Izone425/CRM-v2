<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0056b3;
            padding: 20px;
            text-align: center;
            color: white;
        }
        .content {
            padding: 20px;
            color: black;
        }
        .highlight {
            font-weight: bold;
            color: #0056b3;
        }
        .info-section {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0056b3;
        }
        .footer {
            margin-top: 20px;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .note {
            padding: 10px;
            background-color: #ffffcc;
            border-left: 4px solid #ffcc00;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Hi Admin Hardware,</p>

        <p>Please be informed that the user data migration for the following account has been completed.</p>

        <div class="info-section">
            <p><strong>Company name:</strong> {{ $emailContent['company']['name'] }}</p>
            <p><strong>Salesperson name:</strong> {{ $emailContent['salesperson']['name'] }}</p>
            <p><strong>Implementer name:</strong> {{ $emailContent['implementer']['name'] }}</p>
        </div>

        <p>You may proceed to retrieve the device from logistic to be handed over to the support team for upload user procedure.</p>

        <p>Kindly ignore this message if the above database does not purchase any device.</p>
    </div>
</body>
</html>
