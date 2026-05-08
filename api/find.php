<?php
/**
 * POST /api/find.php
 *
 * 요청 본문(JSON):
 *  {
 *    "req":   { reqName, reqPhone },
 *    "pt":    { name, gender, dob, phone },
 *    "place": { place, hospital, city, district },
 *    "when":  { start, end, startTime, endTime, allDay, note },
 *    "state": { height, weight, mobility, special[], cognitive[],
 *               cognitiveOther, extra[], note }
 *  }
 */
declare(strict_types=1);
define('BARUNCARE_API', 1);
require __DIR__ . '/_lib.php';

$body = read_json_body();

$req   = $body['req']   ?? [];
$pt    = $body['pt']    ?? [];
$place = $body['place'] ?? [];
$when  = $body['when']  ?? [];
$state = $body['state'] ?? [];

// 신청인
$reqName  = require_str($req, 'reqName', 50);
$reqPhone = require_phone($req, 'reqPhone');

// 환자
$ptName   = require_str($pt, 'name', 50);
$ptGender = require_in($pt, 'gender', ['남성', '여성']);
$ptDob    = require_dob($pt, 'dob');
$ptPhone  = require_phone($pt, 'phone');

// 장소
$placeType = require_in($place, 'place', ['집', '병원']);
$hospital  = $placeType === '병원' ? s($place['hospital'] ?? null, 100) : null;
$city      = require_str($place, 'city', 50);
$district  = require_str($place, 'district', 50);

// 일정
$careStart = require_date($when, 'start');
$careEnd   = require_date($when, 'end');
$allDay    = !empty($when['allDay']) ? 1 : 0;
$startTime = $allDay ? null : opt_time($when['startTime'] ?? null);
$endTime   = $allDay ? null : opt_time($when['endTime'] ?? null);
$schedNote = s($when['note'] ?? null, 2000);

if (!$allDay && (!$startTime || !$endTime)) {
    fail(400, '간병 시간이 누락되었습니다');
}

// 환자 상태
$height   = opt_int($state['height'] ?? null, 50, 250);
$weight   = opt_int($state['weight'] ?? null, 10, 300);
$mobility = require_str($state, 'mobility', 50);
$special  = arr_of_str($state['special'] ?? []);
$cognit   = arr_of_str($state['cognitive'] ?? []);
$cognOth  = s($state['cognitiveOther'] ?? null, 255);

// 추가 요청
$extraReq  = arr_of_str($state['extra'] ?? []);
$extraNote = s($state['note'] ?? null, 2000);

try {
    $sql = <<<SQL
INSERT INTO find_requests (
    req_name, req_phone,
    pt_name, pt_gender, pt_dob, pt_phone,
    place_type, hospital_name, city, district,
    care_start, care_end, start_time, end_time, all_day, schedule_note,
    height_cm, weight_kg, mobility, special_care, cognitive, cognitive_other,
    extra_requests, extra_note,
    user_agent, ip_addr
) VALUES (
    :req_name, :req_phone,
    :pt_name, :pt_gender, :pt_dob, :pt_phone,
    :place_type, :hospital_name, :city, :district,
    :care_start, :care_end, :start_time, :end_time, :all_day, :schedule_note,
    :height_cm, :weight_kg, :mobility, :special_care, :cognitive, :cognitive_other,
    :extra_requests, :extra_note,
    :user_agent, :ip_addr
)
SQL;

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':req_name'        => $reqName,
        ':req_phone'       => $reqPhone,
        ':pt_name'         => $ptName,
        ':pt_gender'       => $ptGender,
        ':pt_dob'          => $ptDob,
        ':pt_phone'        => $ptPhone,
        ':place_type'      => $placeType,
        ':hospital_name'   => $hospital,
        ':city'            => $city,
        ':district'        => $district,
        ':care_start'      => $careStart,
        ':care_end'        => $careEnd,
        ':start_time'      => $startTime,
        ':end_time'        => $endTime,
        ':all_day'         => $allDay,
        ':schedule_note'   => $schedNote,
        ':height_cm'       => $height,
        ':weight_kg'       => $weight,
        ':mobility'        => $mobility,
        ':special_care'    => json_encode($special, JSON_UNESCAPED_UNICODE),
        ':cognitive'       => json_encode($cognit, JSON_UNESCAPED_UNICODE),
        ':cognitive_other' => $cognOth,
        ':extra_requests'  => json_encode($extraReq, JSON_UNESCAPED_UNICODE),
        ':extra_note'      => $extraNote,
        ':user_agent'      => user_agent(),
        ':ip_addr'         => client_ip(),
    ]);

    ok(['id' => (int)db()->lastInsertId()]);
} catch (PDOException $e) {
    error_log('[find] ' . $e->getMessage());
    fail(500, '저장 중 오류가 발생했습니다');
}
