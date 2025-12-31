<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .training-info {
            margin: 20px 0;
        }
        .info-line {
            display: flex;
            margin: 5px 0;
        }
        .info-label {
            width: 120px;
            font-weight: normal;
        }
        .info-value {
            flex: 1;
        }
    </style>
</head>
<body>
<p>Dear Customer,</p>

<p>Kindly ensure that you type in your actual name & company name before joining the meeting.<br>
Please find below the training link.<br>
Training Mode : Online Training via Microsoft Teams</p>

<div class="training-info">
    <p><strong>Day 1| {{ $day1Date }} | TimeTec Attendance</strong></p>
    <div class="info-line">
        <span class="info-label">Time:</span>
        <span class="info-value">9AM-5PM</span>
    </div>
    <div class="info-line">
        <span class="info-label">Training Link:</span>
        <span class="info-value"><a href="{{ $session->day1_meeting_link }}">Day 1 Training Link</a></span>
    </div>
    <div class="info-line">
        <span class="info-label">Meeting ID :</span>
        <span class="info-value">{{ $day1MeetingId }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Password :</span>
        <span class="info-value">{{ $day1Password }}</span>
    </div>
</div>

<div class="training-info">
    <p><strong>Day 2| {{ $day2Date }} | TimeTec Leave & TimeTec Claim</strong></p>
    <div class="info-line">
        <span class="info-label">Time:</span>
        <span class="info-value">9AM-5PM</span>
    </div>
    <div class="info-line">
        <span class="info-label">Training Link:</span>
        <span class="info-value"><a href="{{ $session->day2_meeting_link }}">Day 2 Training Link</a></span>
    </div>
    <div class="info-line">
        <span class="info-label">Meeting ID :</span>
        <span class="info-value">{{ $day2MeetingId }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Password :</span>
        <span class="info-value">{{ $day2Password }}</span>
    </div>
</div>

<div class="training-info">
    <p><strong>Day 3 | {{ $day3Date }} | TimeTec Payroll</strong></p>
    <div class="info-line">
        <span class="info-label">Time:</span>
        <span class="info-value">9AM-5PM</span>
    </div>
    <div class="info-line">
        <span class="info-label">Training Link:</span>
        <span class="info-value"><a href="{{ $session->day3_meeting_link }}">Day 3 Training Link</a></span>
    </div>
    <div class="info-line">
        <span class="info-label">Meeting ID :</span>
        <span class="info-value">{{ $day3MeetingId }}</span>
    </div>
    <div class="info-line">
        <span class="info-label">Password :</span>
        <span class="info-value">{{ $day3Password }}</span>
    </div>
</div>

<p>Please take note of the module that you have subscribed and kindly disregard the training for modules that you did not subscribe.</p>

<p>Thank you for choosing TimeTec!</p>
</body>
</html>
