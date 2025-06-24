<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Contoh Pop-up AJAX</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .popup-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 999;
      justify-content: center;
      align-items: center;
    }

    .popup-content {
      background: white;
      padding: 20px;
      border-radius: 10px;
      min-width: 400px;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2>Contoh Tambah Data</h2>
  <button class="btn btn-primary" onclick="loadPopupForm()">Tambah Data</button>
</div>

<!-- Pop-up Container -->
<div class="popup-overlay" id="popupOverlay">
  <div class="popup-content" id="popupContent">
    <!-- Isi form dari file lain akan dimuat di sini -->
  </div>
</div>

<script>
  function loadPopupForm() {
    fetch('pop-up.php')
      .then(response => response.text())
      .then(html => {
        document.getElementById('popupContent').innerHTML = html;
        document.getElementById('popupOverlay').style.display = 'flex';
      });
  }

  function closePopup() {
    document.getElementById('popupOverlay').style.display = 'none';
  }
</script>
</body>
</html>
