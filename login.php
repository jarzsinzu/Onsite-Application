<?php
session_start();

// LDAP settings
$ldap_server = "ldap://172.10.10.70";
$ldap_port = 663;
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
  <link rel="stylesheet" href="asset/css/login.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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