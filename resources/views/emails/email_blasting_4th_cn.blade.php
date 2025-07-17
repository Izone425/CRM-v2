<!DOCTYPE html>
<html>
<head>
    <title>TimeTec HR云端系统最终跟进</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>您好！<strong>{{ $lead['lastName'] }}</strong>，</p>

    <p>希望您一切顺利。这封邮件是我最后一次跟进，以免过度打扰您。</p>

    <p>如果现在不是探讨我们HR 云端系统的时机，您是否愿意让我知道何时会更方便，或是否有其他联系人愿意与我接洽？</p>

    <p>若您或贵公司日后希望重新评估此方案，我将非常乐意再次为您服务。</p>

    <p>感谢您宝贵的时间，祝事业顺利，蒸蒸日上！</p>

    <p>祝商祺！</p>
    <p> {{ $leadOwnerName }} 敬上<br>
        @if(isset($lead['position']))
            @if(strtolower($lead['position']) == 'business development executive')
                市场开发代表<br>
            @elseif(strtolower($lead['position']) == 'sales development representative')
                市场开发专员<br>
            @else
                {{ $lead['position'] }}<br>
            @endif
        @else
            TimeTec 客户服务代表<br>
        @endif
        TimeTec Cloud Sdn Bhd<br>
        办公室电话：+603-8070 9933<br>
        WhatsApp：{{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
