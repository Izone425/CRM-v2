<div id="userListContainer">
    <div wire:ignore>
        <ul id="userList">
            @foreach ($users as $index => $user)
            <li class="user-item" data-id="{{ $user['id'] }}">
                <div class="rank-container">
                    <div class="rank">{{ $index + 1 }}</div>
                </div>
                <div class="draggable-box">
                    <div class="avatar"><img src="{{ $user['avatarPath'] }}"></div>
                    <span>{{ $user['name'] }}</span>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

    @if (session()->has('error'))
    <div class="error-message">{{ session('error') }}</div>
    @endif

    <div wire:loading wire:target="updateRankings" class="loading-indicator">
        <span>Saving rankings...</span>
    </div>
    @script
    <script>
        const el = document.getElementById('userList');

        // Function to update all rank numbers visually
        function updateRanks() {
            const items = Array.from(el.children);
            items.forEach((item, index) => {
                item.querySelector('.rank').textContent = index + 1;
            });
        }

        // Initialize Sortable
        Sortable.create(el, {
            animation: 150
            , handle: '.draggable-box'
            , onEnd: function() {
                // Update the rank numbers after drag
                updateRanks();

                // Save the new order to the database via Livewire
                const orderedIds = Array.from(el.children).map(li => li.dataset.id);
                @this.call('updateRankings', orderedIds);
            }
        });

        // Listen for ranking updated event
        Livewire.on('rankingUpdated', () => {
            // You could add a toast notification here
            console.log('Rankings updated successfully!');
        });

    </script>
    @endscript
    
    <style>
        #userListContainer {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }

        #userList {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 5px;
        }

        .rank-container {
            width: 36px;
            display: flex;
            justify-content: center;
            flex-shrink: 0;
        }

        .rank {
            width: 28px;
            height: 28px;
            text-align: center;
            font-weight: bold;
            color: #fff;
            background-color: #431fa1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .draggable-box {
            display: flex;
            align-items: center;
            background-color: white;
            padding: 8px 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: grab;
            transition: background 0.2s;
            flex-grow: 1;
        }

        .draggable-box:hover {
            background-color: #f9f9f9;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background-color: #3498db;
            border-radius: 50%;
            margin-right: 12px;
            object-fit:cover;
            flex-shrink: 0;

        }
        .avatar img {
            /* max-width: 40px; */
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        .user-item span {
            font-size: 15px;
            font-weight: 500;
        }

        .error-message {
            color: #e74c3c;
            margin-top: 10px;
            padding: 8px;
            background-color: #fadbd8;
            border-radius: 4px;
            text-align: center;
        }

        .loading-indicator {
            display: none;
            text-align: center;
            padding: 10px;
            color: #3498db;
        }

        .loading-indicator[style*="display: block"] {
            display: block !important;
        }

    </style>
    </div>
