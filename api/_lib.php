<?php
/**
 * 공용 유틸 — DB 연결, JSON 응답, 입력 검증.
 * 직접 호출되지 않도록 .htaccess 로 보호합니다.
 */
declare(strict_types=1);

if (!defined('BARUNCARE_API')) {
    http_response_code(403);
    exit('Forbidden');
}

/* ---------- DB ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $cfg = require __DIR__ . '/config.php';
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

/* ---------- 응답 ---------- */
function send_json(int $status, array $body): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(int $status, string $msg, array $extra = []): void {
    send_json($status, ['ok' => false, 'error' => $msg] + $extra);
}

function ok(array $data = []): void {
    send_json(200, ['ok' => true] + $data);
}

/* ---------- 요청 파싱 ---------- */
function read_json_body(): array {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        fail(405, 'Method Not Allowed');
    }
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') fail(400, '빈 요청 본문');

    try {
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        fail(400, '잘못된 JSON 형식');
    }
    if (!is_array($data)) fail(400, 'JSON 객체가 필요합니다');
    return $data;
}

/* ---------- 검증 헬퍼 ---------- */
function s(mixed $v, int $max = 255): ?string {
    if ($v === null) return null;
    if (!is_string($v) && !is_numeric($v)) return null;
    $v = trim((string)$v);
    if ($v === '') return null;
    return mb_substr($v, 0, $max);
}

function require_str(array $src, string $key, int $max = 255): string {
    $v = s($src[$key] ?? null, $max);
    if ($v === null) fail(400, "필수 항목 누락: {$key}");
    return $v;
}

function require_in(array $src, string $key, array $allowed): string {
    $v = s($src[$key] ?? null);
    if ($v === null || !in_array($v, $allowed, true)) {
        fail(400, "유효하지 않은 값: {$key}");
    }
    return $v;
}

function require_phone(array $src, string $key): string {
    $v = preg_replace('/\D/', '', (string)($src[$key] ?? ''));
    if (strlen($v) < 10 || strlen($v) > 11) {
        fail(400, "유효하지 않은 연락처: {$key}");
    }
    return $v;
}

function require_dob(array $src, string $key): string {
    $v = preg_replace('/\D/', '', (string)($src[$key] ?? ''));
    if (strlen($v) !== 8 || !checkdate(
        (int)substr($v, 4, 2),
        (int)substr($v, 6, 2),
        (int)substr($v, 0, 4)
    )) {
        fail(400, "유효하지 않은 생년월일: {$key}");
    }
    return $v;
}

function require_date(array $src, string $key): string {
    $v = s($src[$key] ?? null);
    if ($v === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        fail(400, "유효하지 않은 날짜: {$key}");
    }
    [$y, $m, $d] = explode('-', $v);
    if (!checkdate((int)$m, (int)$d, (int)$y)) {
        fail(400, "유효하지 않은 날짜: {$key}");
    }
    return $v;
}

function opt_time(mixed $v): ?string {
    $v = s($v, 5);
    if ($v === null) return null;
    return preg_match('/^\d{2}:\d{2}$/', $v) ? $v : null;
}

function opt_int(mixed $v, int $min = 0, int $max = 9999): ?int {
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) return null;
    $n = (int)$v;
    if ($n < $min || $n > $max) return null;
    return $n;
}

function arr_of_str(mixed $v): array {
    if (!is_array($v)) return [];
    return array_values(array_filter(array_map(
        fn($x) => is_string($x) ? trim($x) : null,
        $v
    ), fn($x) => $x !== null && $x !== ''));
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
