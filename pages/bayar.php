<?php
// Bayar (Pembayaran) Page - Grid View untuk Admin/Petugas
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

// Handle parameter dari kembali page
$prefill_kembali = null;
if (isset($_GET['kembali_id'])) {
    $kembali_id = (int)$_GET['kembali_id'];
    $prefill_sql = "SELECT k.*, t.total, t.downpayment, t.kekurangan, t.nopol, t.nik,
                    m.brand, m.type, m.foto,
                    mb.nama as nama_member
                    FROM tbl_kembali k
                    JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi
                    JOIN tbl_mobil m ON t.nopol = m.nopol
                    JOIN tbl_member mb ON t.nik = mb.nik
                    WHERE k.id_kembali = ?";
    $prefill_stmt = $conn->prepare($prefill_sql);
    $prefill_stmt->bind_param("i", $kembali_id);
    $prefill_stmt->execute();
    $prefill_result = $prefill_stmt->get_result();
    if ($prefill_result->num_rows > 0) {
        $prefill_kembali = $prefill_result->fetch_assoc();
    }
}

// Handle POST (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_bayar = isset($_POST['id_bayar']) ? (int)$_POST['id_bayar'] : 0;
    $id_kembali = (int)$_POST['id_kembali'];
    $tgl_bayar = $_POST['tgl_bayar'];
    $total_bayar = (float)$_POST['total_bayar'];
    $status_bayar = mysqli_real_escape_string($conn, $_POST['status']);

    if ($id_bayar > 0) {
        // Update
        $sql = "UPDATE tbl_bayar SET tgl_bayar = ?, total_bayar = ?, status = ? WHERE id_bayar = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdsi", $tgl_bayar, $total_bayar, $status_bayar, $id_bayar);

        if ($stmt->execute()) {
            $success_message = "Data pembayaran berhasil diupdate!";
        } else {
            $error_message = "Gagal mengupdate data pembayaran.";
        }
    } else {
        // Insert - cek dulu apakah sudah ada record
        $check_sql = "SELECT id_bayar FROM tbl_bayar WHERE id_kembali = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_kembali);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Pengembalian ini sudah memiliki data pembayaran.";
        } else {
            $sql = "INSERT INTO tbl_bayar (id_kembali, tgl_bayar, total_bayar, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isds", $id_kembali, $tgl_bayar, $total_bayar, $status_bayar);

            if ($stmt->execute()) {
                $success_message = "Pembayaran berhasil dicatat!";
            } else {
                $error_message = "Gagal mencatat pembayaran.";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    $sql = "DELETE FROM tbl_bayar WHERE id_bayar = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $success_message = "Data pembayaran berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus data pembayaran.";
    }
}

// Pagination
$limit = 12;
$current_page = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$offset = ($current_page - 1) * $limit;

// Query untuk menghitung total data
$count_sql = "SELECT COUNT(*) as total FROM tbl_bayar";
$count_result = mysqli_query($conn, $count_sql);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $limit);

// Query untuk mengambil data pembayaran dengan join
$sql = "SELECT b.*, k.tgl_kembali, k.kondisi_mobil, k.denda, k.id_transaksi,
        t.tgl_ambil, t.total as biaya_sewa, t.downpayment, t.kekurangan, t.supir,
        m.brand, m.type, m.nopol as mobil_nopol, m.harga, m.foto,
        mb.nama as nama_member, mb.telp, mb.alamat
        FROM tbl_bayar b
        JOIN tbl_kembali k ON b.id_kembali = k.id_kembali
        JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi
        JOIN tbl_mobil m ON t.nopol = m.nopol
        JOIN tbl_member mb ON t.nik = mb.nik
        ORDER BY b.id_bayar DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Dropdown - Kembali yang belum bayar (not in tbl_bayar)
$kembali_sql = "SELECT k.*, t.total as biaya_sewa, t.nopol, t.nik,
                m.brand, m.type, m.foto,
                mb.nama as nama_member
                FROM tbl_kembali k
                JOIN tbl_transaksi t ON k.id_transaksi = t.id_transaksi
                JOIN tbl_mobil m ON t.nopol = m.nopol
                JOIN tbl_member mb ON t.nik = mb.nik
                WHERE k.id_kembali NOT IN (SELECT id_kembali FROM tbl_bayar)
                ORDER BY k.id_kembali DESC";
$kembali_result = mysqli_query($conn, $kembali_sql);

// Statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) as lunas,
    SUM(CASE WHEN status = 'belum lunas' THEN 1 ELSE 0 END) as belum_lunas,
    COALESCE(SUM(total_bayar), 0) as total_pendapatan
    FROM tbl_bayar";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));

// Prepare kembali data for JavaScript
$kembali_data = [];
if ($kembali_result && mysqli_num_rows($kembali_result) > 0) {
    mysqli_data_seek($kembali_result, 0);
    while ($k = mysqli_fetch_assoc($kembali_result)) {
        $kembali_data[$k['id_kembali']] = [
            'biaya_sewa' => $k['biaya_sewa'],
            'denda' => $k['denda'],
            'total' => $k['biaya_sewa'] + $k['denda']
        ];
    }
    mysqli_data_seek($kembali_result, 0);
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Pembayaran</h1>
        <p class="page-subtitle">Kelola data pembayaran rental mobil</p>
    </div>
    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalBayar">
        <i class="bi bi-plus-circle me-2"></i>Catat Pembayaran
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
            <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
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
                <span class="stat-value"><?php echo $stats['lunas'] ?? 0; ?></span>
                <span class="stat-label">Lunas</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-booking">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['belum_lunas'] ?? 0; ?></span>
                <span class="stat-label">Belum Lunas</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-approve">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-info">
                <span class="stat-value">Rp <?php echo number_format(($stats['total_pendapatan'] ?? 0) / 1000000, 1); ?>M</span>
                <span class="stat-label">Pendapatan</span>
            </div>
        </div>
    </div>
</div>

<!-- Card List Vertical View -->
<div class="bayar-cardlist-page">
    <div class="bayar-cardlist">
        <?php
        while ($row = $result->fetch_assoc()):
            $gambar = $row['foto'] ? 'uploads/mobil/' . $row['foto'] : 'assets/img/car-placeholder.jpg';
            $total_tagihan = $row['biaya_sewa'] + $row['denda'];
            $sisa = $total_tagihan - $row['total_bayar'];
            $is_lunas = $row['status'] === 'lunas';
        ?>
            <div class="bayar-cardlist-item <?php echo $is_lunas ? 'status-lunas' : 'status-belum'; ?>">
                <!-- Left: Thumbnail -->
                <div class="bayar-cardlist-thumbnail">
                    <img src="<?php echo htmlspecialchars($gambar); ?>" alt="<?php echo htmlspecialchars($row['brand']); ?>">
                    <div class="cardlist-status-badge <?php echo $is_lunas ? 'badge-lunas' : 'badge-belum'; ?>">
                        <?php if ($is_lunas): ?>
                            <i class="bi bi-check-circle"></i> LUNAS
                        <?php else: ?>
                            <i class="bi bi-hourglass-split"></i> BELUM
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Center: Content -->
                <div class="bayar-cardlist-content">
                    <!-- Header -->
                    <div class="cardlist-header">
                        <div class="cardlist-car-info">
                            <h3><?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?></h3>
                            <span class="cardlist-nopol"><?php echo htmlspecialchars($row['mobil_nopol']); ?></span>
                        </div>
                        <div class="cardlist-member-info">
                            <div class="cardlist-member-avatar"><?php echo strtoupper(substr($row['nama_member'], 0, 1)); ?></div>
                            <div class="cardlist-member-details">
                                <span class="cardlist-member-name"><?php echo htmlspecialchars($row['nama_member']); ?></span>
                                <span class="cardlist-member-phone"><i class="bi bi-phone"></i> <?php echo htmlspecialchars($row['telp']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Date -->
                    <div class="cardlist-payment-date">
                        <i class="bi bi-calendar-check"></i>
                        <span>Dibayar: <strong><?php echo $row['tgl_bayar'] ? date('d M Y', strtotime($row['tgl_bayar'])) : '-'; ?></strong></span>
                    </div>

                    <!-- Payment Breakdown -->
                    <div class="cardlist-breakdown">
                        <div class="cardlist-breakdown-header">
                            <i class="bi bi-receipt"></i>
                            <span>Rincian Pembayaran</span>
                        </div>
                        <div class="cardlist-breakdown-body">
                            <div class="breakdown-row">
                                <span class="breakdown-label">Biaya Sewa</span>
                                <span class="breakdown-value">Rp <?php echo number_format($row['biaya_sewa'], 0, ',', '.'); ?></span>
                            </div>
                            <?php if ($row['denda'] > 0): ?>
                                <div class="breakdown-row denda">
                                    <span class="breakdown-label">Denda</span>
                                    <span class="breakdown-value text-danger">Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="breakdown-row total-tagihan">
                                <span class="breakdown-label">Total Tagihan</span>
                                <span class="breakdown-value">Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?></span>
                            </div>
                            <div class="breakdown-row dibayar">
                                <span class="breakdown-label">Dibayar</span>
                                <span class="breakdown-value text-success">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></span>
                            </div>
                            <?php if ($sisa > 0): ?>
                                <div class="breakdown-row sisa">
                                    <span class="breakdown-label">Sisa</span>
                                    <span class="breakdown-value text-warning">Rp <?php echo number_format($sisa, 0, ',', '.'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="bayar-cardlist-actions">
                    <div class="cardlist-final-status <?php echo $is_lunas ? 'final-lunas' : 'final-belum'; ?>">
                        <span class="final-label">Status</span>
                        <span class="final-value"><?php echo ucfirst($row['status']); ?></span>
                    </div>
                    <div class="cardlist-action-buttons">
                        <button type="button" class="cardlist-btn cardlist-btn-edit btn-edit"
                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                            data-id="<?php echo $row['id_bayar']; ?>"
                            data-kembali="<?php echo $row['id_kembali']; ?>"
                            data-tgl-bayar="<?php echo $row['tgl_bayar']; ?>"
                            data-total-bayar="<?php echo $row['total_bayar']; ?>"
                            data-status="<?php echo $row['status']; ?>">
                            <i class="bi bi-pencil"></i>
                            <span>Edit</span>
                        </button>
                        <a href="index.php?page=bayar&delete=<?php echo $row['id_bayar']; ?>"
                            class="cardlist-btn cardlist-btn-delete"
                            data-confirm="Yakin ingin menghapus data pembayaran untuk <?php echo htmlspecialchars($row['brand'] . ' ' . $row['type']); ?> - <?php echo htmlspecialchars($row['nama_member']); ?>?"
                            data-title="Hapus Pembayaran?">
                            <i class="bi bi-trash"></i>
                            <span>Hapus</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>Belum Ada Pembayaran</h3>
            <p>Data pembayaran akan muncul di sini setelah pembayaran dicatat.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=bayar&pg=<?php echo $current_page - 1; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                    <a class="page-link" href="index.php?page=bayar&pg=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=bayar&pg=<?php echo $current_page + 1; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal Catat Pembayaran -->
<div class="modal fade" id="modalBayar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Catat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=bayar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Data Pengembalian</label>
                        <select name="id_kembali" id="id_kembali" class="form-select" required onchange="setTotalBayar()">
                            <option value="">Pilih Pengembalian</option>
                            <?php
                            if ($kembali_result && mysqli_num_rows($kembali_result) > 0) {
                                mysqli_data_seek($kembali_result, 0);
                                while ($k = mysqli_fetch_assoc($kembali_result)):
                                    $total_k = $k['biaya_sewa'] + $k['denda'];
                            ?>
                                    <option value="<?php echo $k['id_kembali']; ?>"
                                        data-total="<?php echo $total_k; ?>"
                                        data-sewa="<?php echo $k['biaya_sewa']; ?>"
                                        data-denda="<?php echo $k['denda']; ?>"
                                        <?php echo ($prefill_kembali && $prefill_kembali['id_kembali'] == $k['id_kembali']) ? 'selected' : ''; ?>>
                                        #<?php echo $k['id_kembali']; ?> - <?php echo htmlspecialchars($k['nama_member']); ?> | <?php echo htmlspecialchars($k['brand'] . ' ' . $k['type']); ?> (Rp <?php echo number_format($total_k, 0, ',', '.'); ?>)
                                    </option>
                            <?php
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3" id="info_tagihan" style="display: none;">
                        <div class="card border-0 shadow-sm" style="background: var(--bg-secondary); border-radius: 12px;">
                            <div class="card-body p-3">
                                <h6 class="mb-3 d-flex align-items-center gap-2" style="color: var(--text-primary);">
                                    <i class="bi bi-receipt text-primary"></i> Rincian Biaya
                                </h6>
                                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid var(--border-color);">
                                    <span style="color: var(--text-muted);">Biaya Sewa</span>
                                    <span id="display_sewa" style="font-weight: 600; color: var(--text-primary);">-</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid var(--border-color);">
                                    <span style="color: var(--text-muted);">Denda</span>
                                    <span id="display_denda" class="text-danger" style="font-weight: 600;">-</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 mt-2" style="background: var(--bg-highlight); margin: 8px -12px -12px; padding: 12px 16px; border-radius: 0 0 12px 12px;">
                                    <span style="font-weight: 700; color: var(--text-primary);">Total Tagihan</span>
                                    <span id="display_total" style="font-weight: 700; font-size: 1.1rem; color: var(--primary-color);">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Pembayaran</label>
                        <input type="date" name="tgl_bayar" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Bayar (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="total_bayar" id="total_bayar" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status Pembayaran</label>
                        <select name="status" class="form-select" required>
                            <option value="lunas">Lunas</option>
                            <option value="belum lunas">Belum Lunas</option>
                        </select>
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

<!-- Modal Edit Pembayaran -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=bayar">
                <div class="modal-body">
                    <input type="hidden" name="id_bayar" id="edit_id_bayar">
                    <input type="hidden" name="id_kembali" id="edit_id_kembali">

                    <div class="mb-3">
                        <label class="form-label">Tanggal Pembayaran</label>
                        <input type="date" name="tgl_bayar" id="edit_tgl_bayar" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Bayar (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="total_bayar" id="edit_total_bayar" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status Pembayaran</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="lunas">Lunas</option>
                            <option value="belum lunas">Belum Lunas</option>
                        </select>
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
    // Data kembali untuk JavaScript
    const kembaliData = <?php echo json_encode($kembali_data); ?>;

    function setTotalBayar() {
        const selectKembali = document.getElementById('id_kembali');
        const totalBayarInput = document.getElementById('total_bayar');
        const infoTagihan = document.getElementById('info_tagihan');
        const displaySewa = document.getElementById('display_sewa');
        const displayDenda = document.getElementById('display_denda');
        const displayTotal = document.getElementById('display_total');

        if (!selectKembali || !totalBayarInput) return;

        const selectedOption = selectKembali.options[selectKembali.selectedIndex];

        if (selectedOption && selectedOption.value && selectedOption.dataset) {
            const sewa = parseFloat(selectedOption.dataset.sewa) || 0;
            const denda = parseFloat(selectedOption.dataset.denda) || 0;
            const total = parseFloat(selectedOption.dataset.total) || 0;

            totalBayarInput.value = total;

            if (infoTagihan) {
                infoTagihan.style.display = 'block';
                displaySewa.textContent = 'Rp ' + sewa.toLocaleString('id-ID');
                displayDenda.textContent = 'Rp ' + denda.toLocaleString('id-ID');
                displayTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
            }
        } else {
            totalBayarInput.value = '';
            if (infoTagihan) infoTagihan.style.display = 'none';
        }
    }

    // Edit button handler
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id_bayar').value = this.dataset.id;
            document.getElementById('edit_id_kembali').value = this.dataset.kembali;
            document.getElementById('edit_tgl_bayar').value = this.dataset.tglBayar;
            document.getElementById('edit_total_bayar').value = this.dataset.totalBayar;
            document.getElementById('edit_status').value = this.dataset.status;
        });
    });

    // Auto show modal if prefill
    <?php if ($prefill_kembali): ?>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('modalBayar')).show();
            setTimeout(setTotalBayar, 100);
        });
    <?php endif; ?>
</script>