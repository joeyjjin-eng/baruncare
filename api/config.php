<?php
/**
 * DB 접속 설정
 *
 * 운영 환경에서는 이 파일을 docroot 바깥으로 옮기는 것을 권장합니다.
 * 같은 폴더에 둘 경우 .htaccess 의 deny 룰로 직접 접근을 차단합니다.
 */
declare(strict_types=1);

return [
    'host'    => '127.0.0.1',
    'port'    => 3306,
    'dbname'  => 'baruncare',
    'user'    => 'baruncare_app',
    'pass'    => '여기에-DB-패스워드-입력',
    'charset' => 'utf8mb4',
];
