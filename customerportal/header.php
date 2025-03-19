
<?php
session_start();
require_once './controllers/config.php';
if(!$_SESSION || !$_SESSION['user_id']){
  session_destroy();
  header("location: {$baseurl}/login");
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
  <link href="./templates/assets/img/favicon-32x32.png" rel="icon">
  <link href="./templates/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="./templates/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="./templates/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

  <!-- Main CSS File -->
  <link href="./templates/assets/css/main.css" rel="stylesheet">
  <link href="./templates/assets/css/platform.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css" />
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <!-- Vendor JS Files -->
  <script src="./templates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  
</head>

<body>
  <div class="container-fluid first-container inner-header">
    <div class="row align-items-center">
      <div class="col-md-5 col-sm-5 d-flex justify-content-start">
        <img src="./templates/assets/img/logo.png" alt="Logo" style="width: 100px;">
      </div>

      <div class="col-md-2 col-sm-2 d-flex justify-content-start inner-header-time">
        
      </div>

      <div class="col-md-5 col-sm-5 d-flex justify-content-end" x-data="log">
        <div class="dropdown">
          <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
            data-bs-toggle="dropdown" aria-expanded="false">
            <?=$_SESSION['user_name']?>
          </button>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
          <li><a @click.prevent="logout()" class="dropdown-item" href="#"><i
                  class="bi bi-play-fill"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
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
  function log(){
      return {
        apiUrl:'<?php echo $endpoint?>',
        async logout(){
          const response = await fetch(`${this.apiUrl}/controllers/logout.php`);
          const res = await response.json();
         if(res){
          window.history.replaceState(null, '', `${this.apiUrl}/login`);
          window.location.href = `${this.apiUrl}/login`;  // Example redirect
         }
          
      }
    }
  }
  </script>