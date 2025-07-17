<!DOCTYPE html>
<html>
<head>
    <title>TimeTec HR云端系统跟进</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>您好！<strong>{{ $lead['lastName'] }}</strong>，</p>

    <p>希望您一切安好。我想跟进一下之前发送的邮件，关于我们 TimeTec 的HR 云端系统。如果您还未查看，我希望您不会错过我们目前的特别促销。</p>

    <p>我们的系统旨在简化您的 HR 操作流程，包括：</p>
    <ul>
        <li><strong>✅考勤系统：</strong> 精准记录员工出勤</li>
        <li><strong>✅薪资系统：</strong> 准时且合规的薪资处理</li>
        <li><strong>✅报销系统：</strong> 加快并简化申请与审批</li>
        <li><strong>✅休假系统：</strong> 系统化管理员工请假</li>
    </ul>

    <p>正如之前提到的，只需订阅我们的考勤模块，即可获赠一台<strong>免费的生物识别设备</strong>（需符合条款与条件）。</p>

    <p>我非常乐意根据您方便的时间安排一次免费的系统演示，我将向您展示如何使用我们的系统，并为您介绍更多促销详情。</p>

    <p>请告知您方便的时间。</p>

    <p>祝商祺！</p>
    <p>
        TimeTec Cloud Sdn Bhd<br>
        办公室电话：+603-8070 9933<br>
        WhatsApp：{{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
