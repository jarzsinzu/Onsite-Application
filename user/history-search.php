<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require('../include/koneksi.php');

$user_id = $_SESSION['user_id'];
$search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
$page = max((int)($_POST['page'] ?? 1), 1);
$limit = 5;
$offset = ($page - 1) * $limit;

// Hitung total data untuk pagination
$count_sql = "
  SELECT COUNT(*) as total 
  FROM tambah_onsite 
  WHERE user_id = $user_id 
    AND status_pembayaran IN ('Disetujui', 'Ditolak')
    AND (tanggal LIKE '%$search%' OR keterangan_kegiatan LIKE '%$search%')
";
$total_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Ambil data berdasarkan pencarian & halaman
$sql = "SELECT * FROM tambah_onsite 
        WHERE user_id = $user_id 
        AND status_pembayaran IN ('Disetujui', 'Ditolak') 
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);
?>

<table class="table table-bordered align-middle table-rounded rounded-4 overflow-hidden shadow">
  <thead class="table-dark text-center">
    <tr>
      <th style="width: 120px;">Anggota</th>
      <th style="width: 120px;">Tanggal</th>
      <th>Lokasi</th>
      <th>Detail Kegiatan</th>
      <th style="width: 120px;">Waktu</th>
      <th><i class="bi bi-folder2 fs-5"></i></th>
      <th><i class="bi bi-filetype-csv fs-5"></i></th>
      <th>Biaya</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
      <tr>
        <td style="text-align: left;">
          <ul style="padding-left: 15px;">
            <?php
            $id_onsite = $row['id'];
            $anggota_result = mysqli_query($conn, "
              SELECT a.nama 
              FROM tim_onsite t
              JOIN anggota a ON t.id_anggota = a.id
              WHERE t.id_onsite = $id_onsite
            ");
            while ($anggota = mysqli_fetch_assoc($anggota_result)) {
              echo "<li>" . htmlspecialchars($anggota['nama']) . "</li>";
            }
            ?>
          </ul>
        </td>

        <td><?= htmlspecialchars($row['tanggal']) ?></td>
        <td style="width: 180px; height: 180px;">
          <?php if ($row['latitude'] && $row['longitude']) : ?>
            <iframe
              src="https://maps.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=es&z=14&output=embed"
              width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
          <?php else : ?>
            Tidak tersedia
          <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($row['keterangan_kegiatan']) ?></td>
        <td><?= date('H:i', strtotime($row['jam_mulai'])) ?>-<?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
        <td class="text-center">
          <?php if (!empty($row['dokumentasi'])) : ?>
            <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank">Lihat</a>
          <?php else : ?>
            Tidak ada
          <?php endif; ?>
        </td>

        <td class="text-center">
          <?php if (!empty($row['file_csv'])): ?>
            <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>">CSV</a>
          <?php endif; ?>
        </td>

        <td style="color: #006400; font-weight:bold;">
          Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?>
        </td>
        <td class="text-center">
          <?php
          $status = $row['status_pembayaran'];
          $badge = $status === 'Disetujui' ? 'success' : 'danger';
          ?>
          <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- Pagination -->
<div class="pagination mt-3">
  <?php if ($page > 1): ?>
    <a href="#" class="pagination-link" data-page="<?= $page - 1 ?>">&laquo;</a>
  <?php endif; ?>

  <?php
  $range = 2;
  $start = max(1, $page - $range);
  $end = min($total_pages, $page + $range);
  for ($i = $start; $i <= $end; $i++): ?>
    <a href="#" class="pagination-link <?= ($i == $page) ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
  <?php endfor; ?>

  <?php if ($page < $total_pages): ?>
    <a href="#" class="pagination-link" data-page="<?= $page + 1 ?>">&raquo;</a>
  <?php endif; ?>
</div>