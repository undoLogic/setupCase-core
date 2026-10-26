<script>

    const app = new Vue({
        el: '#app',
        data: {

            init_limit: 30,
            increase: 10,
            limit: 30,
           message: 'test123',
            count: null,
            results: {},
            name: {},
            originalCount: null


        },
        methods: {
            loadPage: function(){
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = "<?= $csrf; ?>";
                let URL = "<?= $webroot; ?>en/SetupPages/jsonIncreaseLimit/";
                console.log(URL);

                var data = {limit: this.limit};
                var jsonData = JSON.stringify(data);
                console.log('jsonData');
                console.log(jsonData);

                //const that = this;
                axios.post(URL, jsonData).then(function (response) {
                    console.log('load');
                    app.results = response.data.result;
                    console.log('limit');
                    app.limit = response.data.limit;
                    //app.records = response.data;

                    app.originalCount = response.data.originalCount;
                    console.log('original count');
                    console.log(app.originalCount);


                    app.count= response.data.count;
                    console.log('current count');
                    console.log(app.count);
                    //alert(app.count);

                });
            },
            increaseLimit() {

                //if our productCount == limit we can get more products
                if (this.limit < this.originalCount) {
                    //we are getting as many products as limit, so we can get another batch
                   // this.loading();
                    console.log('increase limit');
                    this.limit = this.limit + this.increase;
                    this.loadPage();
               // } else {
                    console.log('No more results...');
                }



            },
            resetLimit() {
                console.log('resetting limit');
                this.limit = this.limit_init;
                this.loadPage();
            },

        },// end of method
        created: function(){
            this.loadPage();
        }


    })



    window.onscroll = function(ev) {

        let boundaryAmount = 2000;
        console.log('scroll Y: '+window.scrollY);
        console.log();

        console.log('scroll Height: '+document.body.scrollHeight);

        //console.log('scrollY: '+window.scrollY+" scrollHeight: "+document.body.scrollHeight+" boundary: "+boundaryAmount+" = "+( document.body.scrollHeight - boundaryAmount));

        if (window.scrollY > ( document.body.scrollHeight - boundaryAmount) ) {
           // if (app.isLoading == false) {
                console.log('loading a new limit');
                app.increaseLimit();
            } else {
                console.log('still loading');
            }
        //}

        if (window.scrollY < 200) {
            console.log(' - - - - below 200');
            //app.resetLimit();
            if (app.limit > app.limit_init) {
                console.log('Reset');
                alert('reset');
                this.resetLimit();
            } else {
                console.log('not ')
            }
        }
    };

</script>
