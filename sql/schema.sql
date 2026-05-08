-- =====================================================================
-- 바른케어플러스 — 신청 데이터 저장용 테이블 스키마
-- 대상: MariaDB 10.3.39
-- =====================================================================
-- 한 번만 실행하세요. (이미 만들어진 테이블이 있다면 DROP 후 실행하세요.)
-- 모든 테이블은 utf8mb4 / InnoDB.
-- 배열형 다중선택 컬럼은 JSON 으로 저장 (10.3 부터 JSON 지원).
-- =====================================================================

SET NAMES utf8mb4;

-- 데이터베이스 생성 (선택). 이미 만들어두셨다면 건너뛰세요.
-- CREATE DATABASE IF NOT EXISTS baruncare
--     DEFAULT CHARACTER SET utf8mb4
--     DEFAULT COLLATE utf8mb4_unicode_ci;
-- USE baruncare;


-- ---------------------------------------------------------------------
-- 1) 간병인 찾기 신청  (find_requests)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS find_requests (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- 신청인 정보
    req_name        VARCHAR(50)  NOT NULL,
    req_phone       VARCHAR(20)  NOT NULL,

    -- 환자 정보
    pt_name         VARCHAR(50)  NOT NULL,
    pt_gender       ENUM('남성','여성') NOT NULL,
    pt_dob          CHAR(8)      NOT NULL,                    -- YYYYMMDD
    pt_phone        VARCHAR(20)  NOT NULL,

    -- 간병 장소
    place_type      ENUM('집','병원') NOT NULL,
    hospital_name   VARCHAR(100) NULL,                        -- '병원' 일 때만
    city            VARCHAR(50)  NOT NULL,
    district        VARCHAR(50)  NOT NULL,

    -- 간병 일정
    care_start      DATE         NOT NULL,
    care_end        DATE         NOT NULL,
    start_time      CHAR(5)      NULL,                        -- HH:MM
    end_time        CHAR(5)      NULL,                        -- HH:MM
    all_day         TINYINT(1)   NOT NULL DEFAULT 0,
    schedule_note   TEXT         NULL,

    -- 환자 상태
    height_cm       SMALLINT UNSIGNED NULL,
    weight_kg       SMALLINT UNSIGNED NULL,
    mobility        VARCHAR(50)  NOT NULL,                    -- 1개 선택
    special_care    JSON         NULL,                        -- 배열
    cognitive       JSON         NULL,                        -- 배열
    cognitive_other VARCHAR(255) NULL,                        -- '기타(입력)' 추가 텍스트

    -- 추가 요청사항
    extra_requests  JSON         NULL,                        -- 배열
    extra_note      TEXT         NULL,

    -- 약관 동의 / 메타
    terms_agreed    TINYINT(1)   NOT NULL DEFAULT 1,
    user_agent      VARCHAR(255) NULL,
    ip_addr         VARCHAR(45)  NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_find_created_at (created_at),
    INDEX idx_find_req_phone  (req_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2) 간병인 등록  (caregiver_registrations)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS caregiver_registrations (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- 간병인 본인
    cg_type         ENUM('일반간병인','가족간병인') NOT NULL,
    cg_name         VARCHAR(50)  NOT NULL,
    cg_gender       ENUM('남성','여성') NOT NULL,
    cg_dob          CHAR(8)      NOT NULL,
    cg_phone        VARCHAR(20)  NOT NULL,
    cg_city         VARCHAR(50)  NULL,
    cg_district     VARCHAR(50)  NULL,

    -- 간병 대상자
    pt_name         VARCHAR(50)  NULL,
    pt_gender       ENUM('남성','여성') NULL,
    pt_dob          CHAR(8)      NULL,
    pt_phone        VARCHAR(20)  NULL,
    pt_city         VARCHAR(50)  NULL,
    pt_district     VARCHAR(50)  NULL,

    -- 약관 동의 / 메타
    terms_agreed    TINYINT(1)   NOT NULL DEFAULT 1,
    user_agent      VARCHAR(255) NULL,
    ip_addr         VARCHAR(45)  NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_reg_created_at (created_at),
    INDEX idx_reg_cg_phone   (cg_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 3) 간병비 보험금 청구 신청  (claim_requests)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS claim_requests (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- 간병인 정보
    cg_type         ENUM('일반간병인','가족간병인') NOT NULL,
    cg_name         VARCHAR(50)  NOT NULL,
    cg_gender       ENUM('남성','여성') NOT NULL,
    cg_dob          CHAR(8)      NOT NULL,
    cg_phone        VARCHAR(20)  NOT NULL,
    cg_city         VARCHAR(50)  NOT NULL,
    cg_district     VARCHAR(50)  NOT NULL,

    -- 피보험자 (간병 대상자)
    pt_name         VARCHAR(50)  NOT NULL,
    pt_gender       ENUM('남성','여성') NOT NULL,
    pt_dob          CHAR(8)      NOT NULL,
    pt_phone        VARCHAR(20)  NOT NULL,
    pt_city         VARCHAR(50)  NOT NULL,
    pt_district     VARCHAR(50)  NOT NULL,

    -- 보험사
    insurer         VARCHAR(50)  NOT NULL,

    -- 간병 정보
    care_start      DATE         NOT NULL,
    care_end        DATE         NOT NULL,
    hospital_name   VARCHAR(100) NOT NULL,
    room_no         VARCHAR(50)  NOT NULL,

    -- 약관 동의 / 메타
    terms_agreed    TINYINT(1)   NOT NULL DEFAULT 1,
    user_agent      VARCHAR(255) NULL,
    ip_addr         VARCHAR(45)  NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_claim_created_at (created_at),
    INDEX idx_claim_cg_phone   (cg_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 전용 DB 사용자 만들기 (선택, 권장)
-- =====================================================================
-- CREATE USER 'baruncare_app'@'localhost' IDENTIFIED BY '여기에-강력한-패스워드';
-- GRANT SELECT, INSERT, UPDATE ON baruncare.* TO 'baruncare_app'@'localhost';
-- FLUSH PRIVILEGES;
