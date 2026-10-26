<?php
//uses Bootstrap 5 & Material Design 2.0 UI KIT
//https://github.com/mdbootstrap/mdb-ui-kit
//https://mdbootstrap.com/docs/standard/#demo

//api
//https://mdbootstrap.com/docs/standard/components/carousel/#docsTabsAPI

?>
<!-- Font Awesome -->
<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    rel="stylesheet"
/>
<!-- Google Fonts -->
<link
    href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap"
    rel="stylesheet"
/>
<!-- MDB -->
<link
    href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/5.0.0/mdb.min.css"
    rel="stylesheet"
/>
<link rel="shortcut icon" href="data:image/x-icon;," type="image/x-icon">




<style>
    .carousel-caption {
        position: absolute;
        right: 15%;
        top: 20%;
        left: 15%;
        padding-top: 1.25rem;
        padding-bottom: 1.25rem;
        color: #fff;
        text-align: center;
        background-color: #00000087;
        height: 300px;
    }

    .hide {
        display: none;
    }
    .show {
        display: block;
    }

</style>





<!-- Carousel wrapper -->
<div id="carouselBasicExample" class="carousel slide carousel-fade" data-mdb-ride="carousel" data-mdb-interval="2000" data-mdb-pause="false">
    <!-- Indicators -->
    <div class="carousel-indicators">
        <button
            id="pause"
            type="button"
            onclick="pause();"
        >
            PAUSE
        </button>

        <button
            id="play"
            type="button"
            class="hide"
            onclick="play();"
        >
            PLAY
        </button>

        <button
            type="button"
            data-mdb-target="#carouselBasicExample"
            data-mdb-slide-to="0"
            class="active"
            aria-current="true"
            aria-label="Slide 1"
        ></button>
        <button
            type="button"
            data-mdb-target="#carouselBasicExample"
            data-mdb-slide-to="1"
            aria-label="Slide 2"
        ></button>
    </div>

    <!-- Inner -->
    <div class="carousel-inner">
        <!-- Single item -->
        <div class="carousel-item active bg-image"
             style="background-image: url('https://www.undologic.com/images/234/330902.jpg');
            height: 100vh"
        >
            <div class="carousel-caption d-none d-md-block">
                <h5>First slide label</h5>
                <p>Nulla vitae elit libero, a pharetra augue mollis interdum.</p>

            </div>
        </div>

        <!-- Single item -->
        <div class="carousel-item bg-image"
            style="background-image: url('https://www.undologic.com/images/234/328551.jpg'); height: 100vh"
        >

            <div class="carousel-caption d-none d-md-block">
                <h5>Second slide label</h5>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>



            </div>
        </div>


    </div>
    <!-- Inner -->

    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-mdb-target="#carouselBasicExample" data-mdb-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-mdb-target="#carouselBasicExample" data-mdb-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
<!-- Carousel wrapper -->







<script
    type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/5.0.0/mdb.min.js"
></script>




<script>
    const myCarousel = document.querySelector('#carouselBasicExample');
    const carousel = new mdb.Carousel(myCarousel);
    setTimeout(function() {
        carousel.cycle();
    }, 1000);

    function pause() {
        carousel.pause();
        console.log('paused');
        showPlay();
    }
    function play() {
        carousel.cycle();
        console.log('play');
        showPause();
    }
    function showPause() {
        document.getElementById("pause").classList.remove("hide");
        document.getElementById("play").classList.add("hide");
    }
    function showPlay() {
        document.getElementById("pause").classList.add("hide");
        document.getElementById("play").classList.remove("hide");
    }
</script>
