<!DOCTYPE html>
<html>
<head>
    <title>会议取消通知</title>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>您好 {{ $lead['lastName'] }}，</p>

    <p>在此通知您，原定于 {{ Carbon\Carbon::createFromFormat('d/m/Y', $lead['date'])->format('Y年n月j日') }}
    {{ Carbon\Carbon::parse($lead['startTime'])->format('a') == 'am' ? '上午' : '下午' }}{{ Carbon\Carbon::parse($lead['startTime'])->format('g点i分') }}的会议已取消。</p>

    <p>如您希望重新安排会议，欢迎随时告知您方便的时段，我们很乐意为您安排。</p>

    <p>顺祝 商祺！</p>
    <p>
        {{ $leadOwnerName }}<br>
        @if(isset($lead['salespersonPosition']))
            @if(strtolower($lead['salespersonPosition']) == 'business development executive')
                市场开发代表<br>
            @elseif(strtolower($lead['salespersonPosition']) == 'sales development representative')
                市场开发专员<br>
            @else
                {{ $lead['salespersonPosition'] }}<br>
            @endif
        @else
            TimeTec 客户服务代表<br>
        @endif
        TimeTec Cloud Sdn. Bhd.<br>
        @if(isset($lead['leadOwnerMobileNumber']) && !empty($lead['leadOwnerMobileNumber']))
            联系电话: {{ $lead['leadOwnerMobileNumber'] }}
        @endif
    </p>
</body>
</html>
