<div class="container" x-data="dashboard">
  <div class="row">
    <h2 class="plateform-header">Session Recordings</h2>
    <div class="col-lg-3 col-md-3 col-sm-12">

      <form>
        <div>
          <div class="form-group">
            <label for="datepicker-input">Date:</label>
            <input type="text" class="form-control" x-model="selectedDate" x-ref="datepicker" x-text="selectedDate" id="datepicker-input" placeholder="Select a date" :value="selectedDate">
          </div>
          <div id="datepicker"></div>
        </div>

      </form>


    </div>
    <div class="col-lg-9 col-md-9 col-sm-12">
      <h5 class="plateform-header">Rotations</h5>
      <div class="cards-master">
        <template x-if="data.length > 0">
          <div class="row" x-ref="cardContainer">
            <template x-for="(item, index) in data" :key="index">
              <div class="col-lg-4 col-md-4 col-sm-12 mb-4"> <!-- Responsive columns -->
                <div class="card custom-card" style="width: 100%;"> <!-- Full width of column -->
                 <!--  <a x-bind:href="`${apiUrl}/adminvideo.php?s=${item.name}&d=${item.date}&u=${item.path}`"> -->
                 <a x-bind:href="`${apiUrl}/adminvideo.php?st=${item.fullpath}&d=${item.date}`">
                   <img src="templates/assets/img/rotation.jpg" class="card-img-top" alt="...">
                  </a>
                  <div class="card-body">
                    <p class="card-text">
                      <i class="bi bi-calendar"></i>
                      <span class="card-span" x-text="atob(item.name)"></span> <!-- Using item data -->
                      <button type="button" class="btn btn-danger btn-sm  me-2" style="float: right;" @click.prevent="remove(item)">
                        <i class="bi bi-trash"></i>
                      </button>
                      <button type="button" class="btn btn-primary btn-sm  me-2" style="float: right;" 
                        @click.prevent="downloadFile(`./controllers/adminvideo.php?st=${item.fullpath}`)">
                        <i class="bi bi-download"></i>
                    </button>

                      
                    </p>
                  </div>
                </div>
              </div>
              <template x-if="(index + 1) % 3 === 0 && index + 1 !== data.length">
                <div class="w-100"></div> <!-- Force new row after every 3 items -->
              </template>
            </template>
          </div> <!-- Final row closing -->
        </template>
        <template x-if="data.length === 0">
          <p>No items to display.</p> <!-- Message if no data is present -->
        </template>
      </div>
    </div>

  </div>
</div>


<!-- Modal End  -->

</div>



<!-- Main JS File -->
<script src="templates/assets/js/main.js"></script>
<script>
  function dashboard() {
    return {
      apiUrl: '<?php echo $endpoint ?>',
      userId: <?php echo json_encode($_SESSION['user_id']); ?>,
      data: [],
      selectedDate: '',

      init() {

        //this.fetchContent();
        this.loadDate()
      },
      loadDate() {
        const self = this;
        $('#datepicker').datepicker({
          format: 'dd-mm-yyyy', // Adjust format as needed
          autoclose: true, // Automatically close the datepicker after selection
          todayHighlight: true,
        }).on('changeDate', function(e) {
          self.selectedDate = e.format(); // Update Alpine property with the selected date
          localStorage.setItem('selDate',self.selectedDate);
          self.fetchContent();

        });
        const defaultDate = self.getDate(); // Your desired default date
        $('#datepicker').datepicker('setDate', defaultDate); // Set the default date
        self.selectedDate = defaultDate; // Also update the Alpine property
        localStorage.setItem('selDate',self.selectedDate);
      },
      getDate() {
       
        const currentDate = new Date();
        const day = String(currentDate.getDate()).padStart(2, '0'); // Get day and pad with zero
        const month = String(currentDate.getMonth() + 1).padStart(2, '0'); // Get month and pad with zero
        const year = currentDate.getFullYear(); // Get full year

        const customFormattedDate = `${day}-${month}-${year}`;
        let selDate = localStorage.getItem('selDate');
        if(selDate){
          return selDate
        }
        return customFormattedDate
        
      },
      fetchContent() {
        const formData = new FormData();
        formData.append('username', this.userId);
        // console.log(this.selectedDate)
        if (this.selectedDate) {
          formData.append('date', this.selectedDate);
        }

        // Send a POST request to the Flask server
        fetch(`${this.apiUrl}/controllers/admindashboard.php`, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(res => {
            // Handle success or failure response here
            this.data = res
           // console.log(res)
          })
          .catch(error => {
            console.error('Error:', error);
           // alert('An error occurred. Please try again.');
          });
      },
      remove(items) {
        if(confirm('Are you sure want to remove?')){
        fetch(`${this.apiUrl}/controllers/deletevideo.php`, {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json' // Set Content-Type to JSON
            },
            body: JSON.stringify(items)
          })
          .then(response => response.json())
          .then(res => {
            if(res.success){
              this.fetchContent();
            }
          })
          .catch(error => {
            console.error('Error:', error);
           // alert('An error occurred. Please try again.');
          });
        }
      },
      downloadFile(url) {
       
              const a = document.createElement('a');
              a.href = url;
              a.setAttribute('download', '');
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
            },
    }
  }
</script>
</body>

</html>