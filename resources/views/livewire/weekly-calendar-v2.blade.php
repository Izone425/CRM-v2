<div>
    <div style="display:flex; flex-direction: row;margin-bottom:1rem;margin-left:2rem">
<button class="hover-button {{ $disablePrevWeek ? 'disabled' : '' }}" wire:click="prevWeek" style="margin-inline:1rem"
            {{ $disablePrevWeek ? 'disabled' : '' }}><i class="fa-solid fa-chevron-left"></i></button>
        <button class="hover-button" wire:click="nextWeek" style="margin-inline:1rem"><i
                class="fa-solid fa-chevron-right"></i></button>
        <div style="display:flex;margin-inline: 1rem">
            <div style="width: 30px;background-color: #71eb71;margin-right:0.5rem"></div>
            <div>NEW DEMO</div>
        </div>

        <div style="display:flex;margin-inline: 1rem">
            <div style="width: 30px;background-color:#ffff5cbf;margin-right:0.5rem"></div>
            <div>WEBINAR DEMO</div>
        </div>

        <div style="display:flex;margin-inline: 1rem">
            <div style="width: 30px;;background-color:#f86f6f;margin-right:0.5rem"></div>
            <div>OTHERS DEMO</div>
        </div>

        <div style="display:flex;margin-inline: 1rem">
            <div style="width:30px;height:100%;background-color:grey;margin-right:0.5rem"></div>
            <div>ON LEAVE & PUBLIC HOLIDAY</div>
        </div>


    </div>
    <div class="small" style="display:flex; flex-direction:row;justify-content: space-around;">
        <div style="display:flex;width:45%;flex-direction:column;">
            @foreach (array_slice($tableArray, 0, 4) as $key => $value)
                <x-weekly-calendar.table :tableData="$tableArray[$key]" />
            @endforeach
        </div>
        <div style="display:flex;width:45%;flex-direction:column;">
            @foreach (array_slice($tableArray, 4, 4) as $key => $value)
                <x-weekly-calendar.table :tableData="$tableArray[$key + 4]" />
            @endforeach
        </div>
        <style>
            .small {
                font-size: 0.9rem;
            }

            .hover-button {
                color: black;

                /* Tailwind's blue-500 */
                transition: color 0.3s;
            }

            .hover-button:hover {
                color: #3b82f6;
            }

            .hover-button.disabled {
                /* light grey */
                color: #888888;
                cursor: not-allowed;
            }

            @media screen and (max-width: 1400px) {

                /* Target the elements you want to change font size for */
                .small {
                    font-size: 0.7rem;
                }
            }
        </style>

        @if ($modalOpen)
            <x-weekly-calendar.table-modal-v2 :modalArray="$modalArray" />
        @endif
    </div>

</div>
