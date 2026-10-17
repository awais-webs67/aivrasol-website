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
  image_mobile  VARCHAR(255) NOT NULL DEFAULT '',
  frame         VARCHAR(10)  NOT NULL DEFAULT 'auto',
  theme         VARCHAR(10)  NOT NULL DEFAULT 'auto',
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
  whatsapp       VARCHAR(40)  NOT NULL DEFAULT '',
  map_url        VARCHAR(500) NOT NULL DEFAULT '',
  is_head_office TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order     INT          NOT NULL DEFAULT 0,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_offices_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employees (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NULL UNIQUE,
  employee_code   VARCHAR(20)  NOT NULL UNIQUE,
  verify_token    CHAR(16)     NOT NULL UNIQUE,
  full_name       VARCHAR(100) NOT NULL,
  father_name     VARCHAR(100) NOT NULL DEFAULT '',
  pronoun         VARCHAR(10)  NOT NULL DEFAULT 'they',
  national_id     VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Encrypted',
  designation     VARCHAR(100) NOT NULL,
  department      VARCHAR(80)  NOT NULL DEFAULT '',
  employment_type VARCHAR(30)  NOT NULL DEFAULT 'Full-time',
  office_id       INT UNSIGNED NULL,
  work_email      VARCHAR(190) NOT NULL DEFAULT '',
  personal_email  VARCHAR(190) NOT NULL DEFAULT '',
  phone           VARCHAR(40)  NOT NULL DEFAULT '',
  emergency_contact VARCHAR(120) NOT NULL DEFAULT '',
  blood_group     VARCHAR(5)   NOT NULL DEFAULT '',
  join_date       DATE         NOT NULL,
  end_date        DATE         NULL,
  status          VARCHAR(20)  NOT NULL DEFAULT 'active',
  approval        VARCHAR(10)  NOT NULL DEFAULT 'approved' COMMENT 'pending when HR adds someone, until an admin approves',
  created_by      INT UNSIGNED NULL,
  approved_by     INT UNSIGNED NULL,
  approved_at     DATETIME     NULL,
  photo           VARCHAR(120) NOT NULL DEFAULT '',
  notes           TEXT         NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_emp_status (status),
  CONSTRAINT fk_emp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_emp_office FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_documents (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  employee_id  INT UNSIGNED NOT NULL,
  doc_type     VARCHAR(30)  NOT NULL,
  ref_no       VARCHAR(40)  NOT NULL UNIQUE,
  verify_token CHAR(16)     NOT NULL UNIQUE,
  title        VARCHAR(120) NOT NULL,
  data         MEDIUMTEXT   NOT NULL COMMENT 'JSON snapshot used to rebuild the PDF',
  issued_by    INT UNSIGNED NULL,
  issued_at    DATE         NOT NULL,
  revoked_at   DATETIME     NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_doc_emp (employee_id),
  CONSTRAINT fk_doc_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  company_name  VARCHAR(120) NOT NULL DEFAULT '',
  contact_name  VARCHAR(100) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  phone         VARCHAR(40)  NOT NULL DEFAULT '',
  country       VARCHAR(60)  NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  notes         TEXT         NULL,
  lead_id       INT UNSIGNED NULL,
  last_login_at TIMESTAMP    NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_projects (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  client_id    INT UNSIGNED NOT NULL,
  title        VARCHAR(150) NOT NULL,
  summary      TEXT         NULL,
  status       VARCHAR(20)  NOT NULL DEFAULT 'planning',
  progress     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  currency     CHAR(3)      NOT NULL DEFAULT 'USD',
  budget       DECIMAL(12,2) NOT NULL DEFAULT 0,
  start_date   DATE         NULL,
  due_date     DATE         NULL,
  manager_id   INT UNSIGNED NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cp_client (client_id),
  CONSTRAINT fk_cp_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_cp_manager FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_updates (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NULL,
  title      VARCHAR(150) NOT NULL,
  body       TEXT         NOT NULL,
  progress   TINYINT UNSIGNED NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pu_project (project_id, created_at),
  CONSTRAINT fk_pu_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_pu_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_messages (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id      INT UNSIGNED NOT NULL,
  sender_type     VARCHAR(10)  NOT NULL COMMENT 'client or staff',
  sender_id       INT UNSIGNED NOT NULL,
  sender_name     VARCHAR(100) NOT NULL,
  body            TEXT         NOT NULL,
  file_stored     VARCHAR(80)  NOT NULL DEFAULT '',
  file_name       VARCHAR(150) NOT NULL DEFAULT '',
  file_size       INT UNSIGNED NOT NULL DEFAULT 0,
  read_by_client  TINYINT(1)   NOT NULL DEFAULT 0,
  read_by_staff   TINYINT(1)   NOT NULL DEFAULT 0,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pm_project (project_id, id),
  CONSTRAINT fk_pm_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_transactions (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id  INT UNSIGNED NOT NULL,
  type        VARCHAR(10)  NOT NULL COMMENT 'invoice, payment or refund',
  amount      DECIMAL(12,2) NOT NULL,
  description VARCHAR(200) NOT NULL DEFAULT '',
  reference   VARCHAR(80)  NOT NULL DEFAULT '',
  method      VARCHAR(40)  NOT NULL DEFAULT '',
  entry_date  DATE         NOT NULL,
  due_date    DATE         NULL,
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pt_project (project_id, entry_date),
  CONSTRAINT fk_pt_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contacts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  first_name    VARCHAR(80)  NOT NULL DEFAULT '',
  last_name     VARCHAR(80)  NOT NULL DEFAULT '',
  company       VARCHAR(120) NOT NULL DEFAULT '',
  website       VARCHAR(190) NOT NULL DEFAULT '',
  country       VARCHAR(60)  NOT NULL DEFAULT '',
  tags          VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Comma separated, lowercase',
  source        VARCHAR(30)  NOT NULL DEFAULT 'manual',
  status        VARCHAR(20)  NOT NULL DEFAULT 'subscribed' COMMENT 'subscribed, unsubscribed, bounced, complained',
  token         CHAR(32)     NOT NULL UNIQUE,
  last_sent_at  DATETIME     NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_contacts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_suppressions (
  email_hash CHAR(64)    NOT NULL PRIMARY KEY COMMENT 'HMAC of the address, so no email is kept after deletion',
  reason     VARCHAR(20) NOT NULL,
  created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  kind       VARCHAR(20)  NOT NULL DEFAULT 'cold',
  layout     VARCHAR(10)  NOT NULL DEFAULT 'plain' COMMENT 'plain or branded',
  subject    VARCHAR(200) NOT NULL,
  body       TEXT         NOT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(120) NOT NULL,
  subject      VARCHAR(200) NOT NULL,
  body         TEXT         NOT NULL,
  layout       VARCHAR(10)  NOT NULL DEFAULT 'plain',
  from_name    VARCHAR(80)  NOT NULL DEFAULT '',
  reply_to     VARCHAR(190) NOT NULL DEFAULT '',
  audience_tag VARCHAR(60)  NOT NULL DEFAULT '' COMMENT 'Empty means every subscribed contact',
  status       VARCHAR(12)  NOT NULL DEFAULT 'draft' COMMENT 'draft, sending, paused, done, cancelled',
  created_by   INT UNSIGNED NULL,
  started_at   DATETIME     NULL,
  finished_at  DATETIME     NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_recipients (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED NOT NULL,
  contact_id  INT UNSIGNED NOT NULL,
  status      VARCHAR(10)  NOT NULL DEFAULT 'queued' COMMENT 'queued, sent, failed, skipped',
  error       VARCHAR(255) NOT NULL DEFAULT '',
  sent_at     DATETIME     NULL,
  UNIQUE KEY uq_campaign_contact (campaign_id, contact_id),
  KEY idx_cr_status (status, sent_at),
  CONSTRAINT fk_cr_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS system_checks (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  passed     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  warnings   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  failed     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  results    MEDIUMTEXT   NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sc_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_messages (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  thread_id    INT UNSIGNED NULL COMMENT 'First message of the conversation; NULL on the first message itself',
  employee_id  INT UNSIGNED NOT NULL,
  direction    VARCHAR(3)   NOT NULL DEFAULT 'out' COMMENT 'out = company to person, in = their reply',
  kind         VARCHAR(10)  NOT NULL DEFAULT 'message' COMMENT 'message or notice',
  subject      VARCHAR(190) NOT NULL,
  body         TEXT         NOT NULL,
  sender_id    INT UNSIGNED NULL,
  read_at      DATETIME     NULL,
  emailed      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sm_employee (employee_id),
  KEY idx_sm_thread (thread_id),
  CONSTRAINT fk_sm_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doc_templates (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code           VARCHAR(6)   NOT NULL COMMENT 'Short code used in reference numbers, for example EXP',
  name           VARCHAR(80)  NOT NULL,
  kind           VARCHAR(12)  NOT NULL DEFAULT 'certificate' COMMENT 'letter (A4 portrait) or certificate (A4 landscape)',
  title          VARCHAR(80)  NOT NULL,
  salutation     VARCHAR(120) NOT NULL DEFAULT '',
  body           TEXT         NOT NULL,
  needs_end_date TINYINT(1)   NOT NULL DEFAULT 0,
  details_label  VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'When set, the issuer fills in {details}',
  is_builtin     TINYINT(1)   NOT NULL DEFAULT 0,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order     INT          NOT NULL DEFAULT 0,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_profiles (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL UNIQUE,
  allowed      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Admin lets this person have a public page',
  slug         VARCHAR(60)  NULL UNIQUE COMMENT 'Address of the live page, /team/{slug}',
  draft        MEDIUMTEXT   NULL COMMENT 'JSON the member is editing',
  live         MEDIUMTEXT   NULL COMMENT 'JSON shown on the website',
  pending      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Draft sent for admin review',
  hidden       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Taken offline by an admin',
  admin_note   VARCHAR(500) NOT NULL DEFAULT '',
  submitted_at DATETIME     NULL,
  published_at DATETIME     NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  name       VARCHAR(100) NOT NULL PRIMARY KEY,
  applied_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
