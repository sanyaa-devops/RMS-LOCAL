<?php 

include_once 'header.php';
?>
  <div class="container second-container" x-data="videos">
    <div class="row">
      <h5 class="plateform-header">Session Video</h5>
       <div class="col-lg-12 col-md-12 col-sm-12 ">
        <div class="cards-master">
          <div class="row">
            <div class="card custom-card" style="border:0 !important">
            <!-- <video @contextmenu.prevent height="auto" autoplay muted 
              src="./controllers/adminvideo.php?s=<?php echo $_GET['s']; ?>&d=<?php echo $_GET['d'];  ?>&u=<?php echo $_GET['u'];  ?>" 
              style="max-height: 38rem;">
              <source type="video/mp4">
              Your browser does not support the video tag.
            </video> -->
            <video @contextmenu.prevent height="auto" autoplay muted 
              src="./controllers/adminvideo.php?st=<?php echo $_GET['st']; ?>" 
              style="max-height: 38rem;">
              <source type="video/mp4">
              Your browser does not support the video tag.
            </video>
              <div class="card-body">
                <p class="card-text">
                  <i class="bi bi-calendar"></i> 
                  <span class="card-span" x-text="formattedDate"></span> 
                  <button type="button" class="btn btn-danger  me-2" style="float: right;" @click.prevent="remove('<?php echo $_GET['st']??''; ?>')">
                    <i class="bi bi-trash"></i>
                  </button>
                  <button  type="button"  class="btn btn-primary me-2"  style="float: right;"  @click="downloadFile('./controllers/adminvideo.php?st=<?php echo $_GET['st']??''; ?>')">
                      <i class="bi bi-download"></i>
                  </button>
                 
                 </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Modal End  -->

  </div>

   



  <!-- Main JS File -->
  <script src="templates/assets/js/main.js"></script>
  <script>

function videos() {
        return {
            apiUrl:'<?php echo $endpoint?>',
            userId:<?php echo json_encode($_SESSION['user_id']); ?>,
            data:[],
            selectedDate:'',
            formattedDate:'',
            videoName:'',
            videoUrl:'',
            date:'',
            init() {
                this.urlValues();
              },
            formatDate(dateString) {
              const [day, month, year] = atob(dateString).split('-');
              const date = new Date(`${year}-${month}-${day}`);
              
              const options = { day: 'numeric', month: 'long', year: 'numeric' };
              return date.toLocaleDateString('en-GB', options);  // Output: '23 October 2024'
           
             },
            urlValues(){
              const currentUrl = window.location.href;
              const url = new URL(currentUrl);
              const params = new URLSearchParams(url.search);
              const videoName = params.get('s'); // Replace 'name' with your actual parameter name
              const date = params.get('d'); // Example for another parameter

             
                this.videoName = videoName;
                this.date = date;
                this.formattedDate = this.formatDate(date);
              },
            
            downloadFile(url) {
              const a = document.createElement('a');
              a.href = url;
              a.setAttribute('download', '');
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
            },
            remove(path){
              if(confirm('Are you sure want to remove?')){
              fetch(`${this.apiUrl}/controllers/deletevideo.php`, {
                  method: 'POST',
                  headers: {
                  'Content-Type': 'application/json' // Set Content-Type to JSON
                  },
                  body: JSON.stringify({fullpath:path})
                })
                .then(response => response.json())
                .then(res => {
                  if(res.success){
                    setTimeout(() => {
                                window.location.href = `${this.apiUrl}/dashboard`; 
                     }, 1000); 
                  }
                })
                .catch(error => {
                  console.error('Error:', error);
                // alert('An error occurred. Please try again.');
                });
              }
          },
        }
    }

  </script>

</body>

</html>