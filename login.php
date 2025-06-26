<?php
session_start();

// LDAP settings
$ldap_server = "ldap://172.10.10.70";
$ldap_port = 389;
$domain = "training.local";
$base_dn = "DC=training,DC=local";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username dan Password wajib diisi!";
    } else {
        $ldap_conn = ldap_connect($ldap_server, $ldap_port);
        if (!$ldap_conn) {
            $message = "Gagal terhubung ke server LDAP.";
        } else {
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            $ldap_user = $username . '@' . $domain;
            $bind = @ldap_bind($ldap_conn, $ldap_user, $password);

            if ($bind) {
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

                    require('include/koneksi.php');
                    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($row = mysqli_fetch_assoc($result)) {
                        $user_id = $row['id'];
                    } else {
                        $stmt = mysqli_prepare($conn, "INSERT INTO users (username) VALUES (?)");
                        mysqli_stmt_bind_param($stmt, "s", $username);
                        mysqli_stmt_execute($stmt);
                        $user_id = mysqli_insert_id($conn);
                    }

                    $_SESSION['user'] = $username;
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role'] = $is_admin ? 'admin' : 'user';

                    header("Location: " . ($is_admin ? "admin/dashboard-admin.php" : "user/dashboard-user.php"));
                    exit();
                } else {
                    $message = "Tidak dapat menemukan informasi grup pengguna.";
                }
            } else {
                $message = "Login error: Invalid credentials";
            }

            ldap_unbind($ldap_conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login ACTIVin Account</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
      justify-content: center;
      align-items: center;
      background-color: #48cfcb;
    }

    .container {
      position: relative;
      display: flex;
      width: 900px;
      height: 500px;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      background-color: #1e1e1e;
    }

    .card-logo {
      position: absolute;
      top: 25px;
      left: 25px;
      width: 150px;
      height: auto;
      z-index: 10;
    }

    .welcome-section {
      flex: 1;
      background: url('asset/test.png') no-repeat bottom / cover;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .welcome-text {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-align: left;
    }

    .welcome-text p,
    .welcome-text h2 {
      font-size: 50px;
      color: #fff;
      margin: 0;
    }

    .login-section {
      flex: 1;
      background: #1e1e1e;
      color: #fff;
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-section h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .login-section p {
      margin-bottom: 20px;
      font-size: 14px;
    }

    .error-message {
      color: red;
      font-size: 14px;
      margin-bottom: 15px;
    }

    .input-group {
      position: relative;
      margin-bottom: 15px;
    }

    .input-group input {
      width: 100%;
      height: 44px;
      padding: 10px 42px 10px 42px;
      font-size: 14px;
      border: 1px solid #f5f5f5;
      border-radius: 5px;
      background: #1c1c1c;
      color: #fff;
    }

    .input-icon-left,
    .input-icon-right {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      font-size: 20px;
      color: #cccccc;
    }

    .input-icon-left { left: 12px; pointer-events: none; }
    .input-icon-right { right: 12px; cursor: pointer; }

    button {
      width: 100%;
      padding: 12px;
      background: #48cfcb;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #227779;
    }

    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #1c1c1c inset !important;
      -webkit-text-fill-color: #fff !important;
      transition: background-color 5000s ease-in-out 0s;
    }
  </style>
</head>

<body>
  <div class="container">
    <img src="asset/logo-E.png" alt="Logo" class="card-logo">

    <div class="welcome-section">
      <div class="welcome-text">
        <p>Hello,</p>
        <h2>Welcome!</h2>
      </div>
    </div>

    <div class="login-section">
      <h2>Login</h2>
      <p>Enter your account details</p>

      <?php if (!empty($message)): ?>
        <div class="error-message"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="input-group">
          <i class="bi bi-person input-icon-left"></i>
          <input type="text" name="username" placeholder="Username" autocomplete="username" />
        </div>

        <div class="input-group">
          <i class="bi bi-key input-icon-left"></i>
          <input type="password" name="password" id="password" placeholder="Password" />
          <i class="bi bi-eye-slash input-icon-right toggle-password" id="togglePassword"></i>
        </div>

        <button type="submit">Login</button>
      </form>
    </div>
  </div>

  <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
      const input = document.getElementById('password');
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });
  </script>
</body>
</html>
