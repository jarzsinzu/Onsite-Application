<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require('../include/koneksi.php');

$user_id = $_SESSION['user_id'] ?? 0;
$search = $_POST['search'] ?? '';
$page = $_POST['page'] ?? 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

$search = mysqli_real_escape_string($conn, $search);

// Hitung total data (Hanya status Disetujui atau Ditolak)
$count_query = "SELECT COUNT(*) as total FROM tambah_onsite 
                WHERE user_id = $user_id 
                AND status_pembayaran IN ('Disetujui', 'Ditolak')";
if (!empty($search)) {
    $count_query .= " AND (tanggal LIKE '%$search%' 
                    OR keterangan_kegiatan LIKE '%$search%' 
                    OR status_pembayaran LIKE '%$search%')";
}
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Query data (Hanya status Disetujui atau Ditolak)
$data_query = "SELECT * FROM tambah_onsite WHERE user_id = $user_id AND status_pembayaran = 'Menunggu'";

if (!empty($search)) {
    $data_query .= " AND (tanggal LIKE '%$search%' 
                    OR keterangan_kegiatan LIKE '%$search%' 
                    OR status_pembayaran LIKE '%$search%')";
}
$data_query .= " ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $data_query);
?>

<table class="table table-bordered align-middle table-rounded">
    <thead class="table-dark">
        <tr style="text-align: center;">
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
                        $anggota_query = "
                            SELECT a.nama 
                            FROM tim_onsite t
                            JOIN anggota a ON t.id_anggota = a.id
                            WHERE t.id_onsite = $id_onsite
                        ";
                        $anggota_result = mysqli_query($conn, $anggota_query);
                        while ($anggota = mysqli_fetch_assoc($anggota_result)) {
                            echo "<li>" . htmlspecialchars($anggota['nama']) . "</li>";
                        }
                        ?>
                    </ul>
                </td>

                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                <td style="width: 180px; height: 180px;">
                    <?php if ($row['latitude'] && $row['longitude']) : ?>
                        <iframe src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed"
                            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    <?php else : ?>
                        <em>Lokasi tidak tersedia</em>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['keterangan_kegiatan']) ?></td>
                <td><?= date('H:i', strtotime($row['jam_mulai'])) ?>-<?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
                <td>
                    <?php if (!empty($row['dokumentasi'])) : ?>
                        <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank">Lihat</a>
                    <?php else : ?>
                        Tidak ada
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['file_csv'])): ?>
                        <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>">CSV</a>
                    <?php endif; ?>
                </td>
                <td style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></td>
                <td>
                    <?php
                    $status = $row['status_pembayaran'];
                    $badge = 'warning';
                    if ($status === 'Disetujui') $badge = 'success';
                    elseif ($status === 'Ditolak') $badge = 'danger';
                    ?>
                    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php if ($total_pages > 1): ?>
        <!-- First Page -->
        <?php if ($page > 1): ?>
            <a href="#" class="pagination-link" data-page="1">&laquo;</a>
        <?php else: ?>
            <span class="disabled">&laquo;</span>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php
        $range = 2;
        $start = max(1, $page - $range);
        $end = min($total_pages, $page + $range);

        if ($start > 1) {
            echo '<a href="#" class="pagination-link" data-page="1">1</a>';
            if ($start > 2) echo '<span>...</span>';
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $page ? 'active' : '';
            echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a>";
        }

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo '<span>...</span>';
            echo "<a href='#' class='pagination-link' data-page='$total_pages'>$total_pages</a>";
        }
        ?>

        <!-- Last Page -->
        <?php if ($page < $total_pages): ?>
            <a href="#" class="pagination-link" data-page="<?= $total_pages ?>">&raquo;</a>
        <?php else: ?>
            <span class="disabled">&raquo;</span>
        <?php endif; ?>
    <?php endif; ?>
</div>
