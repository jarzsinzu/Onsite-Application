<?php
    /* =====================================================
   SESSION MANAGEMENT
   ===================================================== */
    session_start(); // Memulai session untuk menyimpan data user setelah login

    /* =====================================================
   KONFIGURASI LDAP (LIGHTWEIGHT DIRECTORY ACCESS PROTOCOL)
   ===================================================== */
    $ldap_server = "ldap://172.10.10.70";  // Server LDAP (Active Directory)
    $ldap_port   = 663;                    // Port LDAP
    $domain      = "training.local";       // Domain untuk autentikasi
    $base_dn     = "DC=training,DC=local"; // Base Distinguished Name untuk pencarian

    $message = ""; // Variable untuk menyimpan pesan error/success

    /* =====================================================
   PROSES LOGIN - KETIKA FORM DI-SUBMIT
   ===================================================== */
    // Mengecek apakah request adalah POST (form di-submit)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        /* =====================================================
     MENGAMBIL DATA DARI FORM
     ===================================================== */
        $username = trim($_POST['username'] ?? ''); // Mengambil username dan menghapus whitespace
        $password = $_POST['password'] ?? '';       // Mengambil password

        /* =====================================================
     VALIDASI RECAPTCHA
     ===================================================== */
        if (isset($_POST['recaptcha_token'])) {
            $token  = $_POST['recaptcha_token'];
            $secret = '6LeixnorAAAAAOQ98Ugrhq9pLSDjpXtf-liPHNDS';

            $verify          = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$token");
            $captcha_success = json_decode($verify);

            // DEBUG - tampilkan ini
            // echo "Score: " . $captcha_success->score . "<br>";
            // echo "Threshold: 0.95<br>";
            // echo "Passed: " . ($captcha_success->score >= 0.95 ? 'Yes' : 'No') . "<br>";
            // die(); // Stop eksekusi

            if ($captcha_success->success && $captcha_success->score >= 0.5) {
                // Login berhasil
            } else {
                $message = "Verifikasi captcha gagal. Score: " . $captcha_success->score;
            }

            /* =====================================================
       VALIDASI INPUT FORM
       ===================================================== */
            // Mengecek apakah username dan password tidak kosong
            if (empty($username) || empty($password)) {
                $message = "Username dan Password wajib diisi!";
            } else {
                /* =====================================================
         KONEKSI KE LDAP SERVER
         ===================================================== */
                $ldap_conn = ldap_connect($ldap_server, $ldap_port); // Menghubungkan ke server LDAP
                if (! $ldap_conn) {
                    $message = "Gagal terhubung ke server LDAP.";
                } else {
                    /* =====================================================
           KONFIGURASI LDAP CONNECTION
           ===================================================== */
                    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set protokol LDAP v3
                    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);        // Disable referrals

                    /* =====================================================
           AUTENTIKASI LDAP
           ===================================================== */
                                                                                // Proses login & mencari data user di LDAP sesuai grup
                    $ldap_user = $username . '@' . $domain;                     // Format username untuk LDAP
                    $bind      = @ldap_bind($ldap_conn, $ldap_user, $password); // Attempt to bind (login)

                    if ($bind) {
                        /* =====================================================
             PENCARIAN USER DI LDAP & CEK GRUP
             ===================================================== */
                        $filter     = "(sAMAccountName=$username)";                            // Filter untuk mencari user
                        $attributes = ['memberOf'];                                            // Atribut yang akan diambil (grup membership)
                        $result     = ldap_search($ldap_conn, $base_dn, $filter, $attributes); // Pencarian LDAP

                        if ($result && ldap_count_entries($ldap_conn, $result) > 0) {
                            $entries = ldap_get_entries($ldap_conn, $result); // Ambil hasil pencarian
                            $groups  = $entries[0]['memberof'] ?? [];         // Ambil grup membership

                            /* =====================================================
               PENGECEKAN ROLE ADMIN
               ===================================================== */
                            $is_admin = false; // Default bukan admin
                            foreach ($groups as $group_dn) {
                                // Cek apakah user adalah member dari grup PAM_ADMIN
                                if (stripos($group_dn, "CN=PAM_ADMIN") !== false) {
                                    $is_admin = true;
                                    break;
                                }
                            }

                            /* =====================================================
               PENGECEKAN & PENYIMPANAN USER KE DATABASE
               ===================================================== */
                                                           // Mengecek apakah user sudah ada di tabel user berdasarkan username
                            require 'include/koneksi.php'; // Include file koneksi database
                            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                            mysqli_stmt_bind_param($stmt, "s", $username); // Bind parameter untuk mencegah SQL injection
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            if ($row = mysqli_fetch_assoc($result)) {
                                // User sudah ada, ambil ID
                                $user_id = $row['id'];
                            } else {
                                // User belum ada, buat user baru
                                $stmt = mysqli_prepare($conn, "INSERT INTO users (username) VALUES (?)");
                                mysqli_stmt_bind_param($stmt, "s", $username);
                                mysqli_stmt_execute($stmt);
                                $user_id = mysqli_insert_id($conn); // Ambil ID user yang baru dibuat
                            }

                            /* =====================================================
               MENYIMPAN DATA KE SESSION
               ===================================================== */
                            // Menyimpan data login ke session
                            $_SESSION['user']          = $username;
                            $_SESSION['user_id']       = $user_id;
                            $_SESSION['role']          = $is_admin ? 'admin' : 'user';
                            $_SESSION['login_success'] = true;

                            /* =====================================================
               REDIRECT BERDASARKAN ROLE
               ===================================================== */
                            // Redirect sesuai role user/admin
                            if ($is_admin) {
                                $_SESSION['role'] = 'admin';              // default role = admin
                                header("Location: admin/pilih-role.php"); // Arahkan ke halaman pemilihan role
                                exit();
                            } else {
                                $_SESSION['role']        = 'user';
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

                    /* =====================================================
           MENUTUP KONEKSI LDAP
           ===================================================== */
                    ldap_unbind($ldap_conn); // Tutup koneksi LDAP
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <!-- =====================================================
       META TAGS & TITLE
       ===================================================== -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login ACTIVin Account</title>

  <!-- =====================================================
       CSS DEPENDENCIES
       ===================================================== -->
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="asset/css/login.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <!-- =====================================================
       LOGO & FAVICON
       ===================================================== -->
<link rel="icon" href="../asset/ACTIVin.png" type="image/png">
  <!-- =====================================================
       JAVASCRIPT DEPENDENCIES
       ===================================================== -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- =====================================================
       LOAD RECAPTCHA SCRIPT
       ===================================================== -->
  <script src="https://www.google.com/recaptcha/api.js?render=6LeixnorAAAAAFNnaCCwBkvHSuTTshjUBH-G1rOW"></script>
</head>

<body>
  <!-- =====================================================
       MAIN CONTAINER
       ===================================================== -->
  <div class="main-container">
    <div class="login-card">
      <!-- Logo positioned absolutely -->
      <img src="asset/logo-E.png" alt="Logo" class="card-logo">

      <!-- Bootstrap fluid container -->
      <div class="container-fluid p-0">
        <div class="row g-0">
          <!-- =====================================================
               WELCOME SECTION (KIRI) - HANYA TAMPIL DI DESKTOP
               ===================================================== -->
          <div class="col-lg-6 d-none d-lg-block">
            <div class="welcome-section">
              <div class="welcome-text">
                <p>Hello,</p>
                <h2>Welcome!</h2>
              </div>
            </div>
          </div>

          <!-- =====================================================
               LOGIN SECTION (KANAN)
               ===================================================== -->
          <div class="col-lg-6 col-12">
            <div class="login-section">
              <h2>Login</h2>
              <p class="subtitle">Enter your account details</p>

              <!-- =====================================================
                   TAMPILKAN PESAN ERROR/SUCCESS
                   ===================================================== -->
              <?php if (! empty($message)): ?>
                <div class="custom-alert">
                  <i class="bi bi-exclamation-circle-fill alert-icon"></i>
                  <span><?php echo htmlspecialchars($message) ?></span> <!-- Escape HTML untuk keamanan -->
                </div>
              <?php endif; ?>

              <!-- =====================================================
                   FORM LOGIN
                   ===================================================== -->
              <form method="POST" action="" novalidate id="loginForm">
                <!-- Username Input -->
                <div class="input-group-custom">
                  <i class="bi bi-person input-icon-left"></i>
                  <input type="text" name="username" class="form-control-custom" placeholder="Username" autocomplete="username" />
                </div>

                <!-- Password Input -->
                <div class="input-group-custom">
                  <i class="bi bi-key input-icon-left"></i>
                  <input type="password" name="password" id="password" class="form-control-custom" placeholder="Password" />
                  <i class="bi bi-eye-slash input-icon-right toggle-password" id="togglePassword"></i>
                </div>

                <!-- reCAPTCHA -->
                <input type="hidden" name="recaptcha_token" id="recaptchaToken">

                <!-- Submit Button -->
                <button type="submit" class="btn-login">Login</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- =====================================================
       JAVASCRIPT UNTUK INTERAKTIVITAS
       ===================================================== -->
  <script>
    /* =====================================================
       TOGGLE PASSWORD VISIBILITY
       ===================================================== */
    // Fungsi untuk show/hide password
    document.getElementById('togglePassword').addEventListener('click', function() {
      const input = document.getElementById('password');
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);

      // Toggle icon mata
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });

    /* =====================================================
       EFEK INTERAKTIF PADA INPUT
       ===================================================== */
    // Menambahkan efek warna pada icon saat focus
    document.querySelectorAll('.form-control-custom').forEach(input => {
      input.addEventListener('focus', function() {
        // Ubah warna icon menjadi accent color saat input fokus
        this.parentElement.querySelector('.input-icon-left').style.color = '#48cfcb';
      });

      input.addEventListener('blur', function() {
        // Kembalikan warna icon saat input tidak fokus
        this.parentElement.querySelector('.input-icon-left').style.color = '#999';
      });
    });

    /* =====================================================
       VALIDASI FORM CLIENT-SIDE
       ===================================================== */
    // Validasi form sebelum submit
    document.querySelector('form').addEventListener('submit', function(e) {
      const username = document.querySelector('input[name="username"]').value.trim();
      const password = document.querySelector('input[name="password"]').value;

      // Hapus alert yang ada jika bukan dari server
      const existingAlert = document.querySelector('.custom-alert');
      if (existingAlert && !existingAlert.querySelector('.alert-icon')) {
        existingAlert.remove();
      }

      // Jika username atau password kosong
      if (!username || !password) {
        e.preventDefault(); // Mencegah form submit

        // Buat dan tampilkan alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'custom-alert';
        alertDiv.innerHTML = `
          <i class="bi bi-exclamation-circle-fill alert-icon"></i>
          <span>Username dan Password wajib diisi!</span>
        `;

        // Insert alert sebelum form
        const form = document.querySelector('form');
        form.parentNode.insertBefore(alertDiv, form);

        // Focus pada field yang kosong
        if (!username) {
          document.querySelector('input[name="username"]').focus();
        } else if (!password) {
          document.querySelector('input[name="password"]').focus();
        }
      }
    });

    /* =====================================================
       MENGHAPUS ALERT SAAT USER MENGETIK
       ===================================================== */
    // Hapus alert validasi saat user mulai mengetik
    document.querySelectorAll('input[name="username"], input[name="password"]').forEach(input => {
      input.addEventListener('input', function() {
        const alert = document.querySelector('.custom-alert');
        // Hapus alert jika bukan pesan error dari server
        if (alert && !alert.textContent.includes('Login error') && !alert.textContent.includes('Gagal terhubung') && !alert.textContent.includes('Tidak dapat menemukan')) {
          alert.remove();
        }
      });
    });

    /* =====================================================
        RECAPTCHA V3 INTEGRATION
       ===================================================== */
    document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Cegah submit langsung

    grecaptcha.execute('6LeixnorAAAAAFNnaCCwBkvHSuTTshjUBH-G1rOW', {action: 'login'}).then(function(token) {
        // Masukkan token ke hidden field
        document.getElementById('recaptchaToken').value = token;

        // Submit form setelah dapat token
        this.submit();
    }.bind(this));
    });
  </script>

</body>

</html>