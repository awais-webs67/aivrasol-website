-- AivraSol website database schema
-- MySQL 8+ / MariaDB 10.6+, utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(64)  NOT NULL PRIMARY KEY,
  setting_value TEXT         NULL,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service pages and "hire a developer" pages share one table
CREATE TABLE IF NOT EXISTS offerings (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type            ENUM('service','hire') NOT NULL DEFAULT 'service',
  slug            VARCHAR(80)  NOT NULL,
  title           VARCHAR(120) NOT NULL,
  short_title     VARCHAR(60)  NOT NULL,
  icon            VARCHAR(40)  NOT NULL DEFAULT 'code',
  summary         VARCHAR(400) NOT NULL,
  hero_text       VARCHAR(400) NOT NULL,
  quote           VARCHAR(160) NOT NULL,
  features        TEXT         NOT NULL COMMENT 'One per line: Title | Description',
  expertise_title VARCHAR(120) NOT NULL DEFAULT '',
  expertise_text  TEXT         NOT NULL,
  tech            VARCHAR(400) NOT NULL DEFAULT '' COMMENT 'Comma separated',
  meta_title      VARCHAR(160) NOT NULL DEFAULT '',
  meta_description VARCHAR(300) NOT NULL DEFAULT '',
  sort_order      INT          NOT NULL DEFAULT 0,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_offerings_type_slug (type, slug),
  KEY idx_offerings_active (type, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(80)  NOT NULL UNIQUE,
  title       VARCHAR(120) NOT NULL,
  category    VARCHAR(60)  NOT NULL,
  industry    VARCHAR(60)  NOT NULL DEFAULT '',
  location    VARCHAR(80)  NOT NULL DEFAULT '',
  summary     VARCHAR(400) NOT NULL,
  challenge   TEXT         NOT NULL,
  solution    TEXT         NOT NULL,
  results     TEXT         NOT NULL COMMENT 'One per line',
  tech        VARCHAR(400) NOT NULL DEFAULT '',
  url         VARCHAR(255) NOT NULL DEFAULT '',
  image       VARCHAR(255) NOT NULL DEFAULT '',
  is_featured TINYINT(1)   NOT NULL DEFAULT 0,
  is_sample   TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order  INT          NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_projects_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80)  NOT NULL,
  role       VARCHAR(80)  NOT NULL DEFAULT '',
  company    VARCHAR(80)  NOT NULL DEFAULT '',
  quote      VARCHAR(600) NOT NULL,
  rating     TINYINT      NOT NULL DEFAULT 5,
  avatar     VARCHAR(255) NOT NULL DEFAULT '',
  source_url VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT          NOT NULL DEFAULT 0,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plans (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category      ENUM('project','hire') NOT NULL DEFAULT 'project',
  title         VARCHAR(80)  NOT NULL,
  subtitle      VARCHAR(160) NOT NULL,
  icon          VARCHAR(40)  NOT NULL DEFAULT 'box',
  price_label   VARCHAR(60)  NOT NULL DEFAULT '' COMMENT 'e.g. From $3,500',
  price_note    VARCHAR(80)  NOT NULL DEFAULT '',
  features      TEXT         NOT NULL COMMENT 'One per line',
  q1_label      VARCHAR(80)  NOT NULL,
  q1_options    TEXT         NOT NULL COMMENT 'One per line',
  q2_label      VARCHAR(80)  NOT NULL,
  q2_options    TEXT         NOT NULL COMMENT 'One per line',
  is_highlighted TINYINT(1)  NOT NULL DEFAULT 0,
  show_on_home  TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order    INT          NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(160) NOT NULL,
  slug             VARCHAR(120) NOT NULL UNIQUE,
  category         VARCHAR(60)  NOT NULL DEFAULT '',
  excerpt          VARCHAR(400) NOT NULL DEFAULT '',
  body             MEDIUMTEXT   NOT NULL,
  image            VARCHAR(255) NOT NULL DEFAULT '',
  author_name      VARCHAR(80)  NOT NULL DEFAULT '',
  author_role      VARCHAR(80)  NOT NULL DEFAULT '',
  meta_title       VARCHAR(160) NOT NULL DEFAULT '',
  meta_description VARCHAR(300) NOT NULL DEFAULT '',
  published_at     DATE         NULL,
  is_active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_posts_live (is_active, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jobs (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(80)  NOT NULL UNIQUE,
  title        VARCHAR(120) NOT NULL,
  department   VARCHAR(60)  NOT NULL DEFAULT '',
  job_type     VARCHAR(40)  NOT NULL DEFAULT 'Full-time',
  location     VARCHAR(80)  NOT NULL DEFAULT '',
  experience   VARCHAR(40)  NOT NULL DEFAULT '',
  summary      VARCHAR(400) NOT NULL,
  responsibilities TEXT     NOT NULL COMMENT 'One per line',
  requirements TEXT         NOT NULL COMMENT 'One per line',
  sort_order   INT          NOT NULL DEFAULT 0,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  scope      VARCHAR(80)  NOT NULL DEFAULT 'general' COMMENT 'general, pricing, careers or a service slug',
  question   VARCHAR(255) NOT NULL,
  answer     TEXT         NOT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  KEY idx_faqs_scope (scope, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type        ENUM('contact','consultation','quote','hire') NOT NULL,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  phone       VARCHAR(40)  NOT NULL DEFAULT '',
  company     VARCHAR(120) NOT NULL DEFAULT '',
  message     TEXT         NULL,
  details     JSON         NULL,
  source_page VARCHAR(255) NOT NULL DEFAULT '',
  ip_hash     CHAR(64)     NOT NULL,
  user_agent  VARCHAR(255) NOT NULL DEFAULT '',
  status      ENUM('new','contacted','qualified','won','lost','spam') NOT NULL DEFAULT 'new',
  notes       TEXT         NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_leads_status (status, created_at),
  KEY idx_leads_type (type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  job_id       INT UNSIGNED NULL,
  position     VARCHAR(120) NOT NULL,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(190) NOT NULL,
  phone        VARCHAR(40)  NOT NULL DEFAULT '',
  profile_url  VARCHAR(255) NOT NULL DEFAULT '',
  message      TEXT         NULL,
  cv_file      VARCHAR(80)  NOT NULL DEFAULT '',
  cv_original  VARCHAR(160) NOT NULL DEFAULT '',
  ip_hash      CHAR(64)     NOT NULL,
  status       ENUM('new','reviewing','interview','offer','hired','rejected') NOT NULL DEFAULT 'new',
  notes        TEXT         NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_app_status (status, created_at),
  CONSTRAINT fk_app_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(80)  NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'editor',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at TIMESTAMP    NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  account_type VARCHAR(10)  NOT NULL DEFAULT 'user',
  account_id   INT UNSIGNED NOT NULL,
  token_hash   CHAR(64)     NOT NULL UNIQUE,
  expires_at   DATETIME     NOT NULL,
  used_at      DATETIME     NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pr_account (account_type, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  bucket     VARCHAR(64)  NOT NULL,
  subject    CHAR(64)     NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rl_lookup (bucket, subject, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(60)  NOT NULL,
  target     VARCHAR(120) NOT NULL DEFAULT '',
  ip_hash    CHAR(64)     NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offices (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  country        VARCHAR(80)  NOT NULL,
  city           VARCHAR(80)  NOT NULL DEFAULT '',
  address        VARCHAR(300) NOT NULL DEFAULT '',
  phone          VARCHAR(40)  NOT NULL DEFAULT '',
  email          VARCHAR(190) NOT NULL DEFAULT '',
  map_url        VARCHAR(500) NOT NULL DEFAULT '',
  is_head_office TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order     INT          NOT NULL DEFAULT 0,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_offices_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_log (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type       VARCHAR(40)  NOT NULL,
  recipients VARCHAR(500) NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  status     ENUM('sent','failed') NOT NULL,
  error      VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  name       VARCHAR(100) NOT NULL PRIMARY KEY,
  applied_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
