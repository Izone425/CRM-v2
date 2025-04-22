<style>
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.25);
    }

    .modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border-radius: 10px;
        width: 80%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .close-button {
        float: right;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
    }

    .hover-text {
        color: #3b82f6;
        /* Tailwind's blue-500 */
        transition: color 0.3s;
    }

    .hover-text:hover {
        color: black;
    }
</style>
<div class="modal">
    <div class="modal-content">
        <span class="close-button" wire:click="$set('modalOpen',false)">&times;</span>
        <h2 style="font-weight:bold">Company</h2>
        @foreach ($modalArray as $value)
            <div class="cursor-pointer hover-text">
                <a target="_blank" rel="noopener noreferrer" href={{ $value['url'] }}>
                    {{ $value['company_name'] }}
                </a>
            </div>
        @endforeach

        <h2 style="font-weight:bold">Demo Type</h2>
        <div>{{ $value['type'] }}</div>
    </div>
</div>
