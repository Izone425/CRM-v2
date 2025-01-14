<!DOCTYPE html>
<html lang="en">
<head>
    <title>Demo Request Form</title>
    @livewireStyles
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="{{ asset('demoRequest.css') }}" rel="stylesheet" id="bootstrap-css">

</head>
<body style="font-family: 'AptosBody', sans-serif;">
    <div style=" padding: 30px;">
        <livewire:demo-request :lead_code="$lead_code" />
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

@livewireScripts
</body>
</html>
