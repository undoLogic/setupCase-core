<script>

    const app1 = new Vue({
        el: '#app1',
        data: {
            searchTimer: '',
            message: 'test123',
           searchedName: 'Initially no search',
            searchString: ''


        },
        methods: {
            loadPage: function(){
                this.searchedName = this.searchString;
            },
            updateSearchName: function(){
                var v = this;
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(function () {
                    console.log('TIMEOUT');
                    v.loadPage();
                }, 1000);
            },//productsBySearch

        },// end of method

    })
</script>
