<?php
/**
 * POST /api/claim.php
 *
 * 요청 본문(JSON):
 *  {
 *    "cg":  { type, name, gender, dob, phone, city, district },
 *    "pt":  { patientName, patientGender, patientDob, patientPhone,
 *             patientCity, patientDistrict },
 *    "ins": "<보험사명>",
 *    "per": { start, end, hospital, room }
 *  }
 */
declare(strict_types=1);
define('BARUNCARE_API', 1);
require __DIR__ . '/_lib.php';

$body = read_json_body();
$cg  = $body['cg']  ?? [];
$pt  = $body['pt']  ?? [];
$ins = is_string($body['ins'] ?? null) ? trim((string)$body['ins']) : '';
$per = $body['per'] ?? [];

// 간병인
$cgType   = require_in($cg, 'type',   ['일반간병인', '가족간병인']);
$cgName   = require_str($cg, 'name', 50);
$cgGender = require_in($cg, 'gender', ['남성', '여성']);
$cgDob    = require_dob($cg, 'dob');
$cgPhone  = require_phone($cg, 'phone');
$cgCity   = require_str($cg, 'city', 50);
$cgDist   = require_str($cg, 'district', 50);

// 피보험자
// claimPt 의 키는 'patient*' 접두사를 갖습니다.
$ptName   = require_str($pt, 'patientName', 50);
$ptGender = require_in($pt, 'patientGender', ['남성', '여성']);
$ptDob    = require_dob($pt, 'patientDob');
$ptPhone  = require_phone($pt, 'patientPhone');
$ptCity   = require_str($pt, 'patientCity', 50);
$ptDist   = require_str($pt, 'patientDistrict', 50);

// 보험사
if ($ins === '') fail(400, '보험사를 선택해주세요');
$ins = mb_substr($ins, 0, 50);

// 간병 정보
$careStart = require_date($per, 'start');
$careEnd   = require_date($per, 'end');
$hospital  = require_str($per, 'hospital', 100);
$roomNo    = require_str($per, 'room', 50);

try {
    $sql = <<<SQL
INSERT INTO claim_requests (
    cg_type, cg_name, cg_gender, cg_dob, cg_phone, cg_city, cg_district,
    pt_name, pt_gender, pt_dob, pt_phone, pt_city, pt_district,
    insurer,
    care_start, care_end, hospital_name, room_no,
    user_agent, ip_addr
) VALUES (
    :cg_type, :cg_name, :cg_gender, :cg_dob, :cg_phone, :cg_city, :cg_district,
    :pt_name, :pt_gender, :pt_dob, :pt_phone, :pt_city, :pt_district,
    :insurer,
    :care_start, :care_end, :hospital_name, :room_no,
    :user_agent, :ip_addr
)
SQL;
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':cg_type'       => $cgType,
        ':cg_name'       => $cgName,
        ':cg_gender'     => $cgGender,
        ':cg_dob'        => $cgDob,
        ':cg_phone'      => $cgPhone,
        ':cg_city'       => $cgCity,
        ':cg_district'   => $cgDist,
        ':pt_name'       => $ptName,
        ':pt_gender'     => $ptGender,
        ':pt_dob'        => $ptDob,
        ':pt_phone'      => $ptPhone,
        ':pt_city'       => $ptCity,
        ':pt_district'   => $ptDist,
        ':insurer'       => $ins,
        ':care_start'    => $careStart,
        ':care_end'      => $careEnd,
        ':hospital_name' => $hospital,
        ':room_no'       => $roomNo,
        ':user_agent'    => user_agent(),
        ':ip_addr'       => client_ip(),
    ]);

    ok(['id' => (int)db()->lastInsertId()]);
} catch (PDOException $e) {
    error_log('[claim] ' . $e->getMessage());
    fail(500, '저장 중 오류가 발생했습니다');
}
