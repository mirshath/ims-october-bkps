<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>IMS</title>

    <link rel="stylesheet" href="assets/style.css">

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">



 <!-- ------------------------  -->
    <!-- alertify  -->

    <!-- CSS -->
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/bootstrap.min.css" />





    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- ------------------------  -->

    <!-- Include CKEditor -->
    <script src="https://cdn.ckeditor.com/4.16.1/standard/ckeditor.js"></script>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- new   -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



    <!-- <script>
        // Disable right-click on the page
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault(); // Prevent the default context menu

            // Create custom alert div
            const alertDiv = document.createElement('div');
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '50%';
            alertDiv.style.left = '50%';
            alertDiv.style.transform = 'translate(-50%, -50%)';
            alertDiv.style.backgroundColor = '#c83939';
            alertDiv.style.color = 'white';
            alertDiv.style.padding = '20px';
            alertDiv.style.borderRadius = '5px';
            alertDiv.style.boxShadow = '0 0 10px rgba(0,0,0,0.3)';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = 'Your are not allowed to view this page';

            // Add to page
            document.body.appendChild(alertDiv);

            // Remove after 2 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 2000);
        });


        document.addEventListener('keydown', function(e) {
            // Prevent F12 (developer tools)
            if (e.keyCode === 123) {
                e.preventDefault();
            }
            // Prevent Ctrl+Shift+I (developer tools)
            if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                e.preventDefault();
            }
            // Prevent Ctrl+U (view source)
            if (e.ctrlKey && e.keyCode === 85) {
                e.preventDefault();
            }
            // Prevent right-click menu by disabling context menu shortcut
            if (e.ctrlKey && e.keyCode === 117) { // Ctrl+U
                e.preventDefault();
            }
        });
        
    </script>  -->


    <style>
        /* common CSS  */
        /* ----------------------------------------------  */
        .btn-primary {
            background-color: #052c65 !important;
            /* Custom dark blue color */
            border-color: #052c65 !important;
            /* Match border color */
            color: white !important;
            /* White text */
            margin-left: 5px !important;
            margin-right: 5px!important;
        }

        .btn-primary:hover {
            background-color: #031b42 !important;
            /* Darker shade on hover */
            border-color: #031b42 !important;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.3rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--bs-body-color);
            background-color: var(--bs-body-bg);
            background-clip: padding-box;
            border: var(--bs-border-width) solid var(--bs-border-color);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border-radius: var(--bs-border-radius);
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .form-control:focus {
            box-shadow: 0 0 5px #c110104f;
            border-color: #c110104f;
        }

        .cke_notification_warning {
            background: #c83939;
            border: 1px solid #902b2b;
            display: none;
        }

        /* ----------------------------------------------------------  */
        /* this is for student registration  */

        .form-label {
            margin-bottom: 0.7rem;
            color: #878180;
        }

        .form-check-label {
            color: #878180;
        }

        body {
            font-family: "Open Sans", sans-serif;
            /* size: 12px; */
            line-height: 20px;
        }
    </style>

    <style>
        /* Custom styles for Select2 options */
        .select2-container--default .select2-selection--single {
            height: 30px;
            /* Adjust height */
            font-size: 12px;
            /* Font size for the selected option */
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
            /* Align text vertically */
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 30px;
            /* Adjust arrow height */
        }

        .select2-container--default .select2-results__option {
            font-size: 12px;
            /* Font size for dropdown options */
        }
    </style>


</head>

<body id="page-top" style="font-size: 14px;">
    <!-- <script>
        // Initialize Select2 for all dropdowns
        $('.select2').select2({
            minimumResultsForSearch: Infinity // Disable search box if not needed
        });
    </script> -->
    
    
    
    
    <!-- JavaScript -->
    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/alertify.min.js"></script>


    <script>
        alertify.set('notifier', 'position', 'top-right');
        <?php
        if (isset($_SESSION['message'])) {
        ?>
            alertify.success(' <?= $_SESSION['message']; ?>');
        <?php
            unset($_SESSION['message']);
        }
        ?>
    </script>
    
    
    
    
    <!-- script for the redirect  -->
    <script>
        // $(document).ready(function() {
        //     checkSession(); // immediate check
        //     setInterval(checkSession, 3000); // repeat every 3 seconds
        // });

        // function checkSession() {
        //     $.get('check_session.php', function(data) {
        //         const response = JSON.parse(data);
        //         if (response.logout) {
        //             // Automatically redirect to logout page
        //             window.location.href = 'logout.php';
        //         }
        //     });
        // }
    </script>
    
 <!-- script for the redirect  -->
    <?php if (!isset($skipSessionCheck) || !$skipSessionCheck) : ?>
    <script>
        $(document).ready(function() {
            checkSession(); // immediate check
            setInterval(checkSession, 3000); // repeat every 3 seconds
        });

        function checkSession() {
            $.get('check_session.php', function(data) {
                const response = JSON.parse(data);
                if (response.logout) {
                    // Automatically redirect to logout page
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
    <?php endif; ?>    
    
    
    