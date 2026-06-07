<html>
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-YHDN43XTD3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-YHDN43XTD3');
    </script>

</head>
<body>
This is a google analytics test page for undoWeb

<button onclick="googleEvent('Event', 'Category', 'Label')">Test Event</button>

<script>
    function googleEvent(EVENT, CATEGORY, LABEL) {
        gtag('event', EVENT, {
            'event_category' : CATEGORY,
            'event_label' : LABEL
        });
        console.log('googleEvent: EVENT:'+EVENT+' CATEGORY:'+CATEGORY+' LABEL:'+LABEL);
    }
</script>
</body>
</html>
