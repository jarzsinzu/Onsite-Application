<?php
require('../include/koneksi.php');

// Mengambil data search & pagination dari request POST
$search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
$page = max((int)($_POST['page'] ?? 1), 1);
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

// // Menyiapkan query
$base_query = "
    FROM tambah_onsite o
    LEFT JOIN tim_onsite t ON o.id = t.id_onsite
    LEFT JOIN anggota a ON t.id_anggota = a.id
";

$where = "";
if (!empty($search)) {
    $where = "WHERE o.tanggal LIKE '%$search%' 
           OR o.keterangan_kegiatan LIKE '%$search%' 
           OR o.status_pembayaran LIKE '%$search%' 
           OR a.nama LIKE '%$search%'";
}

// Menghitung total data untuk pagination
$count_query = "SELECT COUNT(DISTINCT o.id) as total $base_query $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Query ambil data sesuai halaman
$data_query = "
    SELECT DISTINCT o.* 
    $base_query 
    $where 
    ORDER BY o.id DESC 
    LIMIT $offset, $records_per_page
";
$result = mysqli_query($conn, $data_query);
?>


<table class="table table-bordered align-middle table-rounded rounded-4 overflow-hidden shadow">
    <thead class="table-dark">
        <tr style="text-align: center;">
            <th style="width: 120px;">Anggota</th>
            <th style="width: 120px;">Tanggal</th>
            <th>Lokasi</th>
            <th style="width: 150px;">Detail Kegiatan</th>
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
                    <?php else : ?>
                        Tidak ada
                    <?php endif; ?>
                </td>
                <td style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></td>
                <td>
                    <form method="POST" action="ubah-status.php" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <select name="status_pembayaran" class="form-select status-dropdown" data-id="<?= $row['id'] ?>">
                            <option value="Menunggu" <?= $row['status_pembayaran'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Disetujui" <?= $row['status_pembayaran'] == 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
                            <option value="Ditolak" <?= $row['status_pembayaran'] == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </form>

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
    <!-- Menentukan batas awal dan akhir nomor halaman -->
    <?php
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    // Halaman pertama
    if ($start > 1) {
        echo '<a href="#" class="pagination-link" data-page="1">1</a>';
        if ($start > 2) echo '<span>...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? 'active' : '';
        echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a>";
    }
    // Halaman akhir
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) echo '<span>...</span>';
        echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
    }
    ?>
    <?php if ($page < $total_pages): ?>
        <a href="#" class="pagination-link" data-page="<?= $page + 1 ?>">&raquo;</a>
    <?php endif; ?>
</div>

<!-- Mengubah warna dropdown sesuai status yang ditentukan -->
<script>
    // Update class warna baru sesuai dengan status yang ditentukan
    function updateStatusColor(select) {
        const value = select.value;
        select.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');

        if (value === 'Disetujui') {
            select.classList.add('bg-success', 'text-white');
        } else if (value === 'Ditolak') {
            select.classList.add('bg-danger', 'text-white');
        } else if (value === 'Menunggu') {
            select.classList.add('bg-warning');
        }
    }

    // Menginisialisasi semua dropdown
    document.querySelectorAll('.status-dropdown').forEach(select => {
        updateStatusColor(select);
        select.addEventListener('change', () => {
            updateStatusColor(select);
            select.closest('form').submit();
        });
    });

    if (typeof setupStatusDropdowns === 'function') {
        setupStatusDropdowns();
    }
</script>