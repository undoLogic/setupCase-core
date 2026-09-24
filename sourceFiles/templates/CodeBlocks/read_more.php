<style>
    .readmore {
        position: relative;
        max-height: 100px;
        overflow: hidden;
        padding: 10px;
        margin-bottom: 20px;
        transition: max-height 0.15s ease-out;
    }
    .readmore.expand {
        max-height: 5000px !important;
        transition: max-height 0.35s ease-in-out;
    }
    .readmore-link {
        position: absolute;
        bottom: 0;
        right: 0;
        display: block;
        width: 100%;
        height: 60px;
        text-align: center;
        color: blue;
        font-weight: bold;
        font-size: 16px;
        padding-top: 40px;
        background-image: linear-gradient(to bottom, transparent, white);
        cursor: pointer;
    }
    .readmore-link.expand {
        position: relative;
        background-image: none;
        padding-top: 10px;
        height: 20px;
    }
    .readmore-link::after {
        content: "Read more";
    }
    .readmore-link.expand::after {
        content: "Read less";
    }
</style>

<div class="readmore">
    <p>Paragraph one. this is long text. this is long text. this is long text. this is long text.</p>
    <p>Paragraph two. this is long text. this is long text. this is long text. this is long text. this is long text.</p>
    <p>Paragraph three. this is long text. this is long text. this is long text. this is long text. this is long text.</p>
    <p>Paragraph four. this is long text. this is long text. this is long text. this is long text. this is long text.</p>
    <p>Paragraph five. this is long text. this is long text. this is long text. this is long text. this is long text.</p>
    <span class="readmore-link"></span>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const links = document.querySelectorAll(".readmore-link");

        links.forEach(link => {
            link.addEventListener("click", function(e) {
                const isExpanded = e.target.classList.contains("expand");

                // close all open paragraphs
                document.querySelectorAll(".readmore.expand").forEach(el => el.classList.remove("expand"));
                document.querySelectorAll(".readmore-link.expand").forEach(el => el.classList.remove("expand"));

                // if target wasn't expanded, expand it
                if (!isExpanded) {
                    e.target.classList.add("expand");
                    const parent = e.target.closest(".readmore");
                    if (parent) parent.classList.add("expand");
                }
            });
        });
    });
</script>
