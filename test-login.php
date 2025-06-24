<?php
session_start();

// LDAP settings
$ldap_server = "ldap://172.10.10.70";
$ldap_port = 389;
$domain = "training.local";
$base_dn = "DC=training,DC=local";

$message = "";
$message_type = "";

// Handle login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username dan Password wajib diisi!";
        $message_type = "danger";
    } else {
        $ldap_conn = ldap_connect($ldap_server, $ldap_port);

        if (!$ldap_conn) {
            $message = "Gagal terhubung ke server LDAP.";
            $message_type = "danger";
        } else {
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            $ldap_user = $username . '@' . $domain;

            $bind = @ldap_bind($ldap_conn, $ldap_user, $password);
            if ($bind) {
                // Get group information
                $filter = "(sAMAccountName=$username)";
                $attributes = ['memberOf'];
                $result = ldap_search($ldap_conn, $base_dn, $filter, $attributes);

                if ($result && ldap_count_entries($ldap_conn, $result) > 0) {
                    $entries = ldap_get_entries($ldap_conn, $result);
                    $groups = $entries[0]['memberof'] ?? [];

                    $is_admin = false;

                    foreach ($groups as $group_dn) {
                        if (stripos($group_dn, "CN=PAM_ADMIN") !== false) {
                            $is_admin = true;
                            break;
                        }
                    }

                    // === Integrasi dengan database lokal ===
                    require('include/koneksi.php'); // koneksi ke MySQL

                    // Cek apakah user sudah ada
                    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($row = mysqli_fetch_assoc($result)) {
                        $user_id = $row['id'];
                    } else {
                        // Insert user baru
                        $stmt = mysqli_prepare($conn, "INSERT INTO users (username) VALUES (?)");
                        mysqli_stmt_bind_param($stmt, "s", $username);
                        mysqli_stmt_execute($stmt);
                        $user_id = mysqli_insert_id($conn);
                    }

                    // Set session
                    $_SESSION['user'] = $username;
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role'] = $is_admin ? 'admin' : 'user';

                    // Redirect ke dashboard
                    $redirect = $is_admin ? "admin-test.php" : "user-test.php";
                    header("Location: $redirect");
                    exit();
                } else {
                    $message = "Tidak dapat menemukan informasi grup pengguna.";
                    $message_type = "danger";
                }
            } else {
                $message = "Login error: " . ldap_error($ldap_conn);
                $message_type = "danger";
            }

            ldap_unbind($ldap_conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ACTIVin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: #f5f5f5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
        }

        .container {
            display: flex;
            background: #1c1c1c;
            width: 900px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 20px #0d170f;
        }

        .left {
            flex: 1;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .left img {
            max-width: 100%;
        }

        .right {
            flex: 1;
            padding: 60px 40px;
            color: white;
        }

        .right h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .right h2 span {
            color: #48fcfb;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 93.5%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #555;
            background: #2a2a2a;
            color: #fff;
        }

        .toggle-password {
            position: absolute;
            top: 38px;
            right: 15px;
            cursor: pointer;
            font-size: 18px;
            color: #cccccc;
        }

        .btn-login {
            background: #48fcfb;
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #229799;
            color: white;
        }

        .alert {
            padding: 10px;
            background-color: #e74c3c;
            color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="left">
            <img src="./asset/background.jpg" alt="Login Illustration">
        </div>
        <div class="right">
            <h2>ACTIV<span>in</span><br>Selamat Datang!</h2>

            <?php if (!empty($message)): ?>
                <div class="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username :</label>
                        <input type="text" id="username" name="username" placeholder="Masukan username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password :</label>
                        <input type="password" id="password" name="password" placeholder="Masukan password" required>
                        <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
                    </div>

                    <button type="submit" class="btn-login">Log In</button>
            </form>
        </div>
    </div>

    <script>
        const toggle = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        toggle.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>

</html>