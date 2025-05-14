<!-- filepath: /var/www/html/timeteccrm/resources/views/customer/login.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Your TimeTec Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F7FAFD;
        }
    </style>
</head>
<body>
    <div class="flex flex-col items-center justify-center min-h-screen">
        <div class="w-full max-w-2xl mx-auto mt-8 overflow-hidden bg-white shadow-lg rounded-3xl">
            <!-- Header -->
            <div class="relative h-[270px]">
                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-3xl font-semibold text-[#305edf] z-10">
                    Customer Portal
                </div>
                <img src="https://www.timeteccloud.com/temp/web_portal/images/img-welcometimetec.svg" alt="Welcome Banner" class="absolute bottom-10 left-1/2 transform -translate-x-1/2 w-[70%] z-5">
            </div>

            <!-- Body -->
            <div class="px-8 py-10">
                @if (session('success'))
                    <div class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form class="space-y-6" action="{{ route('customer.login.submit') }}" method="POST">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm text-gray-600">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-3 mt-1 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm text-gray-600">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-3 pr-12 mt-1 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <div id="togglePassword" class="absolute inset-y-0 flex items-center text-gray-500 cursor-pointer right-4">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="remember" class="block ml-2 text-sm text-gray-600">
                            Remember me
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-[#31c6f6] to-[#107eff] hover:opacity-90 text-white text-sm rounded-full transition duration-300">
                        Login
                    </button>
                </form>

                <p class="mt-8 text-xs text-center text-gray-600">
                    Forgot your password?
                    <a href="{{ route('customer.password.request') }}" class="text-blue-600 underline break-words">Reset here</a>.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="py-4 text-xs text-center text-gray-500">
            TimeTec © {{ date('Y') }}, All Rights Reserved.
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    // Toggle icon
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
