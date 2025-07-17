<!DOCTYPE html>
<html>
<head>
    <title>演示信息通知</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>您好！<strong>{{ $lead['lastName'] }}</strong>，</p>

    <p>根据我们之前的电话沟通，演示的时间已安排如下。请查阅以下销售同事及会议信息：</p>

    <p><strong>销售同事信息</strong></p>
    <ul>
        <li><strong>姓名：</strong> {{ $lead['salespersonName'] }}</li>
        <li><strong>联系号码：</strong> {{ $lead['salespersonPhone'] }}</li>
        <li><strong>联系邮箱：</strong> {{ $lead['salespersonEmail'] }}</li>
    </ul>

    <p><strong>客户信息</strong></p>
    <ul>
        <li><strong>公司名字：</strong> {{ $lead['company'] }}</li>
        <li><strong>联系号码：</strong> {{ $lead['phone'] }}</li>
        <li><strong>负责人：</strong> {{ $lead['pic'] }}</li>
        <li><strong>联系邮箱：</strong> {{ $lead['email'] }}</li>
    </ul>

    <p><strong>演示详情</strong></p>
    <ul>
        <li><strong>演示类型：</strong> {{ $lead['demo_type'] }}</li>
        <li><strong>预约类型:</strong> {{ $lead['appointment_type'] }}</li>
        <li>
            <strong>演示日期/时间：</strong>
            {{ \Carbon\Carbon::createFromFormat('d/m/Y', $lead['date'])->format('Y年n月j日') }}
            {{ \Carbon\Carbon::parse($lead['startTime'])->format('H:i') }} -
            {{ \Carbon\Carbon::parse($lead['endTime'])->format('H:i') }}
        </li>
        @if($lead['demo_type'] === 'WEBINAR DEMO')
        <li>
            <strong>会议链接：</strong>
            <a href="{{ $lead['demo_type'] === 'WEBINAR DEMO' ? $lead['salespersonMeetingLink'] : $lead['meetingLink'] }}" target="_blank">
                {{ $lead['demo_type'] === 'WEBINAR DEMO' ? $lead['salespersonMeetingLink'] : $lead['meetingLink'] }}
            </a>
        </li>
        @else
        <li>
            <strong>演示地点：</strong>
            @if($lead['demo_location'] === 'CUSTOMER')
                若线下演示，将会安排在客户办公室
            @else
                TIMETEC OFFICE, Level 18, Tower 5 @ PFCC, Jalan Puteri 1/2, Bandar Puteri, 47100 Puchong, Selangor
            @endif
        </li>
        @endif
    </ul>

    <p>祝商祺！</p>
    <p> {{ $salespersonName }} 敬上<br>
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
        办公室电话： +603-8070 9933<br>
        手机号码： {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>

    <p>
        <img src="{{ asset('img/refer-earn.png') }}" alt="推荐与赚取"
             style="width: 100%; max-width: 600px; display: block; margin-top: 20px;">
    </p>
</body>
</html>
