<?php
/**
 * POST /api/reg.php
 *
 * 요청 본문(JSON) — 모든 필드가 한 객체에 담깁니다(regData 그대로):
 *  {
 *    type, name, gender, dob, phone, city, district,
 *    patientName, patientGender, patientDob, patientPhone,
 *    patientCity, patientDistrict
 *  }
 */
declare(strict_types=1);
define('BARUNCARE_API', 1);
require __DIR__ . '/_lib.php';

$d = read_json_body();

// 간병인 본인 (필수)
$cgType   = require_in($d, 'type',   ['일반간병인', '가족간병인']);
$cgName   = require_str($d, 'name', 50);
$cgGender = require_in($d, 'gender', ['남성', '여성']);
$cgDob    = require_dob($d, 'dob');
$cgPhone  = require_phone($d, 'phone');
$cgCity   = s($d['city']     ?? null, 50);
$cgDist   = s($d['district'] ?? null, 50);

// 간병 대상자 (선택 — 가족간병인일 때 주로 입력)
$ptName   = s($d['patientName']     ?? null, 50);
$ptGender = s($d['patientGender']   ?? null);
if ($ptGender !== null && !in_array($ptGender, ['남성', '여성'], true)) $ptGender = null;
$ptDob    = null;
if (!empty($d['patientDob'])) {
    $tmp = preg_replace('/\D/', '', (string)$d['patientDob']);
    if (strlen($tmp) === 8 && checkdate(
        (int)substr($tmp, 4, 2),
        (int)substr($tmp, 6, 2),
        (int)substr($tmp, 0, 4)
    )) {
        $ptDob = $tmp;
    }
}
$ptPhone  = null;
if (!empty($d['patientPhone'])) {
    $tmp = preg_replace('/\D/', '', (string)$d['patientPhone']);
    if (strlen($tmp) >= 10 && strlen($tmp) <= 11) $ptPhone = $tmp;
}
$ptCity   = s($d['patientCity']     ?? null, 50);
$ptDist   = s($d['patientDistrict'] ?? null, 50);

try {
    $sql = <<<SQL
INSERT INTO caregiver_registrations (
    cg_type, cg_name, cg_gender, cg_dob, cg_phone, cg_city, cg_district,
    pt_name, pt_gender, pt_dob, pt_phone, pt_city, pt_district,
    user_agent, ip_addr
) VALUES (
    :cg_type, :cg_name, :cg_gender, :cg_dob, :cg_phone, :cg_city, :cg_district,
    :pt_name, :pt_gender, :pt_dob, :pt_phone, :pt_city, :pt_district,
    :user_agent, :ip_addr
)
SQL;
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':cg_type'     => $cgType,
        ':cg_name'     => $cgName,
        ':cg_gender'   => $cgGender,
        ':cg_dob'      => $cgDob,
        ':cg_phone'    => $cgPhone,
        ':cg_city'     => $cgCity,
        ':cg_district' => $cgDist,
        ':pt_name'     => $ptName,
        ':pt_gender'   => $ptGender,
        ':pt_dob'      => $ptDob,
        ':pt_phone'    => $ptPhone,
        ':pt_city'     => $ptCity,
        ':pt_district' => $ptDist,
        ':user_agent'  => user_agent(),
        ':ip_addr'     => client_ip(),
    ]);

    ok(['id' => (int)db()->lastInsertId()]);
} catch (PDOException $e) {
    error_log('[reg] ' . $e->getMessage());
    fail(500, '저장 중 오류가 발생했습니다');
}
