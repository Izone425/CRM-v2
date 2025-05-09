<!-- filepath: /var/www/html/timeteccrm/resources/views/livewire/customer/forgot-password.blade.php -->
<div class="flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-2xl mx-auto mt-8 overflow-hidden bg-white shadow-lg rounded-3xl">
        <!-- Header -->
        <div class="relative h-[270px]">
            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-3xl font-semibold text-[#305edf] z-10">
                Reset Password
            </div>
            <img src="https://www.timeteccloud.com/temp/web_portal/images/img-welcometimetec.svg" alt="Welcome Banner" class="absolute bottom-10 left-1/2 transform -translate-x-1/2 w-[70%] z-5">
        </div>

        <!-- Body -->
        <div class="px-8 py-10">
            @if ($successMessage)
                <div class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
                    {{ $successMessage }}
                </div>
            @endif

            <p class="mb-6 text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>

            <form class="space-y-6" wire:submit.prevent="sendResetLink">
                <div>
                    <label for="email" class="block text-sm text-gray-600">Email</label>
                    <input wire:model="email" type="email" id="email" name="email" required class="w-full px-4 py-3 mt-1 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-[#31c6f6] to-[#107eff] hover:opacity-90 text-white text-sm rounded-full transition duration-300">
                    Send Reset Link
                </button>
            </form>

            <p class="mt-8 text-xs text-center text-gray-600">
                Remember your password?
                <a href="{{ route('customer.login') }}" class="text-blue-600 underline break-words">Back to login</a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <div class="py-4 text-xs text-center text-gray-500">
        TimeTec © {{ date('Y') }}, All Rights Reserved.
    </div>
</div>
