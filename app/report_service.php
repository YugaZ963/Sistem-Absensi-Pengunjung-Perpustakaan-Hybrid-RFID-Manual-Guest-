<?php
declare(strict_types=1);

function report_normalize_filters(array $input): array
{
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');
    $allowedPeriod = ['daily', 'weekly', 'monthly', 'custom', 'semester', 'academic_year'];
    $allowedStatus = ['all', 'mahasiswa', 'guest'];

    $period = (string) ($input['period'] ?? 'daily');
    if (!in_array($period, $allowedPeriod, true)) {
        $period = 'daily';
    }

    $status = (string) ($input['status'] ?? 'all');
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'all';
    }

    $refDate = report_safe_date((string) ($input['ref_date'] ?? $today), $today);
    $month = report_safe_month((string) ($input['month'] ?? $currentMonth), $currentMonth);
    $startDate = report_safe_date((string) ($input['start_date'] ?? $today), $today);
    $endDate = report_safe_date((string) ($input['end_date'] ?? $today), $today);
    if ($startDate > $endDate) {
        $tmp = $startDate;
        $startDate = $endDate;
        $endDate = $tmp;
    }

    $semester = (int) ($input['semester'] ?? 1);
    if (!in_array($semester, [1, 2], true)) {
        $semester = 1;
    }

    $filters = [
        'period' => $period,
        'status' => $status,
        'ref_date' => $refDate,
        'month' => $month,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'semester' => $semester,
        'tahun_akademik' => trim((string) ($input['tahun_akademik'] ?? '')),
        'prodi' => trim((string) ($input['prodi'] ?? '')),
        'jenjang' => trim((string) ($input['jenjang'] ?? '')),
        'alamat' => trim((string) ($input['alamat'] ?? '')),
        'range_start' => $today,
        'range_end' => $today,
        'period_label' => '',
        'errors' => [],
    ];

    if ($period === 'daily') {
        $filters['range_start'] = $refDate;
        $filters['range_end'] = $refDate;
        $filters['period_label'] = 'Harian: ' . $refDate;
    } elseif ($period === 'weekly') {
        $ts = strtotime($refDate);
        $dayNum = (int) date('N', $ts);
        $rangeStart = date('Y-m-d', strtotime('-' . ($dayNum - 1) . ' day', $ts));
        $rangeEnd = date('Y-m-d', strtotime('+' . (7 - $dayNum) . ' day', $ts));
        $filters['range_start'] = $rangeStart;
        $filters['range_end'] = $rangeEnd;
        $filters['period_label'] = 'Mingguan: ' . $rangeStart . ' s/d ' . $rangeEnd;
    } elseif ($period === 'monthly') {
        $base = $month . '-01';
        $filters['range_start'] = date('Y-m-01', strtotime($base));
        $filters['range_end'] = date('Y-m-t', strtotime($base));
        $filters['period_label'] = 'Bulanan: ' . $month;
    } elseif ($period === 'custom') {
        $filters['range_start'] = $startDate;
        $filters['range_end'] = $endDate;
        $filters['period_label'] = 'Kustom: ' . $startDate . ' s/d ' . $endDate;
    } elseif ($period === 'semester') {
        if ($filters['tahun_akademik'] === '') {
            $filters['errors'][] = 'Tahun akademik wajib diisi untuk periode semester.';
        }
        $filters['period_label'] = 'Semester ' . $semester . ' - ' . ($filters['tahun_akademik'] !== '' ? $filters['tahun_akademik'] : '(belum diisi)');
    } elseif ($period === 'academic_year') {
        if ($filters['tahun_akademik'] === '') {
            $filters['errors'][] = 'Tahun akademik wajib diisi untuk periode tahun akademik.';
        }
        $filters['period_label'] = 'Tahun Akademik: ' . ($filters['tahun_akademik'] !== '' ? $filters['tahun_akademik'] : '(belum diisi)');
    }

    return $filters;
}

function report_fetch_rows(mysqli $db, array $filters, ?int $limit = 1500): array
{
    $params = [];
    $types = '';
    $where = report_build_where($filters, $params, $types);

    $sql = 'SELECT
                a.id,
                a.tipe,
                a.ref_id,
                a.tanggal,
                a.jam,
                a.tahun_akademik,
                a.semester,
                CASE WHEN a.tipe = "mahasiswa" THEN m.nim ELSE g.guest_id END AS identitas,
                CASE WHEN a.tipe = "mahasiswa" THEN m.nama ELSE g.nama END AS nama,
                CASE WHEN a.tipe = "mahasiswa" THEN m.prodi ELSE "-" END AS prodi,
                CASE WHEN a.tipe = "mahasiswa" THEN m.jenjang ELSE "-" END AS jenjang,
                CASE WHEN a.tipe = "mahasiswa" THEN m.alamat ELSE g.alamat END AS alamat,
                g.asal_kampus
            FROM absensi a
            LEFT JOIN mahasiswa m ON a.tipe = "mahasiswa" AND a.ref_id = m.nim
            LEFT JOIN guest g ON a.tipe = "guest" AND a.ref_id = g.guest_id
            WHERE ' . $where . '
            ORDER BY a.tanggal DESC, a.jam DESC';

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $sql .= ' LIMIT ' . $limit;
    }

    if ($types === '') {
        $result = $db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare report query.');
    }

    report_bind_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

function report_fetch_summary(mysqli $db, array $filters): array
{
    $params = [];
    $types = '';
    $where = report_build_where($filters, $params, $types);

    $sql = 'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN a.tipe = "mahasiswa" THEN 1 ELSE 0 END) AS total_mahasiswa,
                SUM(CASE WHEN a.tipe = "guest" THEN 1 ELSE 0 END) AS total_guest
            FROM absensi a
            LEFT JOIN mahasiswa m ON a.tipe = "mahasiswa" AND a.ref_id = m.nim
            LEFT JOIN guest g ON a.tipe = "guest" AND a.ref_id = g.guest_id
            WHERE ' . $where;

    if ($types === '') {
        $result = $db->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        return report_normalize_summary_row($row);
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare summary query.');
    }
    report_bind_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return report_normalize_summary_row($row);
}

function report_get_filter_options(mysqli $db): array
{
    $prodi = [];
    $prodiResult = $db->query('SELECT DISTINCT prodi FROM mahasiswa WHERE prodi IS NOT NULL AND prodi <> "" ORDER BY prodi ASC');
    if ($prodiResult) {
        while ($row = $prodiResult->fetch_assoc()) {
            $prodi[] = (string) $row['prodi'];
        }
    }

    $jenjang = [];
    $jenjangResult = $db->query('SELECT DISTINCT jenjang FROM mahasiswa WHERE jenjang IS NOT NULL AND jenjang <> "" ORDER BY jenjang ASC');
    if ($jenjangResult) {
        while ($row = $jenjangResult->fetch_assoc()) {
            $jenjang[] = (string) $row['jenjang'];
        }
    }

    $alamat = [];
    $alamatResult = $db->query(
        'SELECT DISTINCT alamat FROM (
            SELECT alamat FROM mahasiswa
            UNION
            SELECT alamat FROM guest
        ) t
        WHERE alamat IS NOT NULL AND alamat <> ""
        ORDER BY alamat ASC'
    );
    if ($alamatResult) {
        while ($row = $alamatResult->fetch_assoc()) {
            $alamat[] = (string) $row['alamat'];
        }
    }

    return [
        'prodi' => $prodi,
        'jenjang' => $jenjang,
        'alamat' => $alamat,
    ];
}

function report_export_query(array $filters): string
{
    return http_build_query([
        'period' => $filters['period'],
        'status' => $filters['status'],
        'ref_date' => $filters['ref_date'],
        'month' => $filters['month'],
        'start_date' => $filters['start_date'],
        'end_date' => $filters['end_date'],
        'semester' => $filters['semester'],
        'tahun_akademik' => $filters['tahun_akademik'],
        'prodi' => $filters['prodi'],
        'jenjang' => $filters['jenjang'],
        'alamat' => $filters['alamat'],
    ]);
}

function report_status_label(string $status): string
{
    if ($status === 'mahasiswa') {
        return 'Mahasiswa';
    }
    if ($status === 'guest') {
        return 'Guest';
    }
    return 'Semua';
}

function report_build_where(array $filters, array &$params, string &$types): string
{
    $clauses = ['1=1'];
    $period = (string) $filters['period'];

    if (in_array($period, ['daily', 'weekly', 'monthly', 'custom'], true)) {
        $clauses[] = 'a.tanggal BETWEEN ? AND ?';
        $params[] = (string) $filters['range_start'];
        $params[] = (string) $filters['range_end'];
        $types .= 'ss';
    } elseif ($period === 'semester') {
        if ((string) $filters['tahun_akademik'] !== '') {
            $clauses[] = 'a.tahun_akademik = ?';
            $params[] = (string) $filters['tahun_akademik'];
            $types .= 's';
        }
        $clauses[] = 'a.semester = ?';
        $params[] = (int) $filters['semester'];
        $types .= 'i';
    } elseif ($period === 'academic_year') {
        if ((string) $filters['tahun_akademik'] !== '') {
            $clauses[] = 'a.tahun_akademik = ?';
            $params[] = (string) $filters['tahun_akademik'];
            $types .= 's';
        }
    }

    if ((string) $filters['status'] !== 'all') {
        $clauses[] = 'a.tipe = ?';
        $params[] = (string) $filters['status'];
        $types .= 's';
    }

    if ((string) $filters['prodi'] !== '') {
        $clauses[] = 'm.prodi = ?';
        $params[] = (string) $filters['prodi'];
        $types .= 's';
    }

    if ((string) $filters['jenjang'] !== '') {
        $clauses[] = 'm.jenjang = ?';
        $params[] = (string) $filters['jenjang'];
        $types .= 's';
    }

    if ((string) $filters['alamat'] !== '') {
        $clauses[] = '((a.tipe = "mahasiswa" AND m.alamat = ?) OR (a.tipe = "guest" AND g.alamat = ?))';
        $params[] = (string) $filters['alamat'];
        $params[] = (string) $filters['alamat'];
        $types .= 'ss';
    }

    return implode(' AND ', $clauses);
}

function report_bind_params(mysqli_stmt $stmt, string $types, array $params): void
{
    $bindValues = [$types];
    foreach ($params as $key => $value) {
        $bindValues[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindValues);
}

function report_normalize_summary_row(?array $row): array
{
    return [
        'total' => (int) ($row['total'] ?? 0),
        'total_mahasiswa' => (int) ($row['total_mahasiswa'] ?? 0),
        'total_guest' => (int) ($row['total_guest'] ?? 0),
    ];
}

function report_safe_date(string $value, string $fallback): string
{
    $value = trim($value);
    $parsed = DateTime::createFromFormat('Y-m-d', $value);
    if ($parsed && $parsed->format('Y-m-d') === $value) {
        return $value;
    }
    return $fallback;
}

function report_safe_month(string $value, string $fallback): string
{
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}$/', $value) !== 1) {
        return $fallback;
    }
    return $value;
}
