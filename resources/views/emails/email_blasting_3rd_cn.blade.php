<!DOCTYPE html>
<html>
<head>
    <title>TimeTec HR云端系统确认</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>您好！<strong>{{ $lead['lastName'] }}</strong>，</p>

    <p>希望您一切顺利。我写这封邮件是想确认之前发送给您，关于TimeTec HR 云端系统的邮件是否顺利送达。</p>

    <p>我们的免费生物识别设备促销仍然有效（附带条款与条件），我希望有机会与您探讨，如何通过量身定制的HR系统来简化贵公司的 HR 流程。</p>

    <p>请问是否方便让我为您安排一次简短的系统演示？如果您目前暂时不考虑，没关系，请让我知道，我将不再做进一步跟进。</p>

    <p>期待您的回复。</p>

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
