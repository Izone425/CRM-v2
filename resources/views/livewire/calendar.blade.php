<div
     style="height: 100%;   background-color: #ffffff; border-radius: 17px; box-shadow: 0px 2px 4px rgba(0,0,0,0.08); display: flex; flex-direction: column; position: relative;">
     <style>
          :root {
               --bar-color-blue: #4f46ba;
               --bar-color-orange: #ff9500;
          }

          .image-container {
               width: 50px;
               /* Set the container width */
               height: 50px;
               /* Set the container height */
               background-color: grey;
               /* Grey background for placeholder */
               border-radius: 50px;
               /* Rounded corners */
               flex-shrink: 0;
          }

          /* ||  Calendar CSS ||  START*/
          .appointment-card {
               margin-block: 0.5rem;
               width: 100%;
               background-color: rgba(252, 158, 162, 0.2);
               display: flex;
               flex-direction: row;
          }

          .appointment-card-bar {
               background-color: var(--bar-color-blue);
               width: 12px;
          }

          .appointment-card-info {
               display: flex;
               flex: 1;
               flex-direction: column;
               padding-block: 0.25rem;
               padding-inline: 0.5rem;
          }


          table {
               width: 100%;
               border-collapse: collapse;
          }

          td {
               padding: 10px;
               border: 1px solid #e5e7eb;
          }

          .first-column {
               width: 15%;
               /* Adjust the width of the first column */
          }

          .other-columns {
               width: 12.143%;
               /* Adjust width of the other columns */
               vertical-align: top;
          }

          .other-columns.invisible {
               padding: 0;
          }

          .flex-container {
               display: flex;
               width: 100%;
               height: 100%;
               align-items: center;
               justify-content: center;
               gap: 0.1rem;
               text-align: center;
          }

          .scroll td {
               border: none;
          }

          .scroll .other-columns {
               vertical-align: middle;
               position: relative;
          }

          /* || FINISH || */

          /* Scroll */
          select.scroll-dropdown {
               outline: 0;
               /* Removes the blue ring */
               border: none;
               /* Optional: Add your custom border */
               border-radius: 4px;
               /* Optional: Add rounded corners */
               font-size: 28px;
               font-weight: bold;
               width: 100%;
          }

          select.scroll-dropdown:focus {
               border-color: pink;
               /* Optional: Customize the border on focus */
               outline: 0;
          }

          select.scroll-dropdown option {
               border-color: #007bff;
               padding: 1rem;
          }

          .spinner {
               border: 10px solid #f3f3f3;
               /* Light gray background */
               border-top: 10px solid #3498db;
               /* Blue color for the spinner */
               border-radius: 50%;
               width: 100px;
               height: 100px;
               animation: spin 1s linear infinite;
               margin-block: auto;
          }

          /* Spinner animation */
          @keyframes spin {
               0% {
                    transform: rotate(0deg);
               }

               100% {
                    transform: rotate(360deg);
               }
          }

          /* The overlay that covers the content */
          .overlay {
               position: absolute;
               top: 0;
               left: 0;
               right: 0;
               bottom: 0;
               background-color: rgba(236, 240, 241, 0.5);
               /* Semi-transparent grey */
               display: flex;
               justify-content: center;
               /* Center horizontally */
               align-items: center;
               /* Center vertically */
               color: white;
               z-index: 9999;
               /* Make sure it appears on top */
               height: 100%;
               width: 100%;
          }

          .demo-avatar {
               display: grid;
               grid-template-columns: repeat(5, 1fr);
               row-gap: 5px;
               column-gap: 2px;
          }

          .demo-avatar img {
               border-radius: 50%;
          }

          /* For 1280 */
          @media (max-width: 1400px) {
               body {
                    font-size: 0.9rem;
               }

               .appointment-card-info {
                    font-size: 0.7rem;
               }

               .image-container {
                    width: 2rem;
                    /* Set the container width */
                    height: 2rem;
                    /* Set the container height */
               }

               .demo-avatar {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    row-gap: 5px;
                    column-gap: 3px;


               }

               .demo-avatar img {
                    width: 100%;
                    height: auto;
               }

          }
     </style>

     <!-- Loading overlay (this will show while Livewire is processing) -->
     <div class="overlay" wire:loading.flex>
          <div class="spinner"></div>
     </div>

     <table class="scroll">
          <tr>
               <td class="first-column"><!-- Demo Appointment Type Filter -->
                    <div>
                         <form>
                              <select class="scroll-dropdown">
                                   <option value="">Jan '24</option>
                              </select>
                         </form>
                    </div>
               </td>
               <td class="other-columns text-center">
                    <div class="flex">
                         <button wire:click="prevWeek" style="width: 10%;"><i
                                   class="fa-solid fa-chevron-left"></i></button>
                         <span class="flex-1" @if($weekDays[0]['today']) style="background-color: lightblue;" @endif>
                              <span class="block text-center" style="font-size: 24px;">{{$weekDays[0]['date']}}</span>
                              <span class="block">{{$weekDays[0]['day']}}</span>
                         </span>
                    </div>

               </td>
               <td class="other-columns text-center" @if($weekDays[1]['today']) style="background-color: lightblue;"
                    @endif>
                    <span class="block" style="font-size: 24px;">{{$weekDays[1]['date']}}</span>
                    <span class="block">{{$weekDays[1]['day']}}</span>
               </td>
               <td class="other-columns text-center" @if($weekDays[2]['today']) style="background-color: lightblue;"
                    @endif>
                    <span class="block" style="font-size: 24px;">{{$weekDays[2]['date']}}</span>
                    <span class="block">{{$weekDays[2]['day']}}</span>
               </td>
               <td class="other-columns text-center" @if($weekDays[3]['today']) style="background-color: lightblue;"
                    @endif>
                    <span class="block" style="font-size: 24px;">{{$weekDays[3]['date']}}</span>
                    <span class="block">{{$weekDays[3]['day']}}</span>
               </td>

               <td class="other-columns text-center" @if($weekDays[4]['today']) style="background-color: lightblue;"
                    @endif>
                    <span class="block" style="font-size: 24px;">{{$weekDays[4]['date']}}</span>
                    <span class="block">{{$weekDays[4]['day']}}</span>
               </td>
               <td class="other-columns text-center" @if($weekDays[5]['today']) style="background-color: lightblue;"
                    @endif>
                    <span class="block" style="font-size: 24px;">{{$weekDays[5]['date']}}</span>
                    <span class="block">{{$weekDays[5]['day']}}</span>
               </td>
               <td class="other-columns text-center" style="text-align: center;">
                    <div class="flex">
                         <div class="flex-1" @if($weekDays[6]['today']) style="background-color: lightblue;" @endif>
                              <span class="block" style="font-size: 24px;">{{$weekDays[6]['date']}}</span>
                              <span class="block">{{$weekDays[6]['day']}}</span>
                         </div>
                         <button wire:click="nextWeek" style="width: 10%;"><i
                                   class="fa-solid fa-chevron-right"></i></button>
                    </div>
               </td>

          </tr>
     </table>

     <div>
          <div class=" w-full h-16" style="background-color: #F6F8FF"></div>
          <table style="background-color: #F6F8FF">
               <tr>
                    <td class="first-column">No New Demo</td>
                    @foreach (['mondayNewDemo', 'tuesdayNewDemo', 'wednesdayNewDemo', 'thursdayNewDemo',
                    'fridayNewDemo', 'saturdayNewDemo', 'sundayNewDemo'] as $day)
                    <td class="other-columns">
                         <div class="demo-avatar">
                              @foreach ($rows as $salesperson)
                              @if ($salesperson[$day] == 0)
                              <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                              @endif
                              @endforeach
                         </div>
                    </td>
                    @endforeach
               </tr>
               <tr>

                    <td class="first-column">One New Demo</td>
                    @foreach (['mondayNewDemo', 'tuesdayNewDemo', 'wednesdayNewDemo', 'thursdayNewDemo',
                    'fridayNewDemo', 'saturdayNewDemo', 'sundayNewDemo'] as $day)
                    <td class="other-columns">
                         <div class="demo-avatar">
                              @foreach ($rows as $salesperson)
                              @if ($salesperson[$day] == 1)
                              <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                              @endif
                              @endforeach
                         </div>
                    </td>
                    @endforeach
               </tr>
               <tr>
                    <td class="first-column">On Leave</td>
                    <td class="other-columns">Hello</td>
                    <td class="other-columns">Hello</td>
                    <td class="other-columns">Hello</td>
                    <td class="other-columns">Hello</td>
                    <td class="other-columns">Hello</td>
                    <td class="other-columns">Hello</td>
                    <td class="other-columns">Hello</td>
               </tr>
          </table>
     </div>

     <table style="position: relative; height: fit-content;">
          <tr>
               <td class="first-column" style="padding: 0; border: 0;">
               </td>

               @if(!empty($holidays))
               @foreach($holidays as $row)
               <td class="other-columns" style="padding: 0;  border: 0;">
                    <div
                         style="border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; background-color: #C2C2C2; position: absolute; left: calc(15% + (12.143% * {{$row['day_of_week']-1}})); top: 0; height: 100%; width: 12.143%;">
                         <div>
                              <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
                              <div style="font-size: 0.8rem;">{{$row['name']}}</div>
                         </div>
                    </div>
               </td>
               @endforeach
               @endif
          </tr>
          @foreach($rows as $key=>$value)
          <tr @if($loop->even) style="background-color: rgba(242,242,242,0.4);" @endif>
               <td class="first-column">
                    <div class="flex-container">
                         <div class="image-container"><img style="border-radius: 50%;"
                                   src="{{$value['salespersonAvatar']}}"></div>
                         <span style="flex: 1;">{{$value['salespersonName']}}</span>
                    </div>
               </td>
               <td class="other-columns" style="height: 100%;">
                    @if(isset($value['leave'][1]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][1]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['mondayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
               <td class="other-columns">
                    @if(isset($value['leave'][2]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][2]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['tuesdayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
               <td class="other-columns">
                    @if(isset($value['leave'][3]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][3]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['wednesdayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
               <td class="other-columns">
                    @if(isset($value['leave'][4]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][4]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['thursdayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
               <td class="other-columns">
                    @if(isset($value['leave'][5]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][5]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['fridayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
               <td class="other-columns">
                    @if(isset($value['leave'][6]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][6]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['saturdayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
               <td class="other-columns">
                    @if(isset($value['leave'][7]))
                    <div
                         style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                         <div style="flex:1; text-align: center;">
                              <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                              <div style="font-size: 0.8rem;">{{$value['leave'][7]['leave_type']}}</div>
                         </div>
                    </div>
                    @else
                    @foreach($value['sundayAppointments'] as $appointment)
                    <div class="appointment-card">
                         <div class="appointment-card-bar"></div>
                         <div class="appointment-card-info">
                              <span class="appointment-card-title">{{$appointment->title}}</span>
                              <span class="appointment-card-type">{{$appointment->type}}</span>
                              <span class="appointment-card-time">{{$appointment->start_time}} -
                                   {{$appointment->end_time}}</span>
                         </div>
                    </div>
                    @endforeach
                    @endif
               </td>
          </tr>
          @endforeach
     </table>


</div>