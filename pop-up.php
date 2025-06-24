<h4>Form Tambah Data</h4>
<form action="proses_tambah.php" method="POST">
  <div class="">
    <label class="">Nama</label>
    <input type="text" name="nama" class="" required>
  </div>
  <div class="">
    <label class="">Lokasi</label>
    <input type="text" name="lokasi" class="" required>
  </div>
  <div class="">
    <label class="">Tanggal</label>
    <input type="date" name="tanggal" class="f" required>
  </div>
  <button type="submit" class="">Simpan</button>
  <button type="button" class="" onclick="closePopup()">Batal</button>
</form>
