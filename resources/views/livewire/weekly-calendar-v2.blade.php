<div class="small" style="display:flex; flex-direction:row; 1rem; justify-content: space-around;">
    <div style="display:flex;width:45%;flex-direction:column;">
        @foreach (array_slice($tableArray, 0, 4) as $key=>$value)
            <x-weekly-calendar.table :tableData="$tableArray[$key]" />
        @endforeach
    </div>
    <div style="display:flex;width:45%;flex-direction:column;">
        @foreach (array_slice($tableArray, 4, 4) as $key=>$value)
            <x-weekly-calendar.table :tableData="$tableArray[$key+4]" />
        @endforeach
    </div>
    <style>
        .small{
            font-size: 0.9rem;
        }

        @media screen and (max-width: 1400px) {
          /* Target the elements you want to change font size for */
          .small{
            font-size: 0.7rem;
          }
        }

        </style>

    @if($modalOpen)
        <x-weekly-calendar.table-modal-v2 :modalArray="$modalArray" />
    @endif
</div>

