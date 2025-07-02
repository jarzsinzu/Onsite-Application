<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require('../include/koneksi.php');

// Ambil data dari session & input
$user_id = $_SESSION['user_id'] ?? 0;
$search = $_POST['search'] ?? '';
$page = $_POST['page'] ?? 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

$search = mysqli_real_escape_string($conn, $search);

// Hitung total data untuk pagination
$count_query = "SELECT COUNT(DISTINCT to1.id) as total 
    FROM tambah_onsite to1
    LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
    LEFT JOIN anggota a ON t.id_anggota = a.id
    WHERE to1.user_id = $user_id
    AND to1.status_pembayaran = 'Menunggu'";
if (!empty($search)) {
    $count_query .= " AND (
        to1.tanggal LIKE '%$search%' 
        OR to1.keterangan_kegiatan LIKE '%$search%' 
        OR to1.status_pembayaran LIKE '%$search%' 
        OR a.nama LIKE '%$search%'
    )";
}

$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Query ambil data
$data_query = "SELECT DISTINCT to1.* 
    FROM tambah_onsite to1
    LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
    LEFT JOIN anggota a ON t.id_anggota = a.id
    WHERE to1.user_id = $user_id
    AND to1.status_pembayaran = 'Menunggu'";
if (!empty($search)) {
    $data_query .= " AND (
        to1.tanggal LIKE '%$search%' 
        OR to1.keterangan_kegiatan LIKE '%$search%' 
        OR to1.status_pembayaran LIKE '%$search%' 
        OR a.nama LIKE '%$search%'
    )";
}
$data_query .= " ORDER BY to1.id DESC LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $data_query);
?>

<style>
    .onsite-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .onsite-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .onsite-badge {
        background-color: #48cfcb;
        color: #fff;
        border-radius: 20px;
        padding: 5px 12px;
        font-size: 0.85rem;
        margin: 2px;
        display: inline-block;
    }

    .map-box {
        width: 100%;
        max-width: 300px;
        height: 180px;
        border-radius: 10px;
        overflow: hidden;
    }

    .onsite-details {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 20px;
        margin-top: 10px;
    }

    .onsite-info {
        flex: 1;
        min-width: 250px;
    }

    .onsite-footer {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .onsite-footer span {
        font-weight: 600;
    }

    .badge-status {
        padding: 6px 14px;
        border-radius: 30px;
        font-weight: 500;
    }

    .badge-status.warning { background-color: #fff4cc; color: #b38f00; }
    .badge-status.success { background-color: #d4edda; color: #155724; }
    .badge-status.danger { background-color: #f8d7da; color: #721c24; }

    .onsite-files a {
        margin-right: 10px;
        text-decoration: none;
        color: #0d6efd;
    }

    @media (max-width: 768px) {
        .onsite-details { flex-direction: column; }
        .onsite-footer { flex-direction: column; gap: 10px; }
    }
</style>

<?php while ($row = mysqli_fetch_assoc($result)) : ?>
    <div class="onsite-card">
        <div class="onsite-header">
            <div>
                <strong><?= htmlspecialchars($row['keterangan_kegiatan']) ?></strong><br>
                <small><?= date('d M Y', strtotime($row['tanggal'])) ?> | <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></small>
            </div>
            <div>
                <?php
                $status = $row['status_pembayaran'];
                $statusClass = match($status) {
                    'Menunggu' => 'warning',
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'secondary'
                };
                ?>
                <span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
            </div>
        </div>

        <div class="onsite-details">
            <div class="onsite-info">
                <div><strong>Anggota:</strong><br>
                    <?php
                    $id_onsite = $row['id'];
                    $anggota_result = mysqli_query($conn, "
                        SELECT a.nama 
                        FROM tim_onsite t
                        JOIN anggota a ON t.id_anggota = a.id
                        WHERE t.id_onsite = $id_onsite
                    ");
                    while ($anggota = mysqli_fetch_assoc($anggota_result)) {
                        echo '<span class="onsite-badge">' . htmlspecialchars($anggota['nama']) . '</span>';
                    }
                    ?>
                </div>
                <div class="mt-2"><strong>Biaya:</strong> Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></div>
                <div class="mt-2 onsite-files">
                    <?php if (!empty($row['dokumentasi'])): ?>
                        <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank"><i class="bi bi-folder2-open"></i> Dokumentasi</a>
                    <?php endif; ?>
                    <?php if (!empty($row['file_csv'])): ?>
                        <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="map-box">
                <?php if ($row['latitude'] && $row['longitude']) : ?>
                    <iframe src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed"
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                <?php else : ?>
                    <em>Lokasi tidak tersedia</em>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endwhile; ?>

<!-- Pagination -->
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="#" class="pagination-link" data-page="<?= $page - 1 ?>">&laquo;</a>
    <?php endif; ?>
    <?php
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    if ($start > 1) {
        echo '<a href="#" class="pagination-link" data-page="1">1</a>';
        if ($start > 2) echo '<span>...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? 'active' : '';
        echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a>";
    }
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) echo '<span>...</span>';
        echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
    }
    ?>
    <?php if ($page < $total_pages): ?>
        <a href="#" class="pagination-link" data-page="<?= $page + 1 ?>">&raquo;</a>
    <?php endif; ?>
</div>
