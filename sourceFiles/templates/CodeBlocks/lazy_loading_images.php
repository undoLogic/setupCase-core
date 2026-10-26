<script>
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Lazy loading script initialized.");

        const images = document.querySelectorAll("img[data-src]");
        console.log(`Found ${images.length} images to lazy load.`);

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    console.log(`Image "${img.getAttribute('alt')}" is now visible. Loading...`);

                    img.src = img.getAttribute("data-src");
                    img.classList.add("loaded");

                    img.onload = () => console.log(`Image "${img.getAttribute('alt')}" has loaded successfully.`);
                    img.onerror = () => console.warn(`Error loading image: ${img.getAttribute('data-src')}`);

                    img.removeAttribute("data-src"); // Remove attribute after loading
                    observer.unobserve(img);
                    console.log(`Image "${img.getAttribute('alt')}" is now removed from observer.`);
                }
            });
        }, { rootMargin: "50px 0px", threshold: 0.1 });

        images.forEach(img => {
            console.log(`Observing image: ${img.getAttribute('alt')}`);
            observer.observe(img);
        });
    });
</script>


<img alt="Image" data-src="<?= $webroot; ?>prefix/Page/image" style="height: 100px;" />
