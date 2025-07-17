<!DOCTYPE html>
<html>
<head>
    <title>TimeTec HR云端系统介绍</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>您好！<strong>{{ $lead['lastName'] }}</strong>，</p>

    <p>我是 TimeTec 的<strong>{{ $leadOwnerName }}</strong>。非常感谢您对我们HR云端系统的关注！</p>

    <p>我想向您介绍 TimeTec HR 云端系统，它能协助您更高效地管理日常 HR 工作：</p>
    <ul>
        <li><strong>考勤系统：</strong> 精准记录员工出勤</li>
        <li><strong>薪资系统：</strong> 准时且合规的薪资处理</li>
        <li><strong>报销系统：</strong> 加快并简化申请与审批</li>
        <li><strong>休假系统：</strong> 系统化管理员工请假</li>
    </ul>

    <p>作为特别促销活动的一部分，凡订阅我们的考勤系统，即可获得一台 <strong>免费的生物识别设备</strong>（需符合条款与条件）。</p>

    <p>欢迎向我们预约免费的演示，您将能更深入地了解我们可以如何帮助您提升企业效率，我们也会说明如何领取您的免费设备。</p>

    <p>若想更了解我们的产品，请查阅我们的简介：
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">点击这里</a>
    </p>
    <p>期待您的回复！</p>

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

