<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="/favicon.png">
        <title>POJ</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
        <script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
        <style>
            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li .sidebar-submenu li a {
                font-size: 12px !important;
            }
            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li.sidebar-list:hover > a:hover {
                background-color: #0A5640 !important;
                fill: red;
            }
            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links li a span {
                color: #333 !important;
                font-weight: 600 !important;
            }

            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li:hover .sidebar-link:not(.active):hover span {
                color: #CCCCCC !important;
                font-weight: 500!important;
            }

            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li .sidebar-link.active span {
                color: #fff !important;
                font-weight: 500!important;
            }
            a.router-link-active.router-link-exact-active.sidebar-link.sidebar-title.active {
                background-color: #0A5640 !important;
            }

            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links li a svg {
                stroke: #333 !important;
            }

            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li:hover .sidebar-link:not(.active):hover svg {
                stroke: #CCCCCC !important;
            }

            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li .sidebar-link:is(.active) svg {
                fill: #CCCCCC !important;
                stroke: none !important;
            }
            .page-wrapper .sidebar-main-title h6, .page-wrapper .sidebar-main-title .h6 {
                color: #0A5640 !important;
                font-weight: bold !important;
            }
            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li .sidebar-link.active {
                background-color: #0A5640 !important;
                margin-bottom: 0 !important;
            }
            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links li:last-child {
                margin-bottom: 0 !important;
            }
            .page-wrapper.compact-wrapper .page-body-wrapper div.sidebar-wrapper .sidebar-main .sidebar-links > li:last-child .sidebar-link {
                margin-bottom: 20px !important;
            }
            .page-wrapper .sidebar-main-title h6, .page-wrapper .sidebar-main-title .h6 {
                font-size: 13px !important;
            }

        </style>
        @vite(['resources/js/src/main.js', 'resources/js/src/assets/scss/app.scss'])

    </head>
    <body class="antialiased">
        <div id="app">
        </div>
    </body>
</html>
