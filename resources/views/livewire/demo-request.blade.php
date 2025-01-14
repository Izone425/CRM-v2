<div class="container my-5" style="font-size: 12px;">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/css/intlTelInput.css">
    {{-- <link href="{{ asset('demoRequest.css') }}" rel="stylesheet" id="bootstrap-css"> --}}
    <div class="row justify-content-center">
        <div class="col-md-9" style="width: 520px;">
            <div class="card" style='width: 500px;'>
                <div class="card-body">
                    <form wire:submit.prevent="submit">
                        <h6 style="text-align: center;">Lead Details</h6>

                        <div class="mb-3 form-group">
                            <label for="name" class="form-label" style="font-weight:bold">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" wire:model.defer="name" required>
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group">
                            <label for="email" class="form-label" style="font-weight:bold">Work Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" wire:model.defer="email" required>
                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group">
                            <label for="phone" class="form-label" style="font-weight:bold">Phone <span class="text-danger">*</span></label><br>
                            <input class="form-control" style='width: 465px;' id="phone" type="tel" name="phone"/>
                            <div id="error-msg" style="color: red;"></div>
                                @error('phone') <span style="color: red; font-size: 0.875rem; font-weight: bold;">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group">
                            <label for="company_name" class="form-label" style="font-weight:bold">Company Name <span class="text-danger">*</span></label>
                            <input type="text" id="company_name" name="company_name" class="form-control" wire:model.defer="company_name" required>
                            @error('company_name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group">
                            <label for="company_size" class="form-label" style="font-weight:bold">Company Size <span class="text-danger">*</span></label>
                            <select id="company_size" name="company_size" class="form-control" wire:model.defer="company_size" required>
                                <option value="" disabled selected>-Select-</option>
                                <option value="1-24">1 - 24</option>
                                <option value="25-99">25 - 99</option>
                                <option value="100-500">100 - 500</option>
                                <option value="501 and Above">501 and Above</option>
                            </select>
                            @error('company_size') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group">
                            <label for="country" class="form-label" style="font-weight:bold">Country <span class="text-danger">*</span></label>
                            <select id="country" name="country" class="form-control" wire:model.defer="country" required>
                                <option value="" disabled selected>-Select-</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country['Code'] }}">{{ $country['Country'] }}</option>
                                @endforeach
                            </select>
                            @error('country') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group">
                            <label class="form-label" style="font-weight:bold">TimeTec Products <span class="text-danger">*</span></label>
                            <div style="font-size: 15px; padding-left: 20px;">
                                <label><input type="checkbox" wire:model.defer="products" value="hr"> HR (Attendance, Leave, Claim, Payroll, Hire, Profile)</label><br>
                                <label><input type="checkbox" wire:model.defer="products" value="property_management"> Property Management (Neighbour, Accounting)</label><br>
                                <label><input type="checkbox" wire:model.defer="products" value="smart_parking"> Smart Parking Management (Cashless, LPR, Valet)</label><br>
                                <label><input type="checkbox" wire:model.defer="products" value="security_people_flow"> Security & People Flow (Visitor, Access, Patrol, IoT)</label><br>
                                <label><input type="checkbox" wire:model.defer="products" value="merchants"> i-Merchants (Near Field Commerce, Loyalty Program)</label><br>
                                <label><input type="checkbox" wire:model.defer="products" value="smart_city"> Smart City</label>
                            </div>
                            @error('products') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 form-group" style= 'padding-left: 85px;'>
                            <div class="g-recaptcha" data-sitekey="6LcSPpkqAAAAAATZffKv1qh1zB0yLcvl6KEi9y7m"></div>
                            <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
                        </div>

                        <div class="text-center form-group">
                            <button type="submit" class="btn btn-primary btn-lg btn-form" style="font-size: 18px;">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/intlTelInput.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/utils.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Make sure the #phone input element exists before proceeding
    var input = document.querySelector("#phone");
    if (!input) {
        console.error("Phone input field with ID 'phone' not found.");
        return;
    }

    var errorMap = [
        "Invalid number", // Error code 0
        "Invalid country code", // Error code 1
        "Too short", // Error code 2
        "Too long", // Error code 3
        "Invalid number" // Error code 4
    ];
    var errorMsg = document.querySelector("#error-msg"); // Ensure error element exists
    if (!errorMsg) {
        console.error("Error message element with ID 'error-msg' not found.");
    }

    // GeoIP lookup function (Optional)
    function getIp(callback) {
        fetch("https://ipinfo.io", {
            headers: {
                Accept: "application/json"
            }
        })
        .then((resp) => resp.json())
        .catch(() => ({}))
        .then((resp) => callback(resp.country));
    }

    // Initialize intl-tel-input
    var iti = intlTelInput(input, {
        separateDialCode: true,
        initialCountry: "my",
        geoIpLookup: getIp,
        nationalMode: false,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.15/js/utils.js",
    });

    // Format phone number on keyup/change
    input.addEventListener("keyup", function() {
        if (iti.isValidNumber()) {
            // Clear any previous error message if the number is valid
            if (errorMsg) {
                errorMsg.textContent = 'Valid';
                errorMsg.style.color = 'green';
            }
        } else {
            // Display error message from the errorMap if the number is invalid
            if (errorMsg) {
                var errorCode = iti.getValidationError();
                errorMsg.textContent = errorMap[errorCode] || 'Invalid phone number';
                errorMsg.style.color = 'red';
            }
        }
    });

    function validateAndDispatch() {
            if (iti.isValidNumber()) {
                var phoneNumber = iti.getNumber(); // Get the full phone number with the dial code
                // Dispatch the phone number to Livewire
                Livewire.dispatch('updatePhone', [phoneNumber]);
            }
        }

    // Add event listener to the submit button
    document.querySelector(".btn-form").addEventListener("click", function() {
            validateAndDispatch(); // Call the function on button click
        });
});

</script>

