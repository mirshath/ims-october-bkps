<?php
session_start();
include("database/connection.php");

$error_msg = "";

if (isset($_POST['login'])) {
    $input = $_POST['username']; // can be username or email
    $password = $_POST['password'];

    $query = "SELECT * FROM admin WHERE username = ? OR admin_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // Correct password, start session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['admin_email'] = $row['admin_email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['message'] = "Login successful!";
            // header("Location: index");
           echo "<script>
                window.location.href = 'index';
                </script>";
            exit;
            // exit();
        } else {
            $error_msg = "Incorrect password!";
        }
    } else {
        $error_msg = "User not found!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #052c65;
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .form-control {
            border-radius: 1rem;
            height: 45px;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: #052c65;
            border-radius: 1rem;
            padding: 10px 0;
            font-size: 1.2rem;
        }

        .alert {
            border-radius: 1rem;
        }
    </style>
</head>

<!--<body>-->
    <body style="min-height: 100vh; background: url('./assets/BG-LOGO.png') center center / cover repeat; background-size: 250px ; display: flex; align-items: center; justify-content: center;">

    
    
    <div class="container">
        <div class="row justify-content-center align-items-center" style="height: 100vh;">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header text-center">
                        INSTITUTE MANAGEMENT SYSTEM
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-4 mt-4">
                            <img src="https://202.124.164.112:8140/img/logo4.png" alt="Logo" class="img-fluid w-75">
                        </div>

                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-bolder">Username or Email</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="username" name="username" required placeholder="Enter username or email">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bolder">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password">
                                    <span class="input-group-text" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <button type="submit" name="login" class="btn btn-primary w-100 d-flex justify-content-center align-items-center mb-4" style="height: 45px;">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS & Toggle Password -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
</body>

</html>
