<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SetupCase - CodeBlocks</title>
    <link href="<?= $webroot; ?>css/bootstrap.min.css" rel="stylesheet">

    <script type='text/javascript' src='https://platform-api.sharethis.com/js/sharethis.js#property=68fd162823e7abe4dafd7d21&product=sop' async='async'></script>

    <style>
        /* custom styles */
        .sidebar .nav-link {
            color: #333;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #e9ecef;
        }
        .sidebar .collapse .nav-link {
            font-size: 0.95rem;
            padding-left: 1.5rem;
        }
        .sidebar .bi-chevron-down {
            transition: transform 0.2s;
        }
        .sidebar .nav-link[aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }


        .pr-2 {
            padding-right: 50px;
        }

        .sticky{
            position: -webkit-sticky;
            position: sticky;
            top: 80px;
            margin: 5px;
        }

        .toast {
            position: absolute;
            top: 5px;
            right: 10px;
            z-index: 99999;
        }



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


        .navbar-nav a {
            color: white;
            padding-top: 0px;
        }
        .navbar-nav li {
            margin-right: 10px;
        }



    </style>
</head>
<body>
<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CodeBlocks</a>
        <button class="navbar-toggler d-lg-none" type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileSidebar"
                aria-controls="mobileSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-none d-lg-flex">
            <?= $this->element('codeBlocks/layout/sub-menu'); ?>
        </div>

    </div>
</nav>

<!-- Main Layout -->
<div class="container-fluid" style="margin-top: 56px;">
    <div class="row">


        <!-- Desktop Sidebar -->
        <aside class="col-lg-2 d-none d-lg-block bg-light border-end">
            <div class="sticky">
                <?php echo $this->element('codeBlocks/layout/sidebar-menu'); ?>
            </div>
        </aside>

        <!-- Mobile Sidebar -->
        <div class="offcanvas offcanvas-start d-lg-none"
             tabindex="-1"
             id="mobileSidebar">
            <div class="offcanvas-body p-0">
                <?php echo $this->element('codeBlocks/layout/sidebar-menu'); ?>

                <div class="offcanvas-body">
                    <?php echo $this->element('codeBlocks/layout/sub-menu'); ?>
                </div>


            </div>
        </div>




        <main class="col-12 col-md-9 col-lg-10">



            <?= $this->Flash->render(); ?>




            <?php if (isset($codeBlocks_title)): ?>
                <h1>
                    <?= $codeBlocks_title; ?>
                </h1>
            <?php endif; ?>
            <?php if (isset($codeBlocks_subTitle)): ?>
                <p>
                    <?= $codeBlocks_subTitle; ?>
                </p>
            <?php endif; ?>



            <hr/>

            <?= $this->fetch('content') ?>



            <?php if (isset($codeBlocks_renderVar)): ?>
                <?php foreach ($codeBlocks_renderVar as $title => $actionVar): ?>
                    <h3>
                        <?= $title; ?>
                    </h3>
<?php echo $this->element('codeBlocks/render_var', ['actionVar' => $actionVar]); ?>
                <hr/>
                <?php endforeach; ?>
            <?php endif; ?>




            <?php if (isset($codeBlocks_renderFiles)): ?>
                <?php foreach ($codeBlocks_renderFiles as $title => $file): ?>

                    <h3>
                        <?= $title; ?>
                    </h3>
                    <?php echo $this->element('codeBlocks/render_file', ['file' => $file]); ?>
                    <hr/>
                <?php endforeach; ?>
            <?php endif; ?>

        </main>

    </div>
</div>

<script src="<?= $webroot; ?>js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.toast').forEach(el => {
            new bootstrap.Toast(el).show();
        });
    });
</script>

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



</body>
