<!-- filepath: /var/www/html/timeteccrm/resources/views/livewire/customer/reset-password.blade.php -->
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
            <form class="space-y-6" wire:submit.prevent="resetPassword">
                <div>
                    <label for="email" class="block text-sm text-gray-600">Email</label>
                    <input wire:model="email" type="email" id="email" name="email" readonly class="w-full px-4 py-3 mt-1 text-sm border border-gray-300 rounded-full bg-gray-50 focus:outline-none">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm text-gray-600">New Password</label>
                    <div class="relative">
                        <input wire:model="password" type="password" id="password" name="password" required class="w-full px-4 py-3 pr-12 mt-1 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <div id="togglePassword" class="absolute inset-y-0 flex items-center text-gray-500 cursor-pointer right-4">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm text-gray-600">Confirm New Password</label>
                    <div class="relative">
                        <input wire:model="password_confirmation" type="password" id="password_confirmation" name="password_confirmation" required class="w-full px-4 py-3 pr-12 mt-1 text-sm border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-[#31c6f6] to-[#107eff] hover:opacity-90 text-white text-sm rounded-full transition duration-300">
                    Reset Password
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="py-4 text-xs text-center text-gray-500">
        TimeTec © {{ date('Y') }}, All Rights Reserved.
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        const toggleIcon = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (toggleIcon && passwordInput) {
            toggleIcon.addEventListener('click', () => {
                const isVisible = passwordInput.type === 'text';
                passwordInput.type = isVisible ? 'password' : 'text';
                toggleIcon.querySelector('i').classList.toggle('fa-eye');
                toggleIcon.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    });
</script>
