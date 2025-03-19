<?php
session_start();
require_once './controllers/config.php';

if($_SESSION && $_SESSION['user_id']){
    header("location: {$baseurl}/dashboard");
}else{
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="icon" href="templates/assets/img/favicon-32x32.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="templates/assets/css/login.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .image-section {
            background-image: url('templates/assets/img/logo.jpg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body>
<div class="container-fluid login-container">
    <div class="row w-100">
        <!-- Left side - Image section -->
        <div class="col-md-6 d-none d-md-block image-section">
        </div>

        <!-- Right side - Form section -->
        <div class="col-md-6 form-section">
            <div class="login-form">
                <div style="padding-left: 25px;">
                    <img src="templates/assets/img/logo.png" alt="Logo" class="me-2">
                    <!-- Right margin for spacing -->
                    <p class="ps-2 p-heading">Session Recordings</p> <!-- Left padding for spacing -->
                </div>

                <div class="form-login-custom" x-data="loginForm()">
                    <p class="p-heading">Login</p>
                    <p class="light letter-space">Enter your email below to access recordings</p>
                    <div class="alert alert-danger" role="alert" x-show="showAlert" x-cloak>
                     <span x-show="!status && alertMessage"  x-text="alertMessage" class="text-danger"></span>
					  <span x-show="status && alertMessage"  x-html="alertMessage" class="text-danger"></span>
                    </div>
                    <form @submit.prevent="submitForm">
                        <div class="mb-3"> <!-- Margin bottom for spacing -->
                            <label for="email" class="form-label light-bold">Email</label>
                            <input x-model="email" type="email" id="email" class="form-control"
                                   placeholder="user@example.com" autocomplete="off" required>
                        </div>
                        <div class="mb-3"> <!-- Margin bottom for spacing -->
                            <label for="password" class="form-label light-bold">Password</label>
                            <input x-model="password" type="password" id="password" class="form-control"
                                   placeholder="Password" autocomplete="off" required>
                        </div>
						<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#staticBackdrop" class="forgot-password">Forgot Password</a>
                        <button type="submit" class="btn btn-sm btn-primary login-btn">Login</button>
                    </form>
					<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel">Forgot Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="reset()"></button>
                            </div>
                            <form @submit.prevent="send">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="emailId" class="form-label">Email:</label>
                                            <input type="email" class="form-control" x-model="femail" aria-describedby="emailHelp"  required>
                                            <template x-if="emailHelp.show">
                                                <div  id="emailHelp" class="form-text" x-text="emailHelp.message" x-bind:style="{'color':emailHelp.class}"></div>
                                            </template>
                                         </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button @click="emailHelp=''" type="button" class="btn btn-secondary btn-md forgot-close" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary btn-md forgot-btn">Send</button>
                                    </div>
                                </form>  
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (Optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
    function loginForm() {
        return {
			emailHelp:{
                show:false,
                message:'',
                class:'',
            },
            femail:'',
            email: '',
            password: '',
            showAlert: false,
            alertMessage: '',
			status:false,
            apiUrl:'<?php echo $endpoint?>',
            submitForm() {
                if (!this.email || !this.password) {
                    this.showAlert = true; // Show alert
                    this.alertMessage = 'Email and password are required.';
                    return; // Exit the function if validation fails
                }
                // Create a FormData object to send with fetch
                const formData = new FormData();
                formData.append('username', this.email);
                formData.append('password', this.password);

                // Send a POST request to the Flask server
                fetch(`${this.apiUrl}/controllers/login.php`, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        // Handle success or failure response here
                        //console.log(data);
                        if (data.success) {
                           // Redirect or show a success message
                           window.location.href = `${this.apiUrl}/dashboard`;  // Example redirect
                        } else {
                            this.showAlert = true; // Show alert
                            this.alertMessage = data.message;
							if(data.status === 0){
								this.status = true;
								this.alertMessage = 'Your email is not registered on our portal. Please register at <a href="https://store.inflightdubai.com/inflight/main/store.php?invoice=RS-2C1TZN0A" target="_blank">inflightdubai.com</a> to log in below.';
							}
                            // Show an error message

                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            },
			send(){
                if (!this.femail) {
                    this.emailHelp.show = true;
                    this.emailHelp.class = 'red';
                    this.emailHelp.message = 'Email is required.';
                    return; // Exit the function if validation fails
                }
                // Create a FormData object to send with fetch
                const formData = new FormData();
                formData.append('email', this.femail);
                // Send a POST request to the Flask server
                fetch(`${this.apiUrl}/controllers/sendPassReset.php`, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        this.emailHelp.show = true;
                        if(data.success == 1){
                            this.emailHelp.message = data.msg;
                            this.emailHelp.class = 'green';
                        }else{
                            this.emailHelp.message = data.msg;
                            this.emailHelp.class = 'red';
                           
                        }
                      //  this.femail = '';
					this.$nextTick(() => {
						this.femail = '';  // Clear the email field after the alert shows
					});
									
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
						this.$nextTick(() => {
							this.femail = '';  // Clear the email field after the alert shows
						});
                    });
            },
			reset(){
				this.femail='';
				 this.emailHelp = {};
			}
        }
    }
</script>
</body>
</html>

