<?php
session_start();

// LDAP settings
$ldap_server = "ldap://172.10.10.70";
$ldap_port = 389;
$domain = "training.local";
$base_dn = "DC=training,DC=local";

$message = "";

// Mengecek apakah request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';


  $recaptcha_secret = '6LeZ73UrAAAAALrTyTDi6OUs2n8KRqlnqbRqiRyh';
  $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

  $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response");
  $captcha_success = json_decode($verify);

  if (!$captcha_success->success) {
    $message = "Verifikasi CAPTCHA gagal. Coba lagi.";
  } else {
    // lanjutkan proses login
    if (empty($username) || empty($password)) {
      $message = "Username dan Password wajib diisi!";
    } else {
      $ldap_conn = ldap_connect($ldap_server, $ldap_port); // Menghubungkan ke server LDAP
      if (!$ldap_conn) {
        $message = "Gagal terhubung ke server LDAP.";
      } else {
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        // Proses login & mencari data user di LDAP sesuai grup
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


            // Mengecek apakah user sudah ada di tabel user berdasarkan username
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

            // Menyimpan data login ke session
            $_SESSION['user'] = $username;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $is_admin ? 'admin' : 'user';
            $_SESSION['login_success'] = true;

            // Redirect sesuai role user/admin
            if ($is_admin) {
              $_SESSION['role'] = 'admin'; // default role = admin
              header("Location: admin/pilih-role.php"); // Arahkan ke halaman pemilihan role
              exit();
            } else {
              $_SESSION['role'] = 'user';
              $_SESSION['active_role'] = 'user';
              header("Location: user/dashboard-user.php");
              exit();
            }
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
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login ACTIVin Account</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background-color: #48cfcb;
      min-height: 100vh;
    }

    .main-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      border-radius: 20px;
    }

    .login-card {
      background-color: #1e1e1e;
      border-radius: 21px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      /* overflow: hidden; */
      max-width: 900px;
      width: 100%;
      position: relative;
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
      background: url('asset/test.png') no-repeat center/cover;
      min-height: 500px;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      border-radius: 20px;
    }

    .welcome-text {
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .welcome-text p {
      font-size: 3rem;
      margin: 0;
      line-height: 1.2;
    }

    .welcome-section h2 {
      font-weight: bold;
      font-size: 3rem;
      margin: 0;
      line-height: 1.2;
    }

    .login-section {
      background: #1e1e1e;
      color: #fff;
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-height: 500px;
      border-radius: 20px;
    }

    .login-section h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .login-section .subtitle {
      margin-bottom: 30px;
      font-size: 14px;
      color: #ccc;
    }

    .custom-alert {
      background-color: #ffe6e6;
      color: #a94442;
      padding: 12px 15px;
      border: 1px solid #f5c6cb;
      border-left: 5px solid #dc3545;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slideIn 0.4s ease forwards;
    }

    .alert-icon {
      font-size: 18px;
      flex-shrink: 0;
    }

    .input-group-custom {
      position: relative;
      margin-bottom: 20px;
    }

    .form-control-custom {
      width: 100%;
      height: 50px;
      padding: 12px 45px;
      font-size: 14px;
      border: 1px solid #fff;
      border-radius: 8px;
      background: #1c1c1c;
      color: #fff;
      transition: all 0.3s ease;
    }

    .form-control-custom:focus {
      border-color: #48cfcb;
      box-shadow: 0 0 0 2px rgba(72, 207, 203, 0.2);
      background: #2a2a2a;
      color: #fff;
      outline: none;
    }

    .form-control-custom::placeholder {
      color: #999;
    }

    .input-icon-left {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #999;
      z-index: 5;
    }

    .input-icon-right {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #999;
      cursor: pointer;
      z-index: 5;
    }

    .btn-login {
      width: 100%;
      padding: 15px;
      background: #48cfcb;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-login:hover {
      background: #227779;
      transform: translateY(-1px);
    }

    .g-recaptcha {

      transform: scale(0.9);
      transform-origin: 0 0;
      max-width: 100%;
      z-index: 10;
      position: relative;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
      .main-container {
        padding: 10px;
      }

      .login-card {
        margin: 10px 0;
        border-radius: 20px;
      }

      .card-logo {
        width: 120px;
        top: 15px;
        left: 15px;
      }

      .welcome-section {
        min-height: 250px;
        order: 2;
      }

      .welcome-text p,
      .welcome-text h2 {
        font-size: 2rem;
      }

      .login-section {
        padding: 30px 25px;
        min-height: auto;
        order: 1;
      }

      .login-section h2 {
        font-size: 24px;
      }
    }

    @media (max-width: 576px) {
      .card-logo {
        width: 100px;
        top: 10px;
        left: 10px;
      }

      .welcome-section {
        min-height: 200px;
        display: none;
        /* Hide welcome section on very small screens */
      }

      .welcome-text p,
      .welcome-text h2 {
        font-size: 1.5rem;
      }

      .login-section {
        padding: 25px 20px;
        border-radius: 15px;
      }

      .login-section h2 {
        font-size: 22px;
        text-align: center;
      }

      .subtitle {
        text-align: center;
      }

      .form-control-custom {
        height: 45px;
        padding: 10px 40px;
      }

      .btn-login {
        padding: 12px;
        font-size: 15px;
      }
    }

    .g-recaptcha {
      /* transform: scale(0.85); */
    }

    /* Fix for autofill */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #2a2a2a inset !important;
      -webkit-text-fill-color: #fff !important;
      transition: background-color 5000s ease-in-out 0s;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-10px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
  </style>
</head>

<body>
  <div class="main-container">
    <div class="login-card">
      <img src="asset/logo-E.png" alt="Logo" class="card-logo">

      <div class="container-fluid p-0">
        <div class="row g-0">
          <!-- Welcome Section -->
          <div class="col-lg-6 d-none d-lg-block">
            <div class="welcome-section">
              <div class="welcome-text">
                <p>Hello,</p>
                <h2>Welcome!</h2>
              </div>
            </div>
          </div>

          <!-- Login Section -->
          <div class="col-lg-6 col-12">
            <div class="login-section">
              <h2>Login</h2>
              <p class="subtitle">Enter your account details</p>

              <?php if (!empty($message)): ?>
                <div class="custom-alert">
                  <i class="bi bi-exclamation-circle-fill alert-icon"></i>
                  <span><?= htmlspecialchars($message) ?></span>
                </div>
              <?php endif; ?>

              <form method="POST" action="" novalidate>
                <div class="input-group-custom">
                  <i class="bi bi-person input-icon-left"></i>
                  <input type="text" name="username" class="form-control-custom" placeholder="Username" autocomplete="username" />
                </div>

                <div class="input-group-custom">
                  <i class="bi bi-key input-icon-left"></i>
                  <input type="password" name="password" id="password" class="form-control-custom" placeholder="Password" />
                  <i class="bi bi-eye-slash input-icon-right toggle-password" id="togglePassword"></i>
                </div>

                <div class="g-recaptcha mb-3" data-sitekey="6LeZ73UrAAAAAFNEx7OVwX0T_v1t10q2FRYy3dCZ"></div>
                <button type="submit" class="btn-login">Login</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
      const input = document.getElementById('password');
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });

    // Add some interactive effects
    document.querySelectorAll('.form-control-custom').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.querySelector('.input-icon-left').style.color = '#48cfcb';
      });

      input.addEventListener('blur', function() {
        this.parentElement.querySelector('.input-icon-left').style.color = '#999';
      });
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const username = document.querySelector('input[name="username"]').value.trim();
      const password = document.querySelector('input[name="password"]').value;

      // Remove existing alert if any
      const existingAlert = document.querySelector('.custom-alert');
      if (existingAlert && !existingAlert.querySelector('.alert-icon')) {
        existingAlert.remove();
      }

      if (!username || !password) {
        e.preventDefault(); // Prevent form submission

        // Create and show alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'custom-alert';
        alertDiv.innerHTML = `
          <i class="bi bi-exclamation-circle-fill alert-icon"></i>
          <span>Username dan Password wajib diisi!</span>
        `;

        // Insert alert before the form
        const form = document.querySelector('form');
        form.parentNode.insertBefore(alertDiv, form);

        // Focus on empty field
        if (!username) {
          document.querySelector('input[name="username"]').focus();
        } else if (!password) {
          document.querySelector('input[name="password"]').focus();
        }
      }
    });

    // Remove alert when user starts typing
    document.querySelectorAll('input[name="username"], input[name="password"]').forEach(input => {
      input.addEventListener('input', function() {
        const alert = document.querySelector('.custom-alert');
        if (alert && !alert.textContent.includes('Login error') && !alert.textContent.includes('Gagal terhubung') && !alert.textContent.includes('Tidak dapat menemukan')) {
          alert.remove();
        }
      });
    });
  </script>
  <script src="https://www.google.com/recaptcha/api.js?hl=id" async defer></script>
</body>

</html>