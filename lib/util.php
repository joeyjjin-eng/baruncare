<?php
/**
 * 공용 유틸 — 세션, DB, 검증, HTML escape.
 */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ----- HTML ----- */
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function attr(?string $s): string {
    return h($s);
}

/* ----- Session flow state ----- */
function flow_get(string $flow, string $bucket = ''): array {
    $f = $_SESSION['flow'][$flow] ?? [];
    if ($bucket === '') return is_array($f) ? $f : [];
    $b = $f[$bucket] ?? [];
    return is_array($b) ? $b : [];
}
function flow_set(string $flow, string $bucket, array $data): void {
    $_SESSION['flow'][$flow][$bucket] = $data;
}
function flow_set_root(string $flow, array $data): void {
    $_SESSION['flow'][$flow] = $data;
}
function flow_clear(string $flow): void {
    unset($_SESSION['flow'][$flow]);
}

/* ----- 입력 정규화 ----- */
function in_str(?string $v, int $max = 255): string {
    if ($v === null) return '';
    return mb_substr(trim($v), 0, $max);
}
function in_name(?string $v): string {
    $v = (string)$v;
    $v = preg_replace('/[^가-힣ㄱ-ㅎㅏ-ㅣa-zA-Z\s]/u', '', $v) ?? '';
    return mb_substr(trim($v), 0, 50);
}
function in_phone(?string $v): string {
    $v = preg_replace('/\D/', '', (string)$v) ?? '';
    return substr($v, 0, 11);
}
function in_dob(?string $v): string {
    $v = preg_replace('/\D/', '', (string)$v) ?? '';
    return substr($v, 0, 8);
}
function in_digits(?string $v, int $max = 3): string {
    $v = preg_replace('/\D/', '', (string)$v) ?? '';
    return substr($v, 0, $max);
}
function in_date(?string $v): string {
    $v = trim((string)$v);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
}
function in_time(?string $v): string {
    $v = trim((string)$v);
    return preg_match('/^\d{2}:\d{2}$/', $v) ? $v : '';
}
function in_arr(?array $v): array {
    if (!is_array($v)) return [];
    return array_values(array_filter(array_map(
        fn($x) => is_string($x) ? trim($x) : null,
        $v
    ), fn($x) => $x !== null && $x !== ''));
}
function in_enum(?string $v, array $allowed): string {
    return in_array($v, $allowed, true) ? (string)$v : '';
}
function dob_valid(string $v): bool {
    if (strlen($v) !== 8) return false;
    return checkdate(
        (int)substr($v, 4, 2),
        (int)substr($v, 6, 2),
        (int)substr($v, 0, 4)
    );
}

/* ----- DB ----- */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $cfg = require __DIR__ . '/../api/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']
    );
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
function client_ip(): ?string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if (!$ip) return null;
    if (str_contains($ip, ',')) $ip = trim(explode(',', $ip)[0]);
    return mb_substr($ip, 0, 45);
}
function user_agent(): ?string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    return $ua ? mb_substr($ua, 0, 255) : null;
}

/* ----- Navigation ----- */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/* ----- Misc ----- */
function pr_seg(int $total, int $current): string {
    $out = '<div class="progress-wrap">';
    for ($i = 0; $i < $total; $i++) {
        $cls = $i < $current ? 'done' : ($i === $current ? 'active' : '');
        $out .= '<div class="progress-seg ' . $cls . '"></div>';
    }
    return $out . '</div>';
}

/** key-value list used by FindStep5/6 */
const MOBILITY        = ['전신마비','편마비','부축필요(휠체어/워커)','거동불가','자가보행'];
const SPECIAL_LEFT    = ['욕창','피딩(콧줄식사)','장루','소변줄','기저귀케어'];
const SPECIAL_RIGHT   = ['석션','투석','전염성 질환','재활치료보조',''];
const COGNITIVE_OPTS  = ['치매','섬망','정신질환','의사소통 불가','기타(입력)'];
const EXTRA_REQ_OPTS  = ['야간돌봄','간병일지 작성','간병인 보호자식 제공','비흡연자','남성 간병인 선호','여성 간병인 선호','기타, 요청사항'];

const INSURERS = [
    '삼성생명','한화생명','교보생명','신한라이프',
    '동양생명','ABL생명','흥국생명','DB생명',
    '푸본현대','처브라이프','AIA생명','메트라이프',
    'KB라이프','NH농협','미래에셋','BNP파리바',
    '하나생명','오렌지라이프'
];

const KOREAN_CITIES = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];

/* ----- 약관 정의 ----- */
function find_terms(): array { return [
    ['key'=>'tos','label'=>'서비스 이용약관 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe28380e88692e252bd573173?source=copy_link'],
    ['key'=>'privacy','label'=>'개인정보 수집 및 이용 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe28380d2a355f1a9c37011ee?pvs=21'],
    ['key'=>'sensitive','label'=>'민감정보 수집 및 이용 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe2838049b410c189675cf18e?pvs=21'],
    ['key'=>'thirdparty','label'=>'개인정보 제3자 제공 동의','required'=>true,'link'=>'https://www.notion.so/3-3597b6dfe2838079bd2af4cd3cf60a30?pvs=21'],
];}
function reg_terms(): array { return [
    ['key'=>'tos','label'=>'서비스 이용약관 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe283809fa905dc34a18b5955?pvs=21'],
    ['key'=>'privacy','label'=>'개인정보 수집 및 이용 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe2838040a989e43f20fd08d5?pvs=21'],
    ['key'=>'thirdparty','label'=>'개인정보 제3자 제공 동의','required'=>true,'link'=>'https://www.notion.so/3-3597b6dfe28380b58c92cd6f64fe88a5?pvs=21'],
];}
function claim_terms(): array { return [
    ['key'=>'tos','label'=>'서비스 이용약관 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe2838084a632f7a29f631d82?pvs=21'],
    ['key'=>'privacy','label'=>'개인정보 수집 및 이용 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe283803a89c9dd55fe7ff8ec?pvs=21'],
    ['key'=>'sensitive','label'=>'민감정보 수집 및 이용 동의','required'=>true,'link'=>'https://www.notion.so/3597b6dfe28380f7a5a3e39f0966e34f?pvs=21'],
    ['key'=>'thirdparty','label'=>'개인정보 제3자 제공 동의','required'=>true,'link'=>'https://www.notion.so/3-3597b6dfe2838094b0c3ef3ca99fa9f0?pvs=21'],
];}
