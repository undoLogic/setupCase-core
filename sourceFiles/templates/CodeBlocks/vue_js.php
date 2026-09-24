<!-- vuejs -->
<script src="<?= $webroot; ?>js/vue.2.6.14.js"></script>
<!-- axios -->
<script src="<?= $webroot; ?>js/axios.min.js"></script>

<div id="app">
                <div class="card">

                    <div class="card-block">
                        <div class="row">
                            <div class="col-lg-6">
                                ID: {{id}}
                            </div>
                        </div>
                        <div class="col-lg-6">
                        Message: {{message}}
                        </div>

                        <div class="col-lg-12">
                            <a href="#" @click="update()" class="btn btn-secondary" >Update</a>
                        </div>


                    </div>
                </div>


    <div id="exampleModalLive" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLiveLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLiveLabel">Header</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">
                    body
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>




</div>
<!-- end of app -->
<!-- script Starts -->
<script>

    const app = new Vue({
        el: '#app',
        data: {
            message: 'test123',
            id: <?= json_encode($id); ?>

        },
        methods: {
            loadPage: function () {

                console.log('loading index page');

                var data = {
                    id: this.id,

                };
                const that = this;
                var objData = JSON.stringify(data);
                 that.id = that.id + 1;
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = "<?= $csrf; ?>";
                let URL = "<?= $webroot; ?>codeBlocks/vueIndex/";
               console.log(URL);
                console.log(objData);



               // axios.post(URL, objData).then(function (response) {
                  //  that.message = response.data.message;


               // });

            },

            reset: function () {

                this.selectedSearch = null;
                this.selectedStatus = '2';
                this.selectedClient = '0';
                this.selectedStaff = '0';
                this.loadPage();
                this.project_id = null;
                this.selectedProject = 0;
            },

            openModal:function() {

                    $('#exampleModalLive').modal('show');


            },

            update: function () {

                var data = {id: this.id};
                const that = this;
                var objData = JSON.stringify(data);

                that. message = 'updated';

               // window.axios.defaults.headers.common['X-CSRF-TOKEN'] = "<?= $csrf; ?>";
                //let URL = "<?= $webroot; ?>CodeBlocks/vueUpdate/";
                //console.log(URL);
              //  console.log(objData);
              //  axios.post(URL, objData).then(function (response) {

                    //if (response.data.STATUS == 200) {
                       // that.message = response.data.message;
                       // that.id = response.data.id;


                   // } else {
                      //  console.log('ERROR');
                        //console.log(response);
                        //that.error = response.data.MSG

                    //}
                     that.loadPage();

                //});

            },//update



        },// end of method
        filters: {
            money(number_value) {
                if (number_value === undefined) {
                    var number_value = 0;
                }

                const formattedNumber = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD'
                }).format(number_value);

                return formattedNumber;
            },
            twoDecimal(number_value) {
                if (number_value === undefined) {
                    var number_value = 0;
                }
                return number_value.toFixed(2);
            },
            oneDecimal(number_value) {
                if (number_value === undefined) {
                    var number_value = 0;
                }
                return number_value.toFixed(1);
            },
            dateToYMD(value) {
                const mydate = new Date(value);

                //var mydate = new Date();
                var d = mydate.getDate();
                var m = mydate.getMonth() + 1; //Month from 0 to 11
                var y = mydate.getFullYear();
                return '' + y + '-' + (m <= 9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
            },


        },
        created: function () {
            this.loadPage();



        },


    })


</script>
