<div class="p-4">
    <div class="overflow-hidden border rounded-lg">
        <table class="w-full">
            <tbody class="divide-y">
                <tr class="bg-gray-50">
                    <td class="px-6 py-4 font-medium whitespace-nowrap">Kick Off Meeting Date</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $kickOffDate }}</td>
                </tr>
                <tr>
                    <td class="px-6 py-4 font-medium whitespace-nowrap">Buffer License</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $bufferLicense }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="px-6 py-4 font-medium whitespace-nowrap">Paid License</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $paidLicense }}</td>
                </tr>
                <tr>
                    <td class="px-6 py-4 font-medium whitespace-nowrap">Year Purchase</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $yearPurchase }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="px-6 py-4 font-medium whitespace-nowrap">Next Renewal</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $nextRenewal }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
