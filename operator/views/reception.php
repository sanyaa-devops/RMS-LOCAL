

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Inflight Dubai</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link rel="icon" href="<?=$baseurl?>assets/img/favicon-32x32.png">
  <link rel="apple-touch-icon" href="<?=$baseurl?>assets/img/apple-touch-icon.png">


  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?=$baseurl?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?=$baseurl?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?=$baseurl?>/assets/css/main.css" rel="stylesheet">
  <link href="<?=$baseurl?>/assets/css/operator.css" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3.x.x/dist/cdn.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.0.0/socket.io.min.js"></script>
  <script  src="<?=$baseurl?>/assets/js/config.js"></script>
  <style>
    body {
      font-size: 12px;
      background: #e8e5df;
      /* Replace with your image URL */
      background-size: cover;
      /* Cover the entire screen */
      background-position: center;
      /* Center the image */
      background-repeat: no-repeat;
      /* Do not repeat the image */
    }

    .video-player {
      height: 70vh;
      /* Set video player height to 60% of viewport height */
    }

    .schedule-container {
      background: white;
      /* border-radius: 5px; */
      /* border: 1px solid #ccc; */
      height: 80vh;
      /* Set schedule container height to 60% of viewport height */
      overflow-y: auto;
      overflow-x: hidden;
      /* Enable scrolling if content exceeds height */
      padding-top: 5px;
    }

    .schedule-cards {
      display: flex;
      gap: 10px 0;
      /* Adds space between name cards */
      flex-wrap: wrap;
      /* Ensure cards wrap to next row if too wide */
    }

    .schedule-row {
      /* Space between schedule rows */
      display: grid;
      grid-template-columns: 20rem auto;
    }

    .time-slot {
      background-color: black;
      color: white;
      padding: 10px;
      border-radius: 5px;
      text-align: center;
      font-weight: bold;
    }

    .name-card {
      border: 1px solid #0d6efd40;
      padding: 5px 10px;
      text-align: left !important;
      position: relative;
      min-width: 80px;
      margin: 10px 10px;
    }

    .name {
      font-weight: bold;
      /* Bold font for names */
      font-size: 12px;
      /* Font size for names */
    }

    .time {
      font-size: 12px;
      /* Font size for times */
      margin-top: 5px;
      /* Space between name and time */
    }

    /* Style for check mark */
    .check-mark {
      font-size: 13px;
      /* Size of check mark */
      top: -23px;
      /* Adjusted for better positioning */
      left: 0px;
      /* Adjusted for better positioning */
      color: #18dd42;
      /* Color for check mark */
    }

    .ongoing {
      background-color: #25BB4F;
      color: white;
      border: 1px solid #25BB4F;
    }

    .next {
      background-color: #0166FF;
      color: white;
      border: 1px solid #0166FF;
    }

    .ongoing span {
      color: #25BB4F;
    }

    .next span {
      color: #0166FF;
    }

    /* General Scrollbar - Vertical and Horizontal */
    ::-webkit-scrollbar {
      width: 5px;
      /* Vertical scrollbar width */
      height: 5px;
      /* Horizontal scrollbar height */
    }

    /* Vertical Scrollbar Track */
    ::-webkit-scrollbar-track {
      box-shadow: inset 0 0 5px black;
      border-radius: 10px;
    }

    /* Vertical Scrollbar Handle */
    ::-webkit-scrollbar-thumb {
      background: black;
      border-radius: 10px;
    }

    /* Vertical Scrollbar Handle on Hover */
    ::-webkit-scrollbar-thumb:hover {
      background: black;
    }

    /* Horizontal Scrollbar */
    ::-webkit-scrollbar-horizontal {
      height: 5px;
      /* Horizontal scrollbar height */
    }

    /* Horizontal Scrollbar Track */
    ::-webkit-scrollbar-track:horizontal {
      box-shadow: inset 0 0 5px black;
      border-radius: 10px;
    }

    /* Horizontal Scrollbar Handle */
    ::-webkit-scrollbar-thumb:horizontal {
      background: black;
      border-radius: 10px;
    }

    /* Horizontal Scrollbar Handle on Hover */
    ::-webkit-scrollbar-thumb:horizontal:hover {
      background: black;
    }

    #dropdownMenuButton {
      background: black;
      border: 1px solid black;
      padding: 10px 50px;
      border-radius: 0px;
      color: white;

    }

    /* Styling the dropdown menu */
    .dropdown-menu {
      margin-top: 0;
      /* Remove top margin */
      background-color: black;
      /* Set background color of the dropdown menu */
      width: 100%;
      /* Make the dropdown menu width equal to the button */
      border-radius: 0px;
      /* Remove border-radius */
      transform: translate(0px 35px) !important;
    }

    /* Styling the dropdown menu items */
    .dropdown-item {
      color: white;
      /* Set text color to white */
    }

    /* Styling dropdown items on hover */
    .dropdown-item:hover {
      background-color: #333;
      /* Slightly lighter black on hover */
      color: white;
      /* Keep text color white on hover */
    }

    .form-control {
      font-size: 12px;
      /* Change to your desired font size */
    }

    .form-select {
      font-size: 12px;
      /* Change to your desired font size */
    }
  </style>

  <style>
    .photo-input {
      padding: 20px 12px 0 12px;
    }

    .photo-input button {
      border-radius: 10px;
    }

    .photo-input .bi-folder-fill {
      font-size: 25px;
      padding: 10px;
    }

    #loadFile {
      display: none;
    }

    #fileDetails {
      padding: 10px;
      background-color: #f3f3f3;
      border-radius: 7px;
      height: 50px;
      width: 95%;
      border: none;
    }

    .camera-list {
      width: 100%;
      border-collapse: separate;
      /* This enables spacing between rows */
      border-spacing: 0 15px;
      /* Adds vertical space (15px) between rows */
      font-size: 14px;
    }

    .camera-list td,
    .camera-list th {
      padding: 10px;
      text-align: left;
    }

    .round-button {
      width: 18px;
      /* Width and height should be equal to make a perfect circle */
      height: 18px;
      border-radius: 50%;
      /* This makes the button round */
      background-color: lightgreen;
      /* Choose your preferred background color */
      border: none;
      /* No border */
      cursor: pointer;
      /* Pointer cursor on hover */
      display: inline-block;
    }

    .modal-header,
    .modal-footer {
      border: none;
    }

    .slot-header {
      background-color: black;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 5px 20px;
      /* border-radius: 4px 4px 0px 0px; */
    }

    .slot-container {
      border: none !important;
      border-radius: 0;
      margin-bottom: 15px;
      margin-left: 10px;
      /* height: 100%; */
    }

    .camera-list {
      list-style-type: none;
      margin-bottom: 0;
      padding: 14px 0 14px 14px;
    }

    .camera-list li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .camera-list li .delete-button {
      background-color: transparent;
      border: none;
      color: red;
      font-size: 18px;
    }

    .info-button {
      background-color: transparent;
      border: none;
      color: blue;
      font-size: 18px;
    }

    .arrow-buttons {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 10px;
    }

    .arrow-button {
      background-color: white;
      border: none;
      font-size: 24px;
    }

    .add-slot-close-wrapper {
      display: flex;
      justify-content: flex-end;
      /* Aligns the buttons to the right */
      align-items: center;
      width: 100%;
      /* Take up full width */
    }

    .add-slot {
      margin-right: 10px;
    }

    .add-slot-input-wrapper {
      display: none;
      /* Initially hidden */
    }

    .add-slot-input-wrapper input {
      margin-right: 10px;
    }

    .last-row {
      display: block;
      color: white;
      font-size: 18px;
      background: black;
      padding: 0 15px 15px 15px;
      border-radius: 10px;
      width: 100%;
    }

    .last-container {
      padding: 5px 30px;
    }

    /* .second-container {
      padding: 10px 45px 0 45px;
    } */

    .first-container {
      padding: 10px 10px;
    }

    .full-width {
      width: 100%;
      /* Ensure full width */
      padding: 0;
      /* Remove any padding that might affect the layout */
      margin: 0;
      /* Remove any margin if applied */
    }

    .inner-header {
      background-color: #000;
    }

    .inner-header-time {
      color: #fff;
      font-size: 18px;
      text-align: center;
    }

    .input-group {
      display: flex;
      align-items: center;
      width: 130px;
    }

    .input-group .btn {
      height: 100%;
      z-index: 1;
    }

    .quantity-input {
      text-align: center;
      padding-right: 0px;
    }

    .quantity {
      font-size: 1rem;
      font-size: 12px;
      height: 38px;
      padding-left: 3px;
      font-weight: bold;
      border: unset;
    }

    .decrease,
    .increase {
      border-radius: 5px !important;
      width: 35px;
      border-color: rgb(217 217 217);
      font-weight: bold;
      height: 31px;
      line-height: 1;
      /* padding: 4px; */
    }

    .cus-row {
      justify-content: space-between;
      padding-top: 2px;
      padding-bottom: 2px;
      align-items: center;
      font-weight: bold;
      width: max-content;
    }

    .cus-row>div:first-child {
      padding: 0 14px;
      height: 40px;
      align-content: center;
      text-align: start;
      min-width: 17.85rem;
      border: 1px solid #0d6efd40;
    }

    .cus-row>div:nth-child(2) {
      border: 1px solid #0d6efd40;
      height: 40px;
      align-content: center;
      text-align: center;
      padding: 0 4px;
      border-radius: 5px;
    }

    .name-card {
      max-height: 85px;
    }

    input[name="new_slot"] {
      width: 100% !important;
      border-color: #cccccc;
    }

    .input-container-name {
      display: flex;
      align-items: center;
      /* Center items vertically */
    }

    .form-control-name {
      margin-right: 10px;
      /* Space between input and buttons */
    }

    .active-dot {
      color: green;
      font-size: 30px;
    }

    .inactive-dot {
      color: red;
      font-size: 30px;
    }

    .bi-dot::before {
      margin: -8px;
    }

    @media (max-width: 768px) {
      .schedule-cards {
        margin-left: 7px;
      }
    }

    /* @media (max-height: 800.9px) {
      .schedule-container {
        max-height: 72vh;
      }
    } */

    @media (min-width: 992px) {
      .cus-row div {
        padding: unset;
      }

      .cus-row div:nth-child(3) {
        padding-left: 5px;
      }
    }

/* .sortable-ghost {
	display: block;
	width: 100%;
	text-indent: -9999px;
	font-size: 0.001px;
	line-height: 0;
	color: transparent;
	border: none;
}


.sortable-ghost * {
	display: none; 
}


.sortable-ghost:before {
content: '';
position: absolute;
top: -1.5px;
right: 0;
left: 0;
height: 3px;
background: transparent;
border-radius: 3px;
animation: fade 250ms ease-in-out forwards;
} */
  </style>
</head>

<body>

  <!-- Header Section -->
  <div class="container-fluid first-container inner-header">
    <div class="row align-items-center">
      <!-- Logo on the left -->
      <div class="col-md-5 col-sm-5 d-flex justify-content-start">
        <img src="assets/img/logo.png" alt="Logo" style="width: 150px; height: 50px;">
        <!-- Adjust logo size -->
      </div>

      <div class="col-md-2 col-sm-2 d-flex justify-content-start inner-header-time"
        x-data
        <label class="inner-header-time" x-text="$now"></label>
      </div>
	</div>
  </div>
  <div class="container second-container">
    <div class="col-lg-12 col-md-12 col-sm-12 schedule-container" id="schedule-container" x-data="scheduleData()" x-effect="effect()">
      <div>
        <template x-if="Object.keys(sorted_data).length === 0">
          <div>
            <p style="text-align: center">No data available.</p>
          </div>
        </template>


        <template x-for="(details, time_slot) in slots" :key="time_slot">
          <div class="schedule-row row mb-4 mt-4">
            <div class="">
              <div class="slot-container">
                <div class="slot-header">
                  <span x-ref="timeSlots" x-text="formatTimeSlot(time_slot)"></span>
                </div>
				<div class="camera-list">
                  <!-- Iterate over the correct slot array for this time slot -->
                  <template x-for="(customerDetails, index) in slots[time_slot]" :key="index">
                    <div class="row cus-row">
                      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					  <span style="font-size:12px" x-text="customerDetails.g_customer_name"></span>
					  </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div class="schedule-cards d-flex mt-3" >
              <!-- Iterate over the correct card array for this time slot -->
              <template x-for="(customerDetails, index) in cards[time_slot]" :key="customerDetails.g_card_id">
                <div class="name-card position-relative text-center mx-2 "
                  :class="{[customerDetails.g_status]:true}"
                  x-bind:data-id="customerDetails.g_card_id" :ref="`targetElement_${customerDetails.g_card_id}`">
                  <template x-if="customerDetails.g_status === 'ongoing'">
                    <span class="check-mark position-absolute">ONGOING</span>
                  </template>
                  <template x-if="customerDetails.g_status === 'next'">
                    <span class="check-mark position-absolute">NEXT</span>
                  </template>
                  <template x-if="customerDetails.g_status === 'complete'">
                    <i class="bi bi-check-circle-fill check-mark position-absolute"></i>
                  </template>
                  <template x-if="customerDetails.g_status === 'hold'">
                    <i class="bi bi-dash-circle-fill check-mark position-absolute" style="color:grey;"></i>
                  </template>
                  <div class="name" x-text="customerDetails.g_customer_name"></div>
                  <div class="time" x-text="customerDetails.g_start_time"></div>
                  <div class="time" x-text="customerDetails.g_end_time"></div>
                </div>
              </template>
            </div>
          </div>
        </template>

      </div>
    </div>

  </div>
  <!-- File Location POPUP -->
  <div class="modal fade" id="fileLocationModal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="margin-top: -70%;">
        <div class="modal-body">
          <div class="photo-input p-6">
            <div class="row">
              <div class="col-10">
                <input type="file" id="loadFile" />
                <input readonly type="text" id="fileDetails" placeholder="C://Inflight/Video">
              </div>
              <div class="col-2">
                <button class="btn btn-sm btn-primary float-end" onclick="document.getElementById('loadFile').click()">
                  <i class="bi bi-folder-fill"></i></button>
              </div>
            </div>
            <div class="pt-3">
              <button type="button" class="btn btn-danger float-end m-1" data-bs-dismiss="modal" class="resetbox">Close
              </button>
              <button type="button" class="btn btn-success float-end m-1" data-bs-dismiss="modal" class="resetbox">Save
              </button>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End File Location POPUP -->

  <!-- Camera Settings -->
  <div class="modal fade" id="cameraSettingModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header"
          style="background:black;color:white; display: flex; justify-content: space-between; align-items: center;">
          <span>Camera Settings</span>
          <button class="btn btn-danger" style="border: none; background: none; cursor: pointer; color: white;">
            <i class="bi bi-x-circle"></i> <!-- Cancel icon -->
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col">
              <table class="camera-list" style="width: 100%; border-collapse: collapse;">
                <thead>
                  <tr>
                    <th>Camera Name</th>
                    <th>IP Address</th>
                    <th>
                      <div style="text-align: center;">
                        <button class="fav-button"
                          style="border: none; background: #0166FF; cursor: pointer;color:white">
                          <i class="bi bi-plus"></i> <!-- Favorite icon -->
                        </button>
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Camera 1</td>
                    <td>172.168.90.2</td>
                    <td>
                      <button class="round-button"></button>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Info icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>Camera 2</td>
                    <td>172.168.90.2</td>
                    <td>
                      <button class="round-button"></button>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Info icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>Camera 3</td>
                    <td>172.168.90.2</td>
                    <td>
                      <button class="round-button"></button>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Info icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- camera settings end -->

  <!-- Modal -->
  <div class="modal fade" id="cameraSlotsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <div class="add-slot-close-wrapper">
            <button type="button" class="btn btn-sm btn-primary add-slot">Add Slot</button>
            <div class="add-slot-input-wrapper d-flex align-items-center">
              <input type="text" class="form-control d-inline me-2" style="width: auto;" placeholder="Enter slot name">
              <button class="btn btn-sm btn-success me-2">&#10003;</button>
              <button class="btn btn-sm btn-danger">&#10005;</button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col">
              <div class="slot-container">
                <div class="slot-header">
                  <span>Slot 1</span>
                  <div>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button>
                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
                <div class="input-group mt-2" style="padding:0px 20px">
                  <select class="form-select" aria-label="Select option">
                    <option selected>Select an option</option>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                  </select>
                  <button class="btn btn-success" style="border: none; background: none; cursor: pointer;color:green">
                    <i class="bi bi-check-circle"></i> <!-- Tick icon -->
                  </button>
                  <button class="btn btn-danger" style="border: none; background: none; cursor: pointer;color:red">
                    <i class="bi bi-x-circle"></i> <!-- Cancel icon -->
                  </button>
                </div>
                <ul class="camera-list">
                  <li>
                    <span>Camera 1</span>
                    <span>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Favorite icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </span>
                  </li>
                  <li>
                    <span>Camera 2</span>
                    <span>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Favorite icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </span>
                  </li>
                  <li>
                    <span>Camera 3</span>
                    <span>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Favorite icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </span>
                  </li>
                </ul>
              </div>
            </div>
            <div class="col">
              <div class="slot-container">
                <div class="slot-header">
                  <span>Slot 2</span>
                  <div>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button>
                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
                <div class="input-group mt-2" style="padding:0px 20px">
                  <select class="form-select" aria-label="Select option">
                    <option selected>Select an option</option>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                  </select>
                  <button class="btn btn-success" style="border: none; background: none; cursor: pointer;color:green">
                    <i class="bi bi-check-circle"></i> <!-- Tick icon -->
                  </button>
                  <button class="btn btn-danger" style="border: none; background: none; cursor: pointer;color:red">
                    <i class="bi bi-x-circle"></i> <!-- Cancel icon -->
                  </button>
                </div>
                <ul class="camera-list">
                  <li>
                    <span>Camera 1</span>
                    <span>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Favorite icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </span>
                  </li>
                  <li>
                    <span>Camera 2</span>
                    <span>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Favorite icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </span>
                  </li>
                  <li>
                    <span>Camera 3</span>
                    <span>
                      <button class="fav-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-info-circle"></i> <!-- Favorite icon -->
                      </button>
                      <button class="delete-button" style="border: none; background: none; cursor: pointer;">
                        <i class="bi bi-trash"></i> <!-- Trash icon -->
                      </button>
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal End  -->


  <div class="container-fluid last-container">
    <div class="row align-items-center">
      <div class="col-lg-12 col-md-12 text-center">
        <span class="last-row">
          Camera [Slot 1] : Camera 1<span class="video1"><i class="bi bi-dot active-dot"></i></span> [100%]
          ,Camera 2<span class="video2"><i class="bi bi-dot active-dot"></i></span> [100%]
          ,Camera 3<span class="video3"><i class="bi bi-dot inactive-dot"></i></span> [100%]
        </span>
      </div>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="<?=$baseurl?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?=$baseurl?>/assets/js/reception.js"></script>
</body>

</html>