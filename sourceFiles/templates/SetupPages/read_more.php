<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>

<body>

<style>
    .readmore {
        position: relative;
        max-height: 100px;
        overflow: hidden;
        width:200px;
        padding: 10px;
        margin-bottom: 20px;
        transition:max-height 0.15s ease-out;
    }
    .readmore.expand{
        max-height: 5000px !important;
        transition:max-height 0.35s ease-in-out;
    }
    .readmore-link{
        position: absolute;
        bottom: 0;
        right: 0;
        display: block;
        width:100%;
        height: 60px;
        text-align: center;
        color: blue;
        font-weight:bold;
        font-size:16px;
        padding-top:40px;
        background-image: linear-gradient(to bottom, transparent, white);
        cursor: pointer;
    }
    .readmore-link.expand {
        position: relative;
        background-image: none;
        padding-top:10px;
        height:20px;
    }
    .readmore-link:after {
        content:"Read more";
    }
    .readmore-link.expand:after{
        content:"Read less";
    }

</style>







<div class="container">
    <div class="section">

        <div class="readmore">
            <p>Paragraph one. this is long text. this is long text. this is long text. this is long text.</p>
            <p>Paragraph two. this is long text. this is long text. this is long text. this is long text. this is long text.</p>
            <span class="readmore-link"></span>
        </div>
    </div>

</div>



<script>
    $(".readmore-link").click( function(e) {

        // record if our text is expanded
        var isExpanded =  $(e.target).hasClass("expand");

        //close all open paragraphs
        $(".readmore.expand").removeClass("expand");
        $(".readmore-link.expand").removeClass("expand");

        // if target wasn't expand, then expand it
        if (!isExpanded){
            $( e.target ).parent( ".readmore" ).addClass( "expand" );
            $(e.target).addClass("expand");
        }
    });

</script>

</body>

