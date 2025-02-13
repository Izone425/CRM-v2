<div x-data="rankingForm({{ json_encode($users) }})" class="ranking-container">
    <style>
        .ranking-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Two columns of equal width */
            gap: 20px;
            /* Space between columns and rows */
            max-width: 600px;
            /* Adjust as needed */
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
            grid-column: span 2;
            /* Takes full width */
        }

        .submit-button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            grid-column: span 2;
            /* Spans both columns */
        }
    </style>
    <!-- Left Column -->
    <div class="column">
        <template x-for="rank in leftRanks" :key="rank">
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

    <!-- Right Column -->
    <div class="column">
        <template x-for="rank in rightRanks" :key="rank">
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
                users: users, // Array of user objects: each should have { id, name }
                // Create ranking numbers from 1 up to the number of users.
                rankingNumbers: Array.from({
                    length: users.length
                }, (_, i) => i + 1),
                rankings: {}, // Object to hold the selected user id for each rank

                // Left column: first half of ranking numbers.
                get leftRanks() {
                    const half = Math.ceil(this.rankingNumbers.length / 2);
                    return this.rankingNumbers.slice(0, half);
                },
                // Right column: remaining ranking numbers.
                get rightRanks() {
                    const half = Math.ceil(this.rankingNumbers.length / 2);
                    return this.rankingNumbers.slice(half);
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
