<div class="p-6">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Users</h3>
        <p class="text-sm text-gray-500">All users associated with this company account</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">No</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Backend User Id</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Full Name</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Login Id</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Attendance">TA</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Leave">TL</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Claim">TC</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Payroll">TP</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Onboarding & Offboarding">TO</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Recruitment">TR</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Appraisal">TAP</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="cursor-help border-b border-dashed border-gray-400" title="TimeTec Training">TT</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $index => $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $user['backend_user_id'] }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $user['full_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $user['login_id'] }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded {{ $user['role'] === 'OWNER' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $user['role'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded {{ $user['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user['status'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['ta'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tl'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tc'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tp'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['to'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tr'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tap'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tt'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <a href="#" class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                                    Login as User
                                </a>
                                @if(($user['status'] ?? '') === 'Inactive')
                                    <button class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700">
                                        Enable
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="px-4 py-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p class="mt-2">No users found for this company</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-sm text-gray-500">
        Total Users: <span class="font-semibold">{{ $users->count() }}</span>
    </div>
</div>
