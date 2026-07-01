<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function attendance_find_student_by_rfid(mysqli $db, string $rfidUid): ?array
{
    $query = $db->prepare(
        'SELECT nim, nama, prodi, jenjang, semester, tahun_akademik
         FROM mahasiswa
         WHERE rfid_uid = ?
         LIMIT 1'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare RFID query.');
    }

    $query->bind_param('s', $rfidUid);
    $query->execute();
    $result = $query->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $query->close();

    return $row ?: null;
}

function attendance_find_student_by_nim(mysqli $db, string $nim): ?array
{
    $query = $db->prepare(
        'SELECT nim, nama, prodi, jenjang, semester, tahun_akademik
         FROM mahasiswa
         WHERE nim = ?
         LIMIT 1'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare NIM query.');
    }

    $query->bind_param('s', $nim);
    $query->execute();
    $result = $query->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $query->close();

    return $row ?: null;
}

function attendance_find_student_by_search_key(mysqli $db, string $searchKey): ?array
{
    $searchKey = trim($searchKey);
    if ($searchKey === '') {
        return null;
    }

    $student = attendance_find_student_by_rfid($db, $searchKey);
    if ($student !== null) {
        $student['_matched_by'] = 'rfid';
        return $student;
    }

    $student = attendance_find_student_by_nim($db, $searchKey);
    if ($student !== null) {
        $student['_matched_by'] = 'nim';
        return $student;
    }

    return null;
}

function attendance_insert_student_visit(mysqli $db, array $student): void
{
    $tanggal = date('Y-m-d');
    $jam = date('H:i:s');
    $tipe = 'mahasiswa';
    $refId = (string) $student['nim'];
    $tahunAkademik = (string) $student['tahun_akademik'];
    $semester = (int) $student['semester'];

    $insert = $db->prepare(
        'INSERT INTO absensi (tipe, ref_id, tanggal, jam, tahun_akademik, semester)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if (!$insert) {
        throw new RuntimeException('Failed to prepare absensi insert for mahasiswa.');
    }

    $insert->bind_param('sssssi', $tipe, $refId, $tanggal, $jam, $tahunAkademik, $semester);
    $insert->execute();
    $insert->close();
}

function attendance_create_guest_visit(mysqli $db, string $nama, string $alamat, ?string $asalKampus): string
{
    $db->begin_transaction();

    try {
        $lastIdQuery = $db->prepare('SELECT guest_id FROM guest ORDER BY id DESC LIMIT 1 FOR UPDATE');
        if (!$lastIdQuery) {
            throw new RuntimeException('Failed to prepare guest sequence query.');
        }

        $lastIdQuery->execute();
        $result = $lastIdQuery->get_result();
        $lastRow = $result ? $result->fetch_assoc() : null;
        $lastIdQuery->close();

        $nextNumber = 1;
        if ($lastRow && isset($lastRow['guest_id']) && preg_match('/^GST-(\d+)$/', (string) $lastRow['guest_id'], $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }
        $guestId = sprintf('GST-%03d', $nextNumber);

        $insertGuest = $db->prepare(
            'INSERT INTO guest (guest_id, nama, alamat, asal_kampus)
             VALUES (?, ?, ?, ?)'
        );
        if (!$insertGuest) {
            throw new RuntimeException('Failed to prepare guest insert.');
        }
        $insertGuest->bind_param('ssss', $guestId, $nama, $alamat, $asalKampus);
        $insertGuest->execute();
        $insertGuest->close();

        $tanggal = date('Y-m-d');
        $jam = date('H:i:s');
        $tipe = 'guest';
        $insertAbsensi = $db->prepare(
            'INSERT INTO absensi (tipe, ref_id, tanggal, jam, tahun_akademik, semester)
             VALUES (?, ?, ?, ?, NULL, NULL)'
        );
        if (!$insertAbsensi) {
            throw new RuntimeException('Failed to prepare absensi insert for guest.');
        }
        $insertAbsensi->bind_param('ssss', $tipe, $guestId, $tanggal, $jam);
        $insertAbsensi->execute();
        $insertAbsensi->close();

        $db->commit();

        return $guestId;
    } catch (Throwable $exception) {
        $db->rollback();
        throw $exception;
    }
}
