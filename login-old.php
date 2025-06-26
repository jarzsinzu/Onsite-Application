<?php
session_start();

$ldap_server = "ldap://172.10.10.70";
$ldap_port = 389;
$domain = "training.local";
$base_dn = "DC=training,DC=local";

$message = "";
$message_type = "";

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
                $filter = "(sAMAccountName=$username)";
                $attributes = ['memberOf'];
                $result = ldap_search($ldap_conn, $base_dn, $filter, $attributes);

                if($result && ldap_count_entries($ldap_conn, $result) > 0) {
                    $entries = ldap_get_entries($ldap_conn, $result);
                    $groups = $entries[0]['memberof'] ?? [];

                    $is_admin = false;

                    foreach ($groups as $group_dn) {
                        if (stripos($group_dn, "CN=PAM_ADMIN") !== false) {
                            $is_admin = true;
                            break;
                        }
                    }

                    $_SESSION['user'] = $username;
                    $_SESSION['role'] = $is_admin ? 'admin' : 'user';

                    if ($is_admin) {
                        header("Location: admin/dashboard-admin.php");
                    } else {
                        header("Location: user/dahboard-user.php");
                    }
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
    <title>Login</title>

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
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
            background: #ffffff;
            flex: 1;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .left img {
            max-width: 100%;
            height: auto;
        }

        .right {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right h2 {
            color: #ffffff;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .right h2 span {
            color: #48fcfb
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #555;
            background: #2a2a2a;
            color: #fff;
            font-size: 14px;
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
            background: #e49425;
            color: #fff;
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

        toggle.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
