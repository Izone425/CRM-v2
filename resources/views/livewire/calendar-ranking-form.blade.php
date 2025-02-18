<div x-data="rankingForm({{ json_encode($users) }})" class="ranking-container">
    <style>
        .ranking-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* Four columns */
            gap: 20px;
            max-width: 90%; /* Adjust as needed */
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }

        .column {
            /* Each column will contain the select fields */
        }

        .rank-field {
            margin-bottom: 20px;
        }

        .rank-field label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .rank-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .error-message {
            color: red;
            margin-bottom: 20px;
            grid-column: span 4; /* Takes full width across 4 columns */
        }

        .submit-button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            grid-column: span 4; /* Spans all four columns */
        }
    </style>

    <!-- Column 1 -->
    <div class="column">
        <template x-for="rank in column1" :key="rank">
            <div class="rank-field">
                <label x-text="'Rank ' + rank"></label>
                <select x-model="rankings[rank]">
                    <option value="">Select User</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.name"
                            :disabled="isUserSelected(user.id) && rankings[rank] != user.id">
                        </option>
                    </template>
                </select>
            </div>
        </template>
    </div>

    <!-- Column 2 -->
    <div class="column">
        <template x-for="rank in column2" :key="rank">
            <div class="rank-field">
                <label x-text="'Rank ' + rank"></label>
                <select x-model="rankings[rank]">
                    <option value="">Select User</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.name"
                            :disabled="isUserSelected(user.id) && rankings[rank] != user.id">
                        </option>
                    </template>
                </select>
            </div>
        </template>
    </div>

    <!-- Column 3 -->
    <div class="column">
        <template x-for="rank in column3" :key="rank">
            <div class="rank-field">
                <label x-text="'Rank ' + rank"></label>
                <select x-model="rankings[rank]">
                    <option value="">Select User</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.name"
                            :disabled="isUserSelected(user.id) && rankings[rank] != user.id">
                        </option>
                    </template>
                </select>
            </div>
        </template>
    </div>

    <!-- Column 4 -->
    <div class="column">
        <template x-for="rank in column4" :key="rank">
            <div class="rank-field">
                <label x-text="'Rank ' + rank"></label>
                <select x-model="rankings[rank]">
                    <option value="">Select User</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.name"
                            :disabled="isUserSelected(user.id) && rankings[rank] != user.id">
                        </option>
                    </template>
                </select>
            </div>
        </template>
    </div>

    <!-- Error Message -->
    <template x-if="hasDuplicates">
        <div class="error-message">
            Duplicate user selections are not allowed!
        </div>
    </template>

    <!-- Submit Button -->
    <button type="button" @click="submitForm()" class="submit-button">
        Submit Rankings
    </button>

    <script>
        function rankingForm(users) {
            return {
                users: users, // Array of user objects: { id, name }
                // Create ranking numbers from 1 up to the number of users.
                rankingNumbers: Array.from({ length: users.length }, (_, i) => i + 1),
                rankings: {}, // Object to hold the selected user id for each rank

                // Calculate the size of each column (quarter of total ranking numbers).
                get quarter() {
                    return Math.ceil(this.rankingNumbers.length / 4);
                },
                // Split the ranking numbers into 4 columns.
                get column1() {
                    return this.rankingNumbers.slice(0, this.quarter);
                },
                get column2() {
                    return this.rankingNumbers.slice(this.quarter, this.quarter * 2);
                },
                get column3() {
                    return this.rankingNumbers.slice(this.quarter * 2, this.quarter * 3);
                },
                get column4() {
                    return this.rankingNumbers.slice(this.quarter * 3);
                },
                // Check if a user id is already selected in any rank (excluding the current one).
                isUserSelected(userId) {
                    const selected = Object.values(this.rankings).filter(val => val);
                    return selected.includes(userId);
                },
                // Check for duplicate selections.
                get hasDuplicates() {
                    const selected = Object.values(this.rankings).filter(val => val);
                    return new Set(selected).size !== selected.length;
                },
                // Submit handler.
                submitForm() {
                    if (this.hasDuplicates) {
                        alert("Please fix duplicate selections before submitting.");
                        return;
                    }
                    console.log("Submitting rankings:", this.rankings);
                    // Place your form submission logic here (e.g., send an AJAX request).
                }
            }
        }
    </script>
</div>
