
<?php


if(isset($_SESSION['user_id']) && $_SESSION['user_id']){
    header("location: $baseurl/operator");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="icon" href="<?=$baseurl?>/assets/img/favicon-32x32.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?=$baseurl?>/assets/css/login.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .image-section {
            background-image: url('<?=$baseurl?>/assets/img/logo.jpg');
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
                    <img src="<?=$baseurl?>/assets/img/logo.png" alt="Logo" class="me-2">
                    <!-- Right margin for spacing -->
                    <p class="ps-2 p-heading">Session Recordings</p> <!-- Left padding for spacing -->
                </div>

                <div class="form-login-custom" x-data="loginForm()">
                    <p class="p-heading">Login</p>
                    <p class="light letter-space">Enter your email below to access recordings</p>
                    <div class="alert alert-danger" role="alert" x-show="showAlert">
                     <span  x-text="alertMessage" class="text-danger"></span>
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
                        <button type="submit" class="btn btn-sm btn-primary login-btn">Login</button>
                    </form>
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
            email: '',
            password: '',
            showAlert: false,
            alertMessage: '',
            apiUrl:'<?php echo $baseurl?>',
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
                           Toast.fire({
                                icon: "success",
                                title: "Signed in successfully"
                            });
                            setTimeout(() => {
                                window.location.href = `${this.apiUrl}/operator`; 
                            }, 1000);  // Example redirect
                        } else {
                            this.showAlert = true; // Show alert
                            this.alertMessage = data.message;
                            // Show an error message

                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            }
        }
    }
</script>
</body>
</html>

