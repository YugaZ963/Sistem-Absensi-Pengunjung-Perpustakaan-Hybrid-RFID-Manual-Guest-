<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
auth_require_login();

$todaySummary = admin_get_today_visit_summary($db);
$todayLabel = date('d M Y', strtotime((string) $todaySummary['date']));

$pageTitle = 'Admin - Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/_layout_top.php';
?>
<section class="card">
    <h1>Dashboard Admin</h1>
    <p class="small">Menampilkan jumlah pengunjung yang hadir hari ini secara realtime.</p>

    <article class="dashboard-kpi">
        <p class="dashboard-kpi-label">Jumlah Pengunjung Hari Ini</p>
        <p class="dashboard-kpi-date" id="dashboard-date"><?= h($todayLabel) ?></p>
        <p class="dashboard-kpi-value" id="dashboard-total"><?= number_format((int) $todaySummary['total'], 0, ',', '.') ?></p>
        <p class="small" id="dashboard-breakdown">
            Mahasiswa: <?= number_format((int) $todaySummary['total_mahasiswa'], 0, ',', '.') ?>
            | Guest: <?= number_format((int) $todaySummary['total_guest'], 0, ',', '.') ?>
        </p>
        <p class="dashboard-realtime-note" id="dashboard-realtime-note">Realtime aktif - update otomatis setiap 10 detik.</p>
    </article>

    <div class="form-actions">
        <a class="btn" href="<?= h(app_url('admin/rekap.php')) ?>">Buka Rekap Absensi</a>
        <a class="btn btn-light" href="<?= h(app_url('/')) ?>">Ke Front Desk</a>
    </div>
</section>

<script>
(function () {
    var totalEl = document.getElementById('dashboard-total');
    var breakdownEl = document.getElementById('dashboard-breakdown');
    var dateEl = document.getElementById('dashboard-date');
    var noteEl = document.getElementById('dashboard-realtime-note');
    var endpoint = '<?= h(app_url('admin/dashboard_stats.php')) ?>';
    var refreshMs = 10000;

    if (!totalEl || !breakdownEl || !dateEl) {
        return;
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
    }

    function formatDate(isoDate) {
        if (!isoDate) {
            return '-';
        }
        var parts = String(isoDate).split('-');
        if (parts.length !== 3) {
            return isoDate;
        }
        var dateObj = new Date(parts[0], Number(parts[1]) - 1, Number(parts[2]));
        if (Number.isNaN(dateObj.getTime())) {
            return isoDate;
        }
        return dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function refreshDashboard() {
        fetch(endpoint, {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error('Invalid payload');
                }

                totalEl.textContent = formatNumber(data.total);
                dateEl.textContent = formatDate(data.date);
                breakdownEl.textContent = 'Mahasiswa: ' + formatNumber(data.total_mahasiswa) + ' | Guest: ' + formatNumber(data.total_guest);
                if (noteEl) {
                    noteEl.textContent = 'Realtime aktif - update terakhir ' + (data.server_time || '-');
                }
            })
            .catch(function () {
                if (noteEl) {
                    noteEl.textContent = 'Realtime aktif, menunggu sinkronisasi data terbaru...';
                }
            });
    }

    setInterval(refreshDashboard, refreshMs);
})();
</script>

<?php
$db->close();
require __DIR__ . '/_layout_bottom.php';
