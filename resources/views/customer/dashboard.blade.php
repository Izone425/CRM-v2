<!-- filepath: /var/www/html/timeteccrm/resources/views/customer/dashboard.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Customer Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="mb-4 text-2xl font-semibold">Welcome, {{ Auth::guard('customer')->user()->name }}!</h1>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <!-- Recent Quotes -->
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow">
                            <h2 class="mb-2 text-lg font-semibold">Recent Quotes</h2>
                            <ul class="divide-y divide-gray-200">
                                <li class="py-2">Quote #12345 - $1,234.56</li>
                                <li class="py-2">Quote #12346 - $2,345.67</li>
                            </ul>
                        </div>

                        <!-- Account Info -->
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow">
                            <h2 class="mb-2 text-lg font-semibold">Account Information</h2>
                            <div class="space-y-2">
                                <p><span class="font-medium">Company:</span> {{ Auth::guard('customer')->user()->company_name }}</p>
                                <p><span class="font-medium">Email:</span> {{ Auth::guard('customer')->user()->email }}</p>
                                <p><span class="font-medium">Phone:</span> {{ Auth::guard('customer')->user()->phone }}</p>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow">
                            <h2 class="mb-2 text-lg font-semibold">Quick Actions</h2>
                            <div class="space-y-2">
                                <a href="#" class="block w-full px-4 py-2 text-center text-white bg-indigo-600 rounded hover:bg-indigo-700">View Quotes</a>
                                <a href="#" class="block w-full px-4 py-2 text-center text-indigo-700 bg-indigo-100 rounded hover:bg-indigo-200">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
