<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require('include/koneksi.php');

$user_id = $_SESSION['user_id'];
$search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
$page = (int)($_POST['page'] ?? 1);
$limit = 5;
$offset = ($page - 1) * $limit;

// Hitung total data
$count_sql = "SELECT COUNT(*) as total FROM tambah_onsite 
              WHERE user_id = $user_id 
              AND (status_pembayaran = 'Disetujui' OR status_pembayaran = 'Ditolak')
              AND (tanggal LIKE '%$search%' OR keterangan_kegiatan LIKE '%$search%')";
$total_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Ambil data
$sql = "SELECT * FROM tambah_onsite 
        WHERE user_id = $user_id 
        AND (status_pembayaran = 'Disetujui' OR status_pembayaran = 'Ditolak') 
        AND (tanggal LIKE '%$search%' OR keterangan_kegiatan LIKE '%$search%') 
        ORDER BY id DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);
?>

<table class="table table-bordered align-middle table-rounded rounded-4 overflow-hidden shadow">
  <thead class="table-dark">
    <tr>
      <th>Anggota</th>
      <th>Tanggal</th>
      <th>Lokasi</th>
      <th>Detail</th>
      <th>Waktu</th>
      <th>Dokumentasi</th>
      <th>Biaya</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
      <tr>
        <td>Asy<br>Syams<br>Fajar<br>Farza</td>
        <td><?= $row['tanggal'] ?></td>
        <td style="width: 200px; height: 200px;">
          <?php if ($row['latitude'] && $row['longitude']) : ?>
            <iframe src="https://maps.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=es&z=14&output=embed"
              width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
          <?php else : ?>
            Tidak tersedia
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row['keterangan_kegiatan']) ?></td>
        <td><?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
        <td>
          <?php if (!empty($row['dokumentasi'])) : ?>
            <a href="../uploads/<?= $row['dokumentasi'] ?>" target="_blank">Lihat</a>
          <?php else : ?>
            Tidak ada
          <?php endif; ?>
        </td>
        <td style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></td>
        <td>
          <span class="badge bg-<?= $row['status_pembayaran'] == 'Disetujui' ? 'success' : 'danger' ?>">
            <?= $row['status_pembayaran'] ?>
          </span>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="#" class="pagination-link" data-page="<?= $page - 1 ?>">&laquo;</a>
  <?php endif; ?>

  <?php
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    for ($i = $start; $i <= $end; $i++): ?>
    <a href="#" class="pagination-link <?= ($i == $page) ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
  <?php endfor; ?>

  <?php if ($page < $total_pages): ?>
    <a href="#" class="pagination-link" data-page="<?= $page + 1 ?>">&raquo;</a>
  <?php endif; ?>
</div>
