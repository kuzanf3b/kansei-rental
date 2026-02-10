<?php
// Kembali (Pengembalian) Page - Grid View untuk Admin/Petugas
// File ini di-include dari index.php, jadi $conn dan $_SESSION sudah tersedia

$user = [
    'level' => $_SESSION['user_level'],
    'username' => $_SESSION['username'] ?? '',
    'nama' => $_SESSION['nama'] ?? $_SESSION['username'] ?? ''
];

// Hanya admin dan petugas yang bisa akses
if ($user['level'] === 'member') {
    echo '<script>window.location="index.php?page=dashboard";</script>';
    exit;
}

$success_message = '';
$error_message = '';

// Handle parameter dari transaksi page
$prefill_transaksi = null;
if (isset($_GET['transaksi_id'])) {
    $trans_id = (int)$_GET['transaksi_id'];
    $prefill_sql = "SELECT t.*, m.brand, m.type, m.nopol as mobil_nopol, m.foto, mb.nama as nama_member
                    FROM tbl_transaksi t
                    JOIN tbl_mobil m ON t.nopol = m.nopol
                    JOIN tbl_member mb ON t.nik = mb.nik
                    WHERE t.id_transaksi = ? AND t.status = 'ambil'";
    $prefill_stmt = $conn->prepare($prefill_sql);
    $prefill_stmt->bind_param("i", $trans_id);
    $prefill_stmt->execute();
    $prefill_result = $prefill_stmt->get_result();
    if ($prefill_result->num_rows > 0) {
        $prefill_transaksi = $prefill_result->fetch_assoc();
    }
}

// Handle POST (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kembali = isset($_POST['id_kembali']) ? (int)$_POST['id_kembali'] : 0;
    $id_transaksi = (int)$_POST['id_transaksi'];
    $tgl_kembali_aktual = $_POST['tgl_kembali'];
    $kondisi_mobil = mysqli_real_escape_string($conn, $_POST['kondisi_mobil']);
    $denda = (float)$_POST['denda'];

    if ($id_kembali > 0) {
        // Update
        $sql = "UPDATE tbl_kembali SET tgl_kembali = ?, kondisi_mobil = ?, denda = ? WHERE id_kembali = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $tgl_kembali_aktual, $kondisi_mobil, $denda, $id_kembali);

        if ($stmt->execute()) {
            $success_message = "Data pengembalian berhasil diupdate!";
        } else {
            $error_message = "Gagal mengupdate data pengembalian.";
        }
    } else {
        // Insert - cek dulu apakah sudah ada record
        $check_sql = "SELECT id_kembali FROM tbl_kembali WHERE id_transaksi = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_transaksi);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Transaksi ini sudah memiliki data pengembalian.";
        } else {
            $sql = "INSERT INTO tbl_kembali (id_transaksi, tgl_kembali, kondisi_mobil, denda) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issd", $id_transaksi, $tgl_kembali_aktual, $kondisi_mobil, $denda);

            if ($stmt->execute()) {
                // Update status transaksi menjadi kembali
                $update_trans = "UPDATE tbl_transaksi SET status = 'kembali' WHERE id_transaksi = ?";
                $update_stmt = $conn->prepare($update_trans);
                $update_stmt->bind_param("i", $id_transaksi);
                $update_stmt->execute();

                // Update status mobil menjadi tersedia
                $get_mobil = "SELECT nopol FROM tbl_transaksi WHERE id_transaksi = ?";
                $mobil_stmt = $conn->prepare($get_mobil);
                $mobil_stmt->bind_param("i", $id_transaksi);
                $mobil_stmt->execute();
                $mobil_result = $mobil_stmt->get_result()->fetch_assoc();

                if ($mobil_result) {
                    $update_mobil = "UPDATE tbl_mobil SET status = 'tersedia' WHERE nopol = ?";
                    $mobil_update_stmt = $conn->prepare($update_mobil);
                    $mobil_update_stmt->bind_param("s", $mobil_result['nopol']);
                    $mobil_update_stmt->execute();
                }

                $success_message = "Pengembalian berhasil dicatat!";
            } else {
                $error_message = "Gagal mencatat pengembalian.";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Get transaksi id sebelum hapus
    $get_trans = "SELECT id_transaksi FROM tbl_kembali WHERE id_kembali = ?";
    $trans_stmt = $conn->prepare($get_trans);
    $trans_stmt->bind_param("i", $delete_id);
    $trans_stmt->execute();
    $trans_result = $trans_stmt->get_result()->fetch_assoc();

    $sql = "DELETE FROM tbl_kembali WHERE id_kembali = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        // Update status transaksi kembali ke ambil
        if ($trans_result) {
            $update_trans = "UPDATE tbl_transaksi SET status = 'ambil' WHERE id_transaksi = ?";
            $update_stmt = $conn->prepare($update_trans);
            $update_stmt->bind_param("i", $trans_result['id_transaksi']);
            $update_stmt->execute();

            // Update status mobil menjadi disewa
            $get_mobil = "SELECT nopol FROM tbl_transaksi WHERE id_transaksi = ?";
            $mobil_stmt = $conn->prepare($get_mobil);
            $mobil_stmt->bind_param("i", $trans_result['id_transaksi']);
            $mobil_stmt->execute();
            $mobil_result = $mobil_stmt->get_result()->fetch_assoc();

            if ($mobil_result) {
                $update_mobil = "UPDATE tbl_mobil SET status = 'tidak' WHERE nopol = ?";
                $mobil_update_stmt = $conn->prepare($update_mobil);
                $mobil_update_stmt->bind_param("s", $mobil_result['nopol']);
                $mobil_update_stmt->execute();
            }
        }
        $success_message = "Data pengembalian berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus data pengembalian.";
    }
}

// Pagination
$limit = 12;
$current_page = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$offset = ($current_page - 1) * $limit;

// Query untuk menghitung total data
$count_sql = "SELECT COUNT(*) as total FROM tbl_kembali";
$count_result = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $limit);

// Query untuk mengambil data pengembalian dengan join
$sql = "SELECT k.*, t.tgl_ambil, t.tgl_kembali as tgl_kembali_seharusnya, t.supir, t.total,
        m.brand, m.type, m.nopol as mobil_nopol, m.harga, m.foto,
        mb.nama as nama_member, mb.telp, mb.alamat
        FROM tbl_kembali k
        JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi
        JOIN tbl_mobil m ON t.nopol = m.nopol
        JOIN tbl_member mb ON t.nik = mb.nik
        ORDER BY k.id_kembali DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Dropdown - Transaksi yang belum dikembalikan (status = ambil)
$transaksi_sql = "SELECT t.*, m.brand, m.type, m.nopol as mobil_nopol, mb.nama as nama_member
                  FROM tbl_transaksi t
                  JOIN tbl_mobil m ON t.nopol = m.nopol
                  JOIN tbl_member mb ON t.nik = mb.nik
                  WHERE t.status = 'ambil'
                  ORDER BY t.id_transaksi DESC";
$transaksi_result = mysqli_query($conn, $transaksi_sql);

// Statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN denda > 0 THEN 1 ELSE 0 END) as dengan_denda,
    SUM(CASE WHEN denda = 0 OR denda IS NULL THEN 1 ELSE 0 END) as tanpa_denda,
    COALESCE(SUM(denda), 0) as total_denda
    FROM tbl_kembali";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));

// Prepare transaksi data for JavaScript
$transaksi_data = [];
if ($transaksi_result && mysqli_num_rows($transaksi_result) > 0) {
    mysqli_data_seek($transaksi_result, 0);
    while ($t = mysqli_fetch_assoc($transaksi_result)) {
        $transaksi_data[$t['id_transaksi']] = [
            'tgl_kembali' => $t['tgl_kembali'],
            'total' => $t['total']
        ];
    }
    mysqli_data_seek($transaksi_result, 0);
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Pengembalian Mobil</h1>
        <p class="page-subtitle">Kelola data pengembalian mobil rental</p>
    </div>
    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalKembali">
        <i class="bi bi-plus-circle me-2"></i>Catat Pengembalian
    </button>
</div>

<!-- Alert Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="bi bi-arrow-return-left"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['total'] ?? 0; ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-kembali">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['tanpa_denda'] ?? 0; ?></span>
                <span class="stat-label">Tanpa Denda</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-booking">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['dengan_denda'] ?? 0; ?></span>
                <span class="stat-label">Dengan Denda</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-approve">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-info">
                <span class="stat-value">Rp <?php echo number_format(($stats['total_denda'] ?? 0) / 1000, 0); ?>K</span>
                <span class="stat-label">Total Denda</span>
            </div>
        </div>
    </div>
</div>

<!-- List View with Thumbnail -->
<div class="kembali-list-page">
    <div class="kembali-list">
        <?php
        while ($row = $result->fetch_assoc()):
            $gambar = $row['foto'] ? 'uploads/mobil/' . $row['foto'] : 'assets/img/car-placeholder.jpg';

            // Calculate late days
            $late_days = 0;
            if ($row['tgl_kembali_seharusnya'] && $row['tgl_kembali']) {
                $tgl_seharusnya = new DateTime($row['tgl_kembali_seharusnya']);
                $tgl_aktual = new DateTime($row['tgl_kembali']);
                if ($tgl_aktual > $tgl_seharusnya) {
                    $diff = $tgl_aktual->diff($tgl_seharusnya);
                    $late_days = $diff->days;
                }
            }
        ?>
            <div class="kembali-list-item <?php echo ($row['denda'] > 0) ? 'has-denda' : 'no-denda'; ?>">
                <!-- Thumbnail -->
                <div class="kembali-list-thumbnail">
                    <img src="<?php echo htmlspecialchars($gambar); ?>" alt="<?php echo htmlspecialchars($row['brand']); ?>">
                    <?php if ($row['denda'] > 0): ?>
                        <div class="list-denda-badge"><i class="bi bi-exclamation-triangle"></i></div>
                    <?php else: ?>
                        <div class="list-ok-badge"><i class="bi bi-check-circle"></i></div>
                    <?php endif; ?>
                </div>

                <!-- Main Content -->
                <div class="kembali-list-content">
                    <!-- Header Row -->
                    <div class="kembali-list-header">
                        <div class="kembali-list-car">
                            <h3><?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?></h3>
                            <span class="list-nopol"><?php echo htmlspecialchars($row['mobil_nopol']); ?></span>
                        </div>
                        <div class="kembali-list-member">
                            <div class="list-member-avatar"><?php echo strtoupper(substr($row['nama_member'], 0, 1)); ?></div>
                            <div class="list-member-info">
                                <span class="list-member-name"><?php echo htmlspecialchars($row['nama_member']); ?></span>
                                <span class="list-member-phone"><i class="bi bi-phone"></i> <?php echo htmlspecialchars($row['telp']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Dates Row -->
                    <div class="kembali-list-dates">
                        <div class="list-date-box">
                            <i class="bi bi-calendar-event"></i>
                            <div class="list-date-info">
                                <span class="list-date-label">Batas Kembali</span>
                                <span class="list-date-value"><?php echo $row['tgl_kembali_seharusnya'] ? date('d M Y', strtotime($row['tgl_kembali_seharusnya'])) : '-'; ?></span>
                            </div>
                        </div>
                        <div class="list-date-arrow"><i class="bi bi-arrow-right"></i></div>
                        <div class="list-date-box <?php echo $late_days > 0 ? 'late' : 'ontime'; ?>">
                            <i class="bi bi-calendar-check"></i>
                            <div class="list-date-info">
                                <span class="list-date-label">Dikembalikan</span>
                                <span class="list-date-value">
                                    <?php echo $row['tgl_kembali'] ? date('d M Y', strtotime($row['tgl_kembali'])) : '-'; ?>
                                    <?php if ($late_days > 0): ?>
                                        <small class="text-danger">(+<?php echo $late_days; ?> hari)</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="list-kondisi-box">
                            <i class="bi bi-car-front"></i>
                            <div class="list-kondisi-info">
                                <span class="list-kondisi-label">Kondisi</span>
                                <span class="list-kondisi-value"><?php echo htmlspecialchars($row['kondisi_mobil']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Row -->
                    <div class="kembali-list-totals">
                        <div class="list-total-item">
                            <span class="list-total-label">Biaya Sewa</span>
                            <span class="list-total-value">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="list-total-separator">+</div>
                        <div class="list-total-item <?php echo $row['denda'] > 0 ? 'has-denda' : ''; ?>">
                            <span class="list-total-label">Denda</span>
                            <span class="list-total-value <?php echo $row['denda'] > 0 ? 'text-danger' : ''; ?>">
                                Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="list-total-separator">=</div>
                        <div class="list-total-item total-final">
                            <span class="list-total-label">Total</span>
                            <span class="list-total-value">Rp <?php echo number_format($row['total'] + $row['denda'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="kembali-list-actions">
                    <a href="index.php?page=bayar&kembali_id=<?php echo $row['id_kembali']; ?>" class="list-btn list-btn-primary">
                        <i class="bi bi-credit-card"></i>
                        <span>Proses Bayar</span>
                    </a>
                    <button type="button" class="list-btn list-btn-secondary btn-edit"
                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                        data-id="<?php echo $row['id_kembali']; ?>"
                        data-transaksi="<?php echo $row['id_transaksi']; ?>"
                        data-tgl-kembali="<?php echo $row['tgl_kembali']; ?>"
                        data-kondisi="<?php echo htmlspecialchars($row['kondisi_mobil']); ?>"
                        data-denda="<?php echo $row['denda']; ?>">
                        <i class="bi bi-pencil"></i>
                        <span>Edit</span>
                    </button>
                    <a href="index.php?page=kembali&delete=<?php echo $row['id_kembali']; ?>"
                        class="list-btn list-btn-danger"
                        data-confirm="Yakin ingin menghapus data pengembalian untuk <?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?> - <?php echo htmlspecialchars($row['nama_member']); ?>?"
                        data-title="Hapus Pengembalian?">
                        <i class="bi bi-trash"></i>
                        <span>Hapus</span>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>Belum Ada Pengembalian</h3>
            <p>Data pengembalian akan muncul di sini setelah mobil dikembalikan.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=kembali&pg=<?php echo $current_page - 1; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                    <a class="page-link" href="index.php?page=kembali&pg=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=kembali&pg=<?php echo $current_page + 1; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal Catat Pengembalian -->
<div class="modal fade" id="modalKembali" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Catat Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=kembali">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Transaksi</label>
                        <select name="id_transaksi" id="id_transaksi" class="form-select" required onchange="hitungDenda()">
                            <option value="">Pilih Transaksi</option>
                            <?php
                            if ($transaksi_result && mysqli_num_rows($transaksi_result) > 0) {
                                mysqli_data_seek($transaksi_result, 0);
                                while ($t = mysqli_fetch_assoc($transaksi_result)):
                            ?>
                                    <option value="<?php echo $t['id_transaksi']; ?>"
                                        data-tgl-kembali="<?php echo $t['tgl_kembali']; ?>"
                                        <?php echo ($prefill_transaksi && $prefill_transaksi['id_transaksi'] == $t['id_transaksi']) ? 'selected' : ''; ?>>
                                        #<?php echo $t['id_transaksi']; ?> - <?php echo htmlspecialchars($t['nama_member']); ?> | <?php echo htmlspecialchars($t['brand'] . ' ' . $t['type']); ?> (<?php echo htmlspecialchars($t['mobil_nopol']); ?>)
                                    </option>
                            <?php
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Pengembalian</label>
                        <input type="date" name="tgl_kembali" id="tgl_kembali" class="form-control"
                            required value="<?php echo date('Y-m-d'); ?>" onchange="hitungDenda()">
                        <div id="info_keterlambatan" class="mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kondisi Mobil</label>
                        <textarea name="kondisi_mobil" class="form-control" rows="3" required
                            placeholder="Jelaskan kondisi mobil saat dikembalikan..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Denda (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="denda" id="denda" class="form-control" min="0" value="0" step="0.01">
                        </div>
                        <div id="info_denda" class="mt-1"></div>
                        <small class="text-muted">Denda keterlambatan: Rp 100.000/hari (otomatis). Anda dapat menambah denda jika mobil rusak, tetapi tidak bisa mengurangi denda keterlambatan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pengembalian -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=kembali">
                <div class="modal-body">
                    <input type="hidden" name="id_kembali" id="edit_id_kembali">
                    <input type="hidden" name="id_transaksi" id="edit_id_transaksi">

                    <div class="mb-3">
                        <label class="form-label">Tanggal Pengembalian</label>
                        <input type="date" name="tgl_kembali" id="edit_tgl_kembali" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kondisi Mobil</label>
                        <textarea name="kondisi_mobil" id="edit_kondisi" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Denda (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="denda" id="edit_denda" class="form-control" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Data transaksi untuk JavaScript
    const transaksiData = <?php echo json_encode($transaksi_data); ?>;
    const dendaPerHari = 100000;

    // Store minimum denda otomatis
    let dendaOtomatis = 0;

    function hitungDenda() {
        const selectTransaksi = document.getElementById('id_transaksi');
        const tglKembaliInput = document.getElementById('tgl_kembali');
        const dendaInput = document.getElementById('denda');
        const infoKeterlambatan = document.getElementById('info_keterlambatan');
        const infoDenda = document.getElementById('info_denda');

        if (!selectTransaksi || !tglKembaliInput || !dendaInput) return;

        const idTransaksi = selectTransaksi.value;
        const tglKembaliAktual = tglKembaliInput.value;

        if (!idTransaksi || !tglKembaliAktual || !transaksiData[idTransaksi]) {
            dendaInput.value = 0;
            dendaOtomatis = 0;
            dendaInput.min = 0;
            if (infoKeterlambatan) infoKeterlambatan.innerHTML = '';
            if (infoDenda) infoDenda.innerHTML = '';
            return;
        }

        const tglSeharusnya = transaksiData[idTransaksi].tgl_kembali;
        const dateSeharusnya = new Date(tglSeharusnya);
        const dateAktual = new Date(tglKembaliAktual);

        const diffTime = dateAktual - dateSeharusnya;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const dendaKeterlambatan = diffDays * dendaPerHari;
            dendaInput.value = dendaKeterlambatan;
            dendaOtomatis = dendaKeterlambatan;
            dendaInput.min = dendaKeterlambatan;
            if (infoKeterlambatan) {
                infoKeterlambatan.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Terlambat ' + diffDays + ' hari</span>';
            }
            if (infoDenda) {
                infoDenda.innerHTML = '<span class="text-warning"><i class="bi bi-info-circle me-1"></i>Denda otomatis: Rp ' + dendaKeterlambatan.toLocaleString('id-ID') + ' (Minimum, tidak dapat dikurangi)</span>';
            }
        } else {
            dendaInput.value = 0;
            dendaOtomatis = 0;
            dendaInput.min = 0;
            if (infoKeterlambatan) {
                infoKeterlambatan.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Tepat waktu</span>';
            }
            if (infoDenda) {
                infoDenda.innerHTML = '';
            }
        }
    }

    // Validasi denda tidak boleh kurang dari denda otomatis
    function validasiDenda() {
        const dendaInput = document.getElementById('denda');
        const infoDenda = document.getElementById('info_denda');

        if (!dendaInput) return;

        const dendaValue = parseFloat(dendaInput.value) || 0;

        if (dendaOtomatis > 0 && dendaValue < dendaOtomatis) {
            // Tampilkan notifikasi error
            if (infoDenda) {
                infoDenda.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i><strong>Denda tidak valid!</strong> Minimum denda adalah Rp ' + dendaOtomatis.toLocaleString('id-ID') + ' (denda keterlambatan)</span>';
            }
            // Reset ke nilai minimum
            dendaInput.value = dendaOtomatis;

            // Shake effect untuk visual feedback
            dendaInput.classList.add('is-invalid');
            dendaInput.style.animation = 'shake 0.5s';
            setTimeout(() => {
                dendaInput.classList.remove('is-invalid');
                dendaInput.style.animation = '';
                // Reset info setelah beberapa saat
                if (infoDenda) {
                    infoDenda.innerHTML = '<span class="text-warning"><i class="bi bi-info-circle me-1"></i>Denda otomatis: Rp ' + dendaOtomatis.toLocaleString('id-ID') + ' (Minimum, tidak dapat dikurangi)</span>';
                }
            }, 2000);
        }
    }

    // Bind event listener untuk input denda
    document.addEventListener('DOMContentLoaded', function() {
        const dendaInput = document.getElementById('denda');
        if (dendaInput) {
            dendaInput.addEventListener('change', validasiDenda);
            dendaInput.addEventListener('blur', validasiDenda);
            dendaInput.addEventListener('input', function() {
                const infoDenda = document.getElementById('info_denda');
                const dendaValue = parseFloat(this.value) || 0;

                if (dendaOtomatis > 0 && dendaValue < dendaOtomatis) {
                    if (infoDenda) {
                        infoDenda.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Denda tidak boleh kurang dari Rp ' + dendaOtomatis.toLocaleString('id-ID') + '</span>';
                    }
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    if (dendaOtomatis > 0 && infoDenda) {
                        infoDenda.innerHTML = '<span class="text-warning"><i class="bi bi-info-circle me-1"></i>Denda otomatis: Rp ' + dendaOtomatis.toLocaleString('id-ID') + ' (Minimum, tidak dapat dikurangi)</span>';
                    }
                }
            });
        }

        // Validasi form sebelum submit
        const formKembali = document.querySelector('#modalKembali form');
        if (formKembali) {
            formKembali.addEventListener('submit', function(e) {
                const dendaValue = parseFloat(document.getElementById('denda').value) || 0;

                if (dendaOtomatis > 0 && dendaValue < dendaOtomatis) {
                    e.preventDefault();

                    // Tampilkan alert error
                    alert('Error: Denda tidak boleh kurang dari denda keterlambatan!\n\nMinimum denda: Rp ' + dendaOtomatis.toLocaleString('id-ID'));

                    // Reset nilai dan fokus ke input
                    document.getElementById('denda').value = dendaOtomatis;
                    document.getElementById('denda').focus();

                    return false;
                }
            });
        }

        // Add CSS for shake animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    });

    // Edit button handler
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id_kembali').value = this.dataset.id;
            document.getElementById('edit_id_transaksi').value = this.dataset.transaksi;
            document.getElementById('edit_tgl_kembali').value = this.dataset.tglKembali;
            document.getElementById('edit_kondisi').value = this.dataset.kondisi;
            document.getElementById('edit_denda').value = this.dataset.denda;
        });
    });

    // Auto show modal if prefill
    <?php if ($prefill_transaksi): ?>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('modalKembali')).show();
            setTimeout(hitungDenda, 100);
        });
    <?php endif; ?>
</script>