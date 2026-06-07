<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <!-- axios -->
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <title>Vue2</title>
</head>
<body>
<style>
    .empty {
        border: solid 1px red;
    }
</style>

<div id="app">
    <div class="card mt-1 ml-1 mr-1">
        <div class="card-header">
            <h5>Vue2 Form Validation</h5>
            Required fields: {{message}}
            <span v-for="(field,fieldKey) in fields" :id="'key-'+fieldKey">
                {{fieldKey}} {{field}} <a href="#" @click="removeValidation(fieldKey)">Remove</a><br>
            </span>



        </div>
        <div class="card-body">
            <form @submit="checkForm" novalidate="true">
                <div class="row">
                    <!-- errors -->
                    <div class="col-lg-12">
                        <span v-if="errors.length">
                            <b>Please correct the following error(s):</b>
                        <ul>
                            <li v-for="error in errors">{{ error }}</li>
                        </ul>
                        </span>
                    </div>

                    <!-- name -->
                    <div class="col-lg-6">
                        <label for="name">Name</label>
                        <input id="name" v-model="formData.name" type="text" name="name" class="form-control">

                    </div>

                    <!-- email -->
                    <div class="col-lg-6">
                        <label for="name">Email</label>
                        <input id="email" v-model="formData.email" type="email" name="email" class="form-control">

                    </div>
                    <div class="col-lg-6">
                        <label>Single Checkbox</label><br>
                        <input type="checkbox" id="checkbox" v-model="checked"> Checkbox<br>
                        <label for="checkbox">Checked True Or False: {{ checked }}</label>
                    </div>


                    <!-- Favourite movie -->
                    <div class="col-lg-6">
                        <label for="movie">Favorite Movie</label>
                        <select id="movie" v-model="formData.movie" name="movie" class="form-control">
                            <option>Star Wars</option>
                            <option>Star Trek</option>
                        </select>
                    </div>
                    <!-- province -- options dropdown -->
                    <div class="col-lg-6">
                        <label for="province">Province</label>
                        <select id="province" v-model="formData.province" name="province" class="form-control">

                            <option v-for="(province, key) in provinces" :value="key">{{province}}</option>
                        </select>
                    </div>

                    <!-- radio -->
                    <div class="col-lg-6">
                        <label>Radio</label>
                        <div class = "row ml-1 mr-2 " v-for="shipping in radioOptions" >
                            <div class="col-md-1" >

                                <input @click="updateShippingToCart(shipping.key)" type="radio" id="shipping.key" :value="shipping.key" v-model="selectedAddress" name="shippingaddress">

                            </div>
                            <div class="col-md-9 text-left" :class="{greyed: shipping.key === 'create'}">
                                {{shipping.value}}
                            </div>



                            </div>
                        </div>


                    <div class="col-lg-12">
                        <label>Multiple Checkbox</label><br/>
                        <input type="checkbox" id="jack" value="Jack" v-model="checkedNames">
                        <label for="jack">Jack</label>
                        <input type="checkbox" id="john" value="John" v-model="checkedNames">
                        <label for="john">John</label>
                        <input type="checkbox" id="mike" value="Mike" v-model="checkedNames">
                        <label for="mike">Mike</label>
                        <br>
                        <span>Checked names: {{ checkedNames }}</span>
                    </div>


                </div>
                    <div class="col-lg-12 mt-2">
                        <input type="button" value="Preview" @click="showModal()" class="btn btn-warning">

                        <input type="submit" value="Submit" class="btn btn-primary">
                    </div>
                </div><!-- /row -->


            </form>
        </div><!-- /card-body -->
    </div><!-- /card -->





    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Data to be submitted</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Name: {{formData.name}}<br/>
                    Email: {{formData.email}}<br/>
                    movie: {{formData.movie}}<br/>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>






</div><!-- /app -->











<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"
        integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6"
        crossorigin="anonymous"></script>
</body>

</html>
<!--------------------------------- end of  html ------------------------------------------------>
<script>

    const app = new Vue({
        el: '#app',
        data: {
            message: 'test123',
            isModalVisible: true,
            errors: [],
            formData: {},
            //FIELDS
            fields: {
                name: {rule: 'notBlank'},
                email: {rule: 'notBlank'},
                province: {rule: 'notBlank'},
                movie: {rule: 'notEqual', string: 'Star Wars'}
            },
            provinces: {'1':'AB', '2': 'BC'},
            radioOptions: [{"key":"1", "value": "Yes"} ,{"key": "0", "value": "No"}],
            selectedAddress: 0,
            checked: true,
            checkedNames: ['John', ]

        },
        methods: {
            showModal() {
                $('#exampleModal').modal('show');
            },
            removeValidation: function(fieldKey) {
                this.$delete(this.fields[fieldKey], 'rule');
            },
            checkForm: function (e) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = "<?= $csrf; ?>";
                this.errors = [];

                //loop
                Object.keys(this.fields).forEach(key => {

                    let field = key;
                    let rule = this.fields[key]['rule'];
                    let string = this.fields[key]['string'];

                    document.getElementById(field).classList.remove('empty');

                    if (rule === 'notBlank') {
                        if (!this.formData[field]) {
                            this.errors.push(field+" required.");
                            document.getElementById(field).classList.add('empty');
                        } else {
                            document.getElementById(field).classList.remove('empty');
                        }
                    } else if (rule === 'notEqual') {
                        if (!this.formData[field]) {
                            this.errors.push("Select "+field);
                            document.getElementById(field).classList.add('empty');
                        } else if (this.formData[field] === string) {
                            this.errors.push(string+" is not allowed");
                            document.getElementById(field).classList.add('empty');
                        } else {
                            document.getElementById(field).classList.remove('empty');
                        }
                    }

                });

                //array
                if (!this.errors.length) {
                    console.log('formdata - submit form');
                    console.log(this.formData);

                    var objData = JSON.stringify(this.formData);

                    console.log('objData');
                    console.log(objData);
                    let URL = "<?= $webroot; ?>en/SetupPages/submitForm";
                    axios.post(URL, objData).then(function (response) {
                        console.log("response");
                        console.log(response);
                        if (response.data.STATUS == 200) {
                            alert('SUCCESS '+response.data.MSG);
                        }
                    });
                }
                e.preventDefault();
            }
        },// end of method

        })
</script>
