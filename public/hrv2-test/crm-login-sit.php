<?php
/**
 * CRM Login as Owner - SIT Environment
 *
 * Run with: php -S localhost:3000 crm-login-sit.php
 * Then visit: http://localhost:3000
 *
 * Required: Place your private key at ./keys-sit/crm/crm_auth_private_key.pem
 */

// ============================================================
// CONFIGURATION - SIT ENVIRONMENT
// ============================================================

// API Configuration - SIT
$apiUrl = 'https://int-crmauth-hr-test.timeteccloud.com';
$apiKey = '2wQ6E0cDU+AjoWIbWkZ1apOkfDrPkMdH3WlX0SNnaQU=';

// Path to your RSA private key (relative to this file)
$privateKeyPath = __DIR__ . '/keys-sit/crm_auth_private_key.pem';

// Default redirect URL after successful login - SIT
$defaultRedirectUrl = 'https://hr-test.timeteccloud.com/auth/crm-login';

// Cookie domain for cross-subdomain sharing
$cookieDomain = '.timeteccloud.com';

// Environment label
$environment = 'SIT';
$envColor = '#2196f3';

// ============================================================
// PROCESSING - Handle POST request
// ============================================================

$result = null;
$error = null;
$success = null;
$debugInfo = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $email = $_POST['email'] ?? '';
        $userId = (int) ($_POST['userId'] ?? 0);
        $companyId = (int) ($_POST['companyId'] ?? 0);
        $crmUserId = (int) ($_POST['crmUserId'] ?? 0);
        $crmUserName = $_POST['crmUserName'] ?? '';

        // Validate inputs
        if (!$email || !$userId || !$companyId || !$crmUserId || !$crmUserName) {
            throw new Exception('All fields are required');
        }

        // Read private key
        if (!file_exists($privateKeyPath)) {
            throw new Exception("Private key not found at: {$privateKeyPath}");
        }
        $privateKey = file_get_contents($privateKeyPath);

        // Prepare payload
        $payload = [
            'email' => $email,
            'userId' => $userId,
            'companyId' => $companyId,
            'crmUserId' => $crmUserId,
            'crmUserName' => $crmUserName,
        ];
        $payloadJson = json_encode($payload);

        // Generate timestamp and signature
        $timestamp = gmdate('Y-m-d\TH:i:s.v\Z'); // ISO 8601 format
        $dataToSign = $payloadJson . $timestamp;

        // Sign with RSA-SHA256
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if (!$privateKeyResource) {
            throw new Exception('Invalid private key: ' . openssl_error_string());
        }

        openssl_sign($dataToSign, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        $signatureBase64 = base64_encode($signature);

        $debugInfo['timestamp'] = $timestamp;
        $debugInfo['dataToSign'] = $dataToSign;
        $debugInfo['payloadJson'] = $payloadJson;

        // Make API request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl . '/api/crmauth/loginas',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payloadJson,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $apiKey,
                'X-Signature: ' . $signatureBase64,
                'X-Timestamp: ' . $timestamp,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if (curl_errno($ch)) {
            throw new Exception('CURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        // Parse response
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $debugInfo['httpCode'] = $httpCode;
        $debugInfo['responseBody'] = $body;
        $debugInfo['responseHeaders'] = $headers;

        // Extract Set-Cookie headers
        preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $headers, $cookieMatches);
        $cookies = $cookieMatches[1] ?? [];

        $debugInfo['cookiesFromApi'] = $cookies;

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($body, true);

            // Forward cookies as-is (API already sets domain=.timeteccloud.com)
            foreach ($cookies as $cookie) {
                // Trim any whitespace/newlines from the cookie
                $cookie = trim($cookie);
                header('Set-Cookie: ' . $cookie, false);
            }

            // Get redirect URL
            $redirectUrl = $responseData['redirectUrl'] ?? $defaultRedirectUrl;

            // Redirect to HR app
            header('Location: ' . $redirectUrl, true, 302);
            exit;

        } else {
            $errorBody = json_decode($body, true);
            throw new Exception('API Error (' . $httpCode . '): ' . ($errorBody['message'] ?? $body));
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get old values for form - SIT test user
$oldEmail = $_POST['email'] ?? 'ft.karentan6@yopmail.com';
$oldUserId = $_POST['userId'] ?? '263';
$oldCompanyId = $_POST['companyId'] ?? '89';
$oldCrmUserId = $_POST['crmUserId'] ?? '999';
$oldCrmUserName = $_POST['crmUserName'] ?? 'Demo CRM Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Login - <?= $environment ?></title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, <?= $envColor ?> 0%, #1976d2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
        h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }
        .env-badge {
            display: inline-block;
            padding: 4px 12px;
            background: <?= $envColor ?>;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus {
            outline: none;
            border-color: <?= $envColor ?>;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        button {
            width: 100%;
            padding: 14px 20px;
            background: <?= $envColor ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1565c0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .debug {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            font-size: 12px;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 400px;
            overflow-y: auto;
        }
        .debug-toggle {
            background: #f0f0f0;
            border: 1px solid #ddd;
            color: <?= $envColor ?>;
            cursor: pointer;
            font-size: 13px;
            padding: 8px 16px;
            margin-top: 20px;
            width: auto;
            border-radius: 6px;
        }
        .debug-toggle:hover {
            background: #e8e8e8;
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CRM Login as Owner <span class="env-badge"><?= $environment ?></span></h1>
        <p class="subtitle">Authenticate HR user and redirect to HR System</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="info">
            <strong>API:</strong> <?= htmlspecialchars($apiUrl) ?><br>
            <strong>Redirect:</strong> <?= htmlspecialchars($defaultRedirectUrl) ?><br>
            <strong>Cookie Domain:</strong> <?= htmlspecialchars($cookieDomain ?: '(current host)') ?><br>
            <strong>Private Key:</strong> <?= file_exists($privateKeyPath) ? 'Found' : 'NOT FOUND' ?>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">HR User Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($oldEmail) ?>" required>
            </div>

            <div class="form-group">
                <label for="userId">HR User ID</label>
                <input type="number" id="userId" name="userId" value="<?= htmlspecialchars($oldUserId) ?>" required>
            </div>

            <div class="form-group">
                <label for="companyId">Company ID</label>
                <input type="number" id="companyId" name="companyId" value="<?= htmlspecialchars($oldCompanyId) ?>" required>
            </div>

            <div class="form-group">
                <label for="crmUserId">CRM User ID</label>
                <input type="number" id="crmUserId" name="crmUserId" value="<?= htmlspecialchars($oldCrmUserId) ?>" required>
            </div>

            <div class="form-group">
                <label for="crmUserName">CRM User Name</label>
                <input type="text" id="crmUserName" name="crmUserName" value="<?= htmlspecialchars($oldCrmUserName) ?>" required>
            </div>

            <button type="submit">Login as Owner</button>
        </form>

        <?php if (!empty($debugInfo)): ?>
            <button class="debug-toggle" onclick="document.getElementById('debug').style.display = document.getElementById('debug').style.display === 'none' ? 'block' : 'none'">
                Toggle Debug Info
            </button>
            <div id="debug" class="debug" style="display: none;"><?= htmlspecialchars(json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
