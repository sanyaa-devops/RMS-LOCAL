<?php
if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
  header("location: $baseurl/login");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Inflight Dubai</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link rel="icon" href="<?= $baseurl ?>/assets/img/favicon-32x32.png">
  <link rel="apple-touch-icon" href="<?= $baseurl ?>/assets/img/apple-touch-icon.png">


  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="<?= $baseurl ?>/assets/css/fonts_googleapi.css" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?= $baseurl ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $baseurl ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= $baseurl ?>/assets/css/main.css" rel="stylesheet">
  <link href="<?= $baseurl ?>/assets/css/operator.css" rel="stylesheet">
  <script defer src="<?= $baseurl ?>/assets/js/alphinejs_sort.js"></script>
  <script defer src="<?= $baseurl ?>/assets/js/alpinejs.js"></script>

  <script src="<?= $baseurl ?>/assets/js/socket.js"></script>
  <link href="<?= $baseurl ?>/assets/css/operator2.css" rel="stylesheet">
  <link href="<?= $baseurl ?>/assets/css/operator3.css" rel="stylesheet">
  <script src="<?= $baseurl ?>/assets/js/config.js"></script>

</head>

<body x-data="modalDataFunction()">

  <!-- Header Section -->
  <div class="container-fluid first-container inner-header" x-data="navbarData()">
    <div class="row align-items-center">
      <!-- Logo on the left -->
      <div class="col-md-5 col-sm-5 d-flex justify-content-start">
        <img src="assets/img/logo.png" alt="Logo" style="width: 150px; height: 50px;">
        <!-- Adjust logo size -->
      </div>

      <div class="col-md-2 col-sm-2 d-flex justify-content-start inner-header-time" x-data>
        <label class="inner-header-time" x-text="$now"></label>
      </div>

      <!-- Dropdown on the right -->
      <div class="col-md-5 col-sm-5 d-flex justify-content-end">
        <div class="dropdown">
        <button class="btn btn-danger" @click="tunnelholdCustomer()" 
          :title="tunnelActive ?'Tunnel UnHold':'Tunnel Hold'">
            <div x-show="!tunnelActive">
              <i class="bi bi-pause"></i>
           </div>
            <div  x-show="tunnelActive">
              <i class="bi bi-play"></i>
            </div>
          </button>
          <button class="btn btn-primary" @click="runCron()" title="Run Cron">
            <i class="bi bi-arrow-repeat"></i>
          </button>
          <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
            data-bs-toggle="dropdown" aria-expanded="false">
            Settings
          </button>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <li><a data-bs-toggle="modal" data-bs-target="#wifiSettingsModal" class="dropdown-item" href="#"><i
                  class="bi bi-play-fill"></i>Wifi Settings</a></li>
            <li><a data-bs-toggle="modal" data-bs-target="#cameraSettingModal" class="dropdown-item" href="#"><i
                  class="bi bi-play-fill"></i>Camera Settings</a></li>
            <li><a data-bs-toggle="modal" data-bs-target="#fileSaveModel" class="dropdown-item" href="#"><i
                  class="bi bi-play-fill"></i>File Location</a></li>     
            <li><a class="dropdown-item" href="#" @click.prevent="logout"><i
                  class="bi bi-play-fill"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div id="overlay" class="overlay" x-show="tunnelActive">
  <div class="spinner"></div>
  </div>
  </div>
  
  <div class="container second-container" id="container">
    <div class="col-lg-12 col-md-12 col-sm-12 schedule-container" id="schedule-container" x-data="scheduleData()" >
      <div>
        <template x-if="Object.keys(sorted_data).length === 0||Object.keys(slots).length === 0||Object.keys(cards).length === 0">
          <div>
            <p style="text-align: center">No data available.</p>
          </div>
        </template>


        <template x-for="(details, time_slot) in slots" :key="time_slot">
          <div class="schedule-row row mb-4 mt-4" :data-id=`schedule-row-${time_slot}`>
            <div>
              <div class="slot-container">
                <div class="slot-header">
                  <span x-ref="timeSlots" x-text="details.formattedTime"></span>
				  <!-- Show modal  -->
                    <button data-bs-toggle="modal" data-bs-target="#intervalmodel"
                    @click="modalData = { timeSlot: time_slot, details: details[0] }"
                     x-show="slots[time_slot] && slots[time_slot].length === 1 && checkAllCardIsScheduled(cards[time_slot])" 
                     type="button"  class="btn btn-sm"><i class="bi bi-clock-history text-white"></i></button>
                </div>
                <div class="camera-list">
                  <!-- Iterate over the correct slot array for this time slot -->
                  <template x-for="(customerDetails, index) in slots[time_slot]" :key="index">
                    <div class="row cus-row">
                      <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3" x-text="customerDetails.g_customer_name"></div>
                      <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                        <div class="input-group" style="width: 100%;">
                          <button class="btn btn-outline-secondary btn decrease" type="button"
                            @click="decreaseDuration(customerDetails, slots[time_slot])"
                            x-bind:disabled="isButtonDisabledForDecrease(time_slot)||hasTimePassed(details.formattedTime)">
                            -
                          </button>
                          <input type="text" class="form-control quantity-input quantity"
                            x-model.number="customerDetails.duration"
                            @input="showOkCloseButtons(customerDetails)"
                            @keydown.enter="handleEnter(customerDetails, slots[time_slot])"
                            x-bind:disabled="isButtonDisabledForInput(time_slot)">
                          <button class="btn btn-outline-secondary btn increase" type="button"
                            @click="increaseDuration(customerDetails, slots[time_slot])"
                            x-bind:disabled="isButtonDisabledForIncrease(time_slot) ||hasTimePassed(details.formattedTime)">
                            +
                          </button>
                          <span class="mx-1"
                            x-text="`${parseFloat(customerDetails.duration) === 1 ? ' Min' : (parseFloat(customerDetails.duration) > 1 ? ' Mins' : ' Sec')}`"></span>
                        </div>
                      </div>
                      <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                      <div  x-show="!customerDetails.showOkClose">
                                      <button class="btn btn-danger" 
                                              @click="removeCustomer(customerDetails, time_slot)" 
                                              title="Delete" 
                                              x-show="!customerDetails.showOkClose"
                                              x-bind:disabled="isButtonDisabledForDelete(time_slot) || hasTimePassed(details.formattedTime)">
                                            <i class="bi bi-trash"></i>
                                      </button>

                                  <!-- Hold Button -->
                                      <button class="btn btn-secondary" 
                                              @click="holdCustomer(customerDetails)" 
                                              x-bind:title="!customerDetails.g_status?'Hold':'Unhold'" 
                                              :data-id="customerDetails.g_status" 
                                              x-bind:disabled="isButtonDisabledForHold(time_slot) || hasTimePassed(details.formattedTime)">
                                             <div x-show="!customerDetails.g_status">
                                                <i class="bi bi-dash-circle"></i>
                                              </div>
                                                <div x-show="customerDetails.g_status">
                                                 <i class="bi bi-plus-circle"></i>
                                              </div>
                                      </button>
                                 </div>
                                 <div  x-show="customerDetails.showOkClose">

                                  <!-- OK Button -->
                                  <button class="btn btn-success" 
                                          @click="send(customerDetails, slots[time_slot])" 
                                          title="OK">
                                      <i class="bi bi-check-lg"></i> 
                                  </button>

                                  <!-- Close Button -->
                                  <button class="btn btn-danger" 
                                          @click="close(customerDetails)" 
                                          title="Close" >
                                        <i class="bi bi-x-circle"></i>
                                  </button>
                                </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div class="schedule-cards d-flex mt-3" x-sort.ghost
              x-sort:config="{ animation: 0, onStart: (evt) => saveOriginalPosition(evt), onEnd: (evt) => handleCardDrop(evt, time_slot), onMove:(evt)=>handleMove(evt),filter: '.filtered' }">
              <!-- Iterate over the correct card array for this time slot -->
              <template x-for="(customerDetails, index) in cards[time_slot]" :key="index + new Date().getTime()">
                <div class="name-card position-relative text-center mx-2"
                  :class="{ [customerDetails.g_status]: true, 'filtered': customerDetails.g_status === 'complete' || customerDetails.g_status === 'hold'|| customerDetails.g_status === 'ongoing' }"
                  x-sort:item="customerDetails" x-sort:key="customerDetails" x-bind:data-id="customerDetails.g_card_id">
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
  <div class="modal fade" id="wifiSettingsModal" x-data="wifisettingData()"
  data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog">
      <div class="modal-content">
        <div class="modal-header"
          style="background:black;color:white; display: flex; justify-content: space-between; align-items: center;">
          <span>Wifi Settings</span>
          <button class="btn btn-danger" data-bs-dismiss="modal" style="border: none; background: none; cursor: pointer; color: white;">
            <i class="bi bi-x-circle"></i> <!-- Cancel icon -->
          </button>
        </div>
        <div class="modal-body">
          <div class="photo-input p-6">
            <form @submit.prevent="saveWifiSettings()">
              <div class="row">
                <div class="form-group">
                  <label for="wifi_name">Wifi Name</label>
                  <input type="text" class="form-control" id="wifi_name" x-model="wifi_name" x-text="wifi_name"
                    placeholder="Enter Wifi Name"
                    :class="{'is-invalid': !wifi_name && errorMessage}">
                </div>
                <br>
                <div class="form-group">
                  <label for="wifi_password">Wifi Password</label>
                  <input type="text" class="form-control" id="wifi_password" x-model="wifi_password" x-text="wifi_password"
                    placeholder="Enter Wifi Password"
                    :class="{'is-invalid': !wifi_password && errorMessage}">
                </div>
              </div>
              <div class="pt-3">
                <button type="button" class="btn btn-danger float-end m-1" data-bs-dismiss="modal"
                  class="resetbox">Close
                </button>
                <button type="submit" :class="{ 'disabled': buttonName !== 'Save' && buttonName !== 'Saved' }" :disabled="buttonName !== 'Save' && buttonName !== 'Saved'"  class="btn btn-success float-end m-1" class="resetbox" x-text="buttonName" >

              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End File Location POPUP -->

  <!-- Interval Model -->
  <div  data-bs-backdrop="static" data-bs-keyboard="false" class="modal fade" id="intervalmodel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
      <div class="modal-content" style="width:75%">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Interval</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="modalClose()"></button>
        </div>
        <div class="modal-body">
          <div class="row cus-row">
           <!--  <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3" style="padding: 0 !important;">
              <input readonly type="text" class="form-control form-control-interval" x-model="person.name" style="width: 100%;box-sizing: border-box;border: none;">
            </div> -->
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4" style="border:0px">
              <div class="input-group" >
                <button 
                  class="btn btn-outline-secondary btn decrease" 
                  type="button" 
                  @click="decreaseInterval(person)" 
				  :disabled="person.interval==0"
                 > -
                </button>
                <input 
                  type="text" 
                  class="form-control quantity-input quantity" 
                  x-model.number="person.interval"
                  >
                <button 
                  class="btn btn-outline-secondary btn increase" 
                  type="button" 
                  @click="increaseInterval(person)" 
				  :disabled="person.interval==10"
                 > +
                </button>
                <span class="mx-1" x-text="`${parseFloat(person.interval) === 1 ? ' Min' : (parseFloat(person.interval) > 1 ? ' Mins' : ' Sec')}`"> Mins</span>
              </div>
            </div>
            <div class="row cus-row" style="border:0px">
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4" style="border:0px">
              <div style="text-align: center;">
                <!-- OK Button -->
                <button 
                  class="btn btn-success" 
                  @click="send(person)" 
                  title="OK">
                  <i class="bi bi-check-lg"></i>
                </button>

                <!-- Close Button -->
                <button 
                  class="btn btn-danger" 
                  @click="close()" 
                  title="Close">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
            </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
         
        </div>
      </div>
    </div>
  </div>
</div>
  <!-- Camera Settings -->

  <div x-data="cameraSettings()" class="modal fade" id="cameraSettingModal"
  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header"
          style="background:black;color:white; display: flex; justify-content: space-between; align-items: center;">
          <span>Camera Settings</span>
          <button class="btn btn-danger" data-bs-dismiss="modal" style="border: none; background: none; cursor: pointer; color: white;">
            <i class="bi bi-x-circle"></i> <!-- Cancel icon -->
          </button>
        </div>
        <!-- <div class="col loaderdiv" x-show="loadershow">
          <div class="loader"></div>
        </div> -->
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
                        <button class="fav-button btn btn-m" @click="addCamera()"
                          style="border: none; background: #0166FF; cursor: pointer;color:white"
                          title="Add New">
                          <i class="bi bi-plus-lg"></i> <!-- Favorite icon -->
                        </button>
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <!-- <tr>
				  <div x-show="showNewInput" class="row mb-2 mainrow">
						<div class="col" style="font-size: 15px;">
							<input x-model="newCamera.name" class="form-control" type="text" placeholder="Camera Name" />
						</div>
						<div class="col" style="font-size: 15px;">
							<input x-model="newCamera.bluetoothAddress" class="form-control" type="text" placeholder="Bluetooth Address" />
						</div>
						<div class="col">
							<button @click="submitNewCamera()" class="btn btn-sm btn-primary me-2">✓</button>
							<button @click="cancelNewInput()" class="btn btn-danger btn-sm">
								<i class="bi bi-x-circle"></i>
							</button>
						</div>
					</div>
				</div>
				  </tr> -->


                  <template x-if="showNewInput">
                    <tr x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                      x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                      <td>
                        <input x-model="newCamera.name" class="form-control" type="text" placeholder="Camera Name"
                          :class="{'is-invalid': !newCamera.name && errorMessage}" />
                      </td>
                      <td>
                        <input x-model="newCamera.bluetoothAddress" class="form-control" type="text"
                          placeholder="Bluetooth Address"
                          :class="{'is-invalid': !newCamera.bluetoothAddress && errorMessage}" />
                      </td>
                      <td>
                        <div style="float: inline-end;">
                          <button @click="submitNewCamera()" class="btn btn-sm btn-primary me-2"
                          title="Save">
                            <i class="bi bi-check-lg"></i>
                          </button>
                          <button @click="cancelNewInput()" class="btn btn-danger btn-sm"
                          title="Cancel">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </div>
                      </td>
                    </tr>

                  </template>
                  <template x-if="cameras.length > 0">
                    <template x-for="(camera,index) in cameras" :key="camera.id">

                      <tr>
                        <td x-text="camera.name"></td>
                        <td x-text="camera.ip_addess"></td>
                        <td class="d-flex align-items-center justify-content-lg-evenly">
                          <button :class="{
                            'active-button': camera.status == 1, 
                            'inactive-button': camera.status == 2
                          }"
                          title="Status"></button>
                          <button @click="getCameraInfo(camera.id)" class="fav-button"
                            style="border: none; background: none; cursor: pointer;"
                            title="Info">
                            <i class="bi bi-info-circle"></i> <!-- Info icon -->
                          </button>
                          <button class="delete-button" style="border: none; background: none; cursor: pointer;"
                          title="Remove">
                            <i class="bi bi-trash" @click="removeCamera(camera,index)"></i> 
                          </button>
                          <!--<button class="delete-button" style="border: none; background: none; cursor: pointer;">
                            <i class="bi bi-arrow-repeat" @click="refreshCamera(camera,index)"></i> 
                          </button> -->
                        </td>
                      </tr>
                    </template>

                  </template>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card container mt-3 p-2" x-show="infoshow" @click.outside="infoshow = false">

            <label x-text="camerainfo"></label>

          </div>
        </div>
      </div>
    </div>
  </div>

  <div x-data="fileSaveSettings" class="modal fade" id="fileSaveModel"
  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header"
          style="background:black;color:white; display: flex; justify-content: space-between; align-items: center;">
          <span>File Location</span>
          <button class="btn btn-danger" data-bs-dismiss="modal" style="border: none; background: none; cursor: pointer; color: white;">
            <i class="bi bi-x-circle"></i> <!-- Cancel icon -->
          </button>
        </div>
        
        <div class="modal-body">
          <div class="row">
            <div class="col">
              <form>
              <table class="camera-list" style="width: 100%; border-collapse: collapse;">
               
                 <tbody>
                 <tr>
                    <td>Path</td>
                   <td>
                      <div>
                        <input type="text" x-model="fileLoc" class="form-control">
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                    <div class="d-flex justify-content-end">
                        <button x-save-btn='{"ajaxPath":"<?=$baseurl?>/controllers/savefilepath.php","validate":true}' class="btn btn-success  me-2">Save</button>
                        <button class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                    </td>
                  </tr>
                 </tbody>
              </table>
              </form>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>


  <div x-data="scheduleData" class="container-fluid last-container" >
    <div class="row align-items-center">
      <div class="col-lg-12 col-md-12 text-center">
        <span class="last-row d-flex justify-content-center">
          <template x-if="footerContents.length > 0">
            <template x-for="(ipStatus, index) in footerContents" :key="ipStatus.ip">
              <div class="camera-status">
                <span x-text="ipStatus.camera_name"></span> : <span x-text="ipStatus.ip"></span>
                <span class="video1">
                  <i x-show="ipStatus.reachable" class="bi bi-circle-fill active-dot text-green-600" aria-label="Camera is reachable" style="font-size:20px;display:inline-block"></i>
                  <i x-show="!ipStatus.reachable" class="bi bi-circle-fill inactive-dot text-red-600" aria-label="Camera is not reachable" style="font-size:20px;display:inline-block"></i>
                  <span x-show="index < footerContents.length - 1">,&nbsp;</span>
                </span>
              </div>
            </template>
          </template>
          <template x-if="footerContents.length === 0">
            <p>No camera statuses available.</p>
          </template>
        </span>
      </div>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="<?= $baseurl ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $baseurl ?>/assets/js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>

const Toast = Swal.mixin({

            toast: true,

            position: "top-end",

            showConfirmButton: false,

            timer: 3000,

            timerProgressBar: true,

            didOpen: (toast) => {

                toast.onmouseenter = Swal.stopTimer;

                toast.onmouseleave = Swal.resumeTimer;

            }

        }); 



  </script>
</body>

</html>