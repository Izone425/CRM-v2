{{-- resources/views/companies/show.blade.php --}}
<div>
    <x-filament::tabs :active="$this->getActiveTab()">
        <x-filament::tab name="leads">
            <h2>Leads</h2>
            <!-- Your leads content here -->
        </x-filament::tab>

        <x-filament::tab name="company">
            <h2>Company Details</h2>
            <!-- Example Company Content -->
        </x-filament::tab>

        <x-filament::tab name="system">
            <h2>System</h2>
            <!-- Your system content here -->
        </x-filament::tab>

        <x-filament::tab name="refer_earn">
            <h2>Refer & Earn</h2>
            <!-- Your refer & earn content here -->
        </x-filament::tab>

        <x-filament::tab name="appointment">
            <h2>Appointment</h2>
            <!-- Your appointment content here -->
        </x-filament::tab>

        <x-filament::tab name="prospect_follow_up">
            <h2>Prospect Follow Up</h2>
            <!-- Your prospect follow-up content here -->
        </x-filament::tab>
    </x-filament::tabs>
</div>
