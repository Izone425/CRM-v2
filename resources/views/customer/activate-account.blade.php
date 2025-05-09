<!-- filepath: /var/www/html/timeteccrm/resources/views/customer/activate-account.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Your TimeTec Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
          font-family: 'Poppins', sans-serif;
          background-color: #F7FAFD;
        }
        .hover\:custom-sky-dark:hover {
          background-color: rgb(2 132 199);
        }
        .custom-text {
          color: rgb(30 58 138 / var(--tw-text-opacity, 1));
        }
        .content-bg {
          background-color: #ffffff;
        }
        .header-bg {
          position: relative;
          height: 270px;
        }
        .header-text {
          position: absolute;
          bottom: 170px;
          left: 50%;
          transform: translateX(-50%);
          font-size: 1.5rem;
          font-weight: 600;
          color: rgb(30 58 138);
          z-index: 10;
        }
        .header-image {
          position: absolute;
          bottom: 40px;
          left: 50%;
          transform: translateX(-50%);
          width: 70%;
          z-index: 5;
        }
        .pill-button {
          background-image: linear-gradient(to right, #3ecdf4, #007bff);
          border-radius: 9999px;
        }
      </style>
</head>
<body>
    <!-- Content Row -->
    <div class="max-w-2xl mx-auto mt-8 overflow-hidden shadow-lg rounded-3xl">
        <div class="content-bg">
            <!-- Header -->
            <div class="header-bg">
                <div class="header-text">Welcome to</div>
                <img src="https://www.timeteccloud.com/temp/web_portal/images/img-welcometimetec.svg" alt="Welcome Banner" class="header-image">
            </div>

            <!-- Body -->
            <div class="px-8 py-10">
                @if (session('error'))
                    <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <form id="activation-form" class="space-y-6" action="{{ route('customer.complete-activation', $token) }}" method="POST">
                    @csrf

                    <div>
                        <label class="block text-sm text-gray-600">Company Name</label>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ $customer->company_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600">Email</label>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ $customer->email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm text-gray-600">Password</label>
                        <input type="password" id="password" name="password" required class="w-full px-4 py-3 mt-1 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm text-gray-600">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required class="w-full px-4 py-3 mt-1 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-[#31c6f6] to-[#107eff] hover:opacity-90 text-white text-sm rounded-full transition duration-300">
                        Activate Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer Row -->
    <div class="py-4 text-xs text-center text-gray-500">
        TimeTec © {{ date('Y') }}, All Rights Reserved.
    </div>

    <script>
        document.getElementById('activation-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;
            if (password !== confirm) {
                e.preventDefault();
                alert("Passwords do not match.");
            }
        });
    </script>
</body>
</html>
