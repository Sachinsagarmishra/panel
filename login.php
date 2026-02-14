<?php
session_start();

/* ===== LOGIN CHECK ===== */
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = "";

/* ===== LOGIN HANDLE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === 'Sachin' && $password === 'Sagar@001') {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

/* ===== LOGO LINK ===== */
$logo_url = "assets/logo.png";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/png" href="https://sachindesign.com/assets/img/Sachin's%20photo.png">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6fb;
        }

        /* ===== CONTAINER ===== */
        .login-container {
            width: 100%;
            max-width: 1000px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.12);
        }

        /* ===== LEFT IMAGE ===== */
        .login-left img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ===== RIGHT FORM ===== */
        .login-right {
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            max-width: 140px;
        }

        .login-right h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
        }

        .login-right p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        /* ===== FORM ===== */
        input {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 15px;
            margin-bottom: 15px;
        }

        input:focus {
            outline: none;
            border-color: #000;
        }

        /* ===== BUTTON ===== */
        button {
            width: 100%;
            padding: 14px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #111;
        }

        /* ===== ERROR ===== */
        .error {
            background: #ffe4e4;
            color: #c40000;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 13px;
            color: #777;
        }

        /* ===== MOBILE ===== */
        @media(max-width:768px) {
            .login-container {
                grid-template-columns: 1fr;
            }

            .login-left {
                height: 260px;
            }

            .login-right {
                padding: 35px;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">

        <!-- LEFT IMAGE -->
        <div class="login-left">
            <img src="assets/left-banner.svg" alt="Your Product Designer">
        </div>

        <!-- RIGHT FORM -->
        <div class="login-right">

            <div class="logo">
                <img src="https://sachindesign.com/assets/img/SachinMishra.png" alt="Logo">
            </div>

            <h2>Log in to your account</h2>
            <p>Enter your email and password to continue</p>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="username" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <div class="footer">
                © 2025 sachindesign.com
            </div>

        </div>

    </div>

</body>

</html>