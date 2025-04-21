<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <div class="flex items-center mb-2 space-x-6">
        <!-- 🔘 Checkbox: Show Unreplied Only -->
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" wire:model="filterUnreplied" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <span class="text-sm text-gray-700">&nbsp;&nbsp;Show Unreplied Only</span>
        </label>
        &nbsp;&nbsp;
        <!-- 🧑‍💼 Dropdown: Filter by Lead Owner -->
        <div>
            <select wire:model="selectedLeadOwner"
                class="mt-1 border-gray-300 rounded-md shadow-sm">
                <option value="">All Lead Owners</option>
                @foreach(\App\Models\User::where('role_id', 1)->pluck('name', 'name') as $name => $nameLabel)
                    <option value="{{ $name }}">{{ $nameLabel }}</option>
                @endforeach
            </select>
        </div>
        &nbsp;&nbsp;
        <div class="flex items-center ml-10 space-x-4">
            <div>
                <input wire:model="startDate" type="date" id="startDate" class="mt-1 border-gray-300 rounded-md shadow-sm" />
            </div>
            &nbsp;- &nbsp;
            <div>
                <input wire:model="endDate" type="date" id="endDate" class="mt-1 border-gray-300 rounded-md shadow-sm" />
            </div>
        </div>
        &nbsp;&nbsp;
        <div>
            <input
                type="text"
                wire:model.debounce.500ms="searchCompany"
                placeholder="Search company name..."
                class="mt-1 border-gray-300 rounded-md shadow-sm"
            />
        </div>
    </div>
    <div class="flex h-screen bg-white border border-gray-200 rounded-lg">
        <!-- Left Sidebar - Chat List -->
        <div class="border-r bg-gray-50" style="width: 300px;" wire:poll.1s>
            <div class="p-4 bg-white border-b">
                <h2 class="text-lg font-semibold">Chats</h2>
            </div>

            <!-- 🔽 Scrollable area -->
            <div style="overflow-y: auto; height: calc(100vh - 9rem);">
                @foreach($this->fetchContacts() as $contact)
                    <div wire:click="selectChat('{{ $contact->user1 }}', '{{ $contact->user2 }}')"
                        class="p-4 border-b cursor-pointer hover:bg-gray-50 {{ $selectedChat === $contact->participant_name ? 'bg-blue-50' : '' }}">

                        <div class="flex items-center justify-between">
                            <!-- 👤 Name on the left -->
                            <span class="font-medium text-gray-900 truncate">
                                {{ \Illuminate\Support\Str::limit($contact->participant_name, 20, '...') }}
                            </span>

                            <!-- 🔴 Red dot + Timestamp on the right -->
                            <div class="flex items-center gap-2">
                                @if($contact->is_from_customer && ($contact->is_read === null || $contact->is_read == false))
                                    <span class="text-xl font-bold" style="color:red;">&#x25CF;</span>
                                @endif

                                @if($contact->last_message_time)
                                    <span class="text-gray-500" style= "font-size: 12px;">
                                        {{ \Carbon\Carbon::parse($contact->last_message_time)->format('d M, H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-gray-500 truncate">
                            <i class="fa {{ $contact->is_from_customer ? 'fa-reply' : 'fa-share' }}" aria-hidden="true"></i>
                            {{ \Illuminate\Support\Str::limit($contact->latest_message, 50, '...') }}
                        </div>
                    </div>
                @endforeach
                @php
                    $contacts = $this->fetchContacts();
                @endphp
                <!-- 👇 Show More Button -->
                @if ($contacts->count() >= $contactsLimit)
                <div class="flex justify-center p-4 bg-white border-t">
                    <button
                        wire:click="loadMoreContacts"
                        class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-gray-100 rounded hover:bg-gray-200"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading wire:target="loadMoreContacts">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                        <span>Show More</span>
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Middle Section - Chat Messages -->
        <div class="flex flex-col flex-1">
            @if($selectedChat)
                <!-- Chat Header -->
                <div class="p-4 bg-white border-b">
                    @php
                        $details = $this->fetchParticipantDetails();
                    @endphp
                    <h2 class="text-lg font-semibold">
                        {{ $selectedChat ? $details['name'] : 'Select a chat' }}
                    </h2>
                </div>

                <!-- Messages Container -->
                <div class="flex-1 p-4 space-y-4 overflow-y-auto bg-gray-100">
                    <!-- 👇 Loading spinner while switching chat -->
                    <div wire:loading wire:target="selectChat" class="text-center text-gray-500">
                        <i class="mr-2 fas fa-spinner fa-spin"></i> Loading messages...
                    </div>

                    <!-- 👇 Hide messages while loading -->
                    <div wire:loading.remove wire:target="selectChat">
                        @foreach($this->fetchMessages($selectedChat) as $message)
                            <!-- message bubble -->
                        @endforeach
                    </div>
                    @foreach($this->fetchMessages($selectedChat) as $message)
                        <div class="flex {{ $message->is_from_customer ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[70%] rounded-lg p-3
                                    {{ $message->is_from_customer ? 'bg-white' : 'bg-primary-600 text-white' }}">
                                @if ($message->media_url)
                                    @if (str_contains($message->media_type, 'image'))
                                        <!-- Display Image -->
                                        <img src="{{ $message->media_url }}" alt="Image Message" class="w-20 h-20 rounded-lg">
                                    @elseif (str_contains($message->media_type, 'audio'))
                                        <!-- Show an Audio Player for Voice Messages -->
                                        <div class="relative flex items-center w-full p-3 bg-gray-200 rounded-lg shadow-md">
                                            <audio id="audio-{{ $message->id }}" class="hidden">
                                                <source src="{{ $message->media_url }}" type="{{ $message->media_type }}">
                                                Your browser does not support the audio element.
                                            </audio>

                                            <!-- Play/Pause Button -->
                                            <button onclick="toggleAudio('audio-{{ $message->id }}', 'play-btn-{{ $message->id }}')" id="play-btn-{{ $message->id }}"
                                                class="flex items-center justify-center w-10 h-10 p-2 text-white bg-blue-500 rounded-full">
                                                <i class="fas fa-play"></i>
                                            </button>

                                            <!-- Progress Bar -->
                                            <div class="flex-1 mx-4">
                                                <input type="range" id="progress-{{ $message->id }}" value="0" step="0.1" class="w-full h-1 bg-gray-300 rounded-lg appearance-none cursor-pointer">
                                            </div>

                                            <!-- Time Display -->
                                            <span id="time-{{ $message->id }}" class="text-sm text-gray-600">00:00</span>
                                        </div>
                                    @elseif (str_contains($message->media_type, 'application') || str_contains($message->media_type, 'text'))
                                        <!-- Show Download Link for Files -->
                                        <a href="{{ $message->media_url }}" target="_blank" class="flex items-center text-blue-600 hover:underline">
                                            <i class="mr-2 fa fa-file-alt"></i>&nbsp; Download File
                                        </a>
                                    @else
                                        <!-- Show as a generic media message -->
                                        <a href="{{ $message->media_url }}" target="_blank" class="text-blue-600 hover:underline">
                                            [Media File]
                                        </a>
                                    @endif
                                @else
                                    <!-- Quoted Message Preview (if this is a reply) -->
                                    @if ($message->repliedMessage)
                                        <div class="p-2 mb-1 text-xs bg-gray-100 border-l-4 rounded border-primary-500">
                                            {{ $message->repliedMessage->message }}
                                        </div>
                                    @endif

                                    <!-- Actual Message Content -->
                                    <div class="text-sm">{!! nl2br(e($message->message)) !!}</div>
                                @endif
                                <div class="mt-1 text-xs opacity-70">
                                    {{ $message->created_at->format('M d, g:i A') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Message Input -->
                <div class="p-4 bg-white border-t">
                    <form
                        x-data="{
                            message: $wire.entangle('message').defer,
                            send() {
                                $wire.set('message', this.message).then(() => {
                                    $wire.sendMessage(); // ✅ Trigger Livewire method
                                });
                            },
                            clear() {
                                this.message = '';
                                this.$refs.textarea.style.height = 'auto';
                            }
                        }"
                        x-init="window.addEventListener('messageSent', () => clear())"
                        @submit.prevent="send"
                    >
                        @if (session()->has('error'))
                            <p class="mt-2 text-sm" style="color: red;">{{ session('error') }}</p>
                        @endif
                        <div class="flex items-center space-x-2">
                            <!-- File Upload Button -->
                            <label for="fileUpload" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-lg cursor-pointer hover:bg-gray-300">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="fileUpload" wire:model="file" class="hidden">
                            <!-- Text Input -->
                            <div wire:ignore class="flex items-center flex-1">
                                <textarea
                                    x-ref="textarea"
                                    x-model="message"
                                    @input="
                                        $refs.textarea.style.height = 'auto';
                                        $refs.textarea.style.height = $refs.textarea.scrollHeight + 'px';
                                    "
                                    class="w-full overflow-hidden border-gray-300 rounded-lg resize-none focus:border-primary-500 focus:ring-primary-500"
                                    style="min-height: 38px;"
                                    rows="1"
                                    placeholder="Type a message"
                                ></textarea>
                            </div>
                            <!-- Send Button -->
                            <button
                                type="submit"
                                class="flex items-center justify-center px-4 py-2 text-white rounded-lg bg-primary-500 hover:bg-primary-600"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                            >
                                <i class="mr-2 fas fa-spinner fa-spin" wire:loading wire:target="sendMessage"></i>
                                <span wire:loading.remove wire:target="sendMessage">Send</span>
                            </button>
                        </div>

                        <!-- Loading Indicator for File Upload -->
                        @if ($file)
                            <p class="mt-2 text-sm text-gray-500">Uploading: {{ $file->getClientOriginalName() }}</p>
                        @endif
                    </form>
                </div>
            @else
            <div wire:loading wire:target="selectChat" class="text-center text-gray-500">
                <i class="mr-2 fas fa-spinner fa-spin"></i> Loading messages...
            </div>

            <!-- 👇 Hide messages while loading -->
            <div wire:loading.remove wire:target="selectChat">
                @foreach($this->fetchMessages($selectedChat) as $message)
                    <!-- message bubble -->
                @endforeach
            </div>
            <!-- Empty State -->
                <div class="flex items-center justify-center flex-1 bg-gray-50">
                    <div class="text-center text-gray-500">
                        <div class="mb-2 text-xl font-medium">Select a chat</div>
                        <p class="text-sm">Choose a conversation from the list to start messaging</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Sidebar - Contact Details -->
        @if ($selectedChat)
        <div class="w-1/4 border-l shadow-md bg-gray-50" style="width: 300px !important;">

            <div class="flex items-center justify-between p-4 bg-white border-b">
                <h2 class="text-lg font-semibold text-gray-700">Contact Details</h2>
            </div>

            @php
                $details = $this->fetchParticipantDetails();
            @endphp

            <div class="p-6 space-y-4">
                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center space-x-4">
                        <i class="fa fa-search" aria-hidden="true"></i>&nbsp;&nbsp;
                        <p class="text-sm text-gray-500">Lead Status</p>
                    </div>
                    <p class="text-lg font-semibold text-gray-900">{{ $details['lead_status'] }}</p>
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center space-x-4">
                        <i class="fa fa-user-circle" aria-hidden="true"></i>&nbsp;&nbsp;
                        <p class="text-sm text-gray-500">Name</p>
                    </div>
                    <p class="text-lg font-semibold text-gray-900">{{ $details['name'] }}</p>
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center space-x-4">
                        <i class="fa fa-envelope" aria-hidden="true"></i>&nbsp;&nbsp;
                        <p class="text-sm text-gray-500">Email</p>
                    </div>
                    <p class="text-lg font-semibold text-gray-900" style="color:#338cf0;">
                        <a href="mailto:{{ $details['email'] }}" class="text-blue-600 hover:underline" title="{{ $details['email'] }}">
                            {{ \Illuminate\Support\Str::limit($details['email'], 25) }}
                        </a>
                    </p>
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center space-x-4">
                        <i class="fa fa-phone" aria-hidden="true"></i>&nbsp;&nbsp;
                        <p class="text-sm text-gray-500">Phone</p>
                    </div>
                    <p class="text-lg font-semibold text-gray-900">{{ $details['phone'] }}</p>
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center space-x-4">
                        <i class="fa fa-building" aria-hidden="true"></i>&nbsp;&nbsp;
                        <p class="text-sm text-gray-500">Company</p>
                    </div>
                    @if ($details['company_url'])
                        <p class="text-lg font-semibold" style="color:#338cf0;">
                            <a href="{{ $details['company_url'] }}" target="_blank" class="text-blue-600 hover:underline">
                                {{ $details['company'] }}
                            </a>
                        </p>
                    @else
                        <p class="text-lg font-semibold text-gray-900">{{ $details['company'] }}</p>
                    @endif
                </div>

                <div class="p-4 bg-white rounded-lg shadow">
                    <div class="flex items-center space-x-4">
                        <i class="fa fa-wifi" aria-hidden="true"></i>&nbsp;&nbsp;
                        <p class="text-sm text-gray-500">Source</p>
                    </div>
                    <p class="text-lg font-semibold text-gray-900">{{ $details['source'] }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- JavaScript for Custom Audio Player -->
    <script>
        function toggleAudio(audioId, buttonId) {
            let audio = document.getElementById(audioId);
            let button = document.getElementById(buttonId);
            let progressBar = document.getElementById('progress-' + audioId.split('-')[1]);
            let timeDisplay = document.getElementById('time-' + audioId.split('-')[1]);

            if (audio.paused) {
                audio.play();
                button.innerHTML = '<i class="fas fa-pause"></i>';
            } else {
                audio.pause();
                button.innerHTML = '<i class="fas fa-play"></i>';
            }

            // Update Progress Bar & Time Display
            audio.ontimeupdate = function () {
                let currentTime = Math.floor(audio.currentTime);
                let minutes = Math.floor(currentTime / 60);
                let seconds = currentTime % 60;
                timeDisplay.innerText = minutes + ":" + (seconds < 10 ? '0' : '') + seconds;
                progressBar.value = (audio.currentTime / audio.duration) * 100;
            };

            // Seek Audio on Progress Bar Change
            progressBar.oninput = function () {
                audio.currentTime = (this.value / 100) * audio.duration;
            };

            // Reset on Audio End
            audio.onended = function () {
                button.innerHTML = '<i class="fas fa-play"></i>';
                progressBar.value = 0;
                timeDisplay.innerText = '00:00';
            };
        }
    </script>
</x-filament::page>
