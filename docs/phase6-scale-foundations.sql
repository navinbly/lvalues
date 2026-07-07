CREATE TABLE IF NOT EXISTS admin_audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_user_id INT UNSIGNED NULL,
  actor_role VARCHAR(60) NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(80) NULL,
  before_json MEDIUMTEXT NULL,
  after_json MEDIUMTEXT NULL,
  reason TEXT NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  approval_status ENUM('not_required','pending','approved','rejected') NOT NULL DEFAULT 'not_required',
  reviewer_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_admin_audit_actor_date (actor_user_id, created_at),
  KEY idx_admin_audit_entity (entity_type, entity_id),
  KEY idx_admin_audit_action_date (action, created_at),
  KEY idx_admin_audit_approval (approval_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_workflow_tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(80) NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status ENUM('open','assigned','waiting','escalated','resolved','cancelled') NOT NULL DEFAULT 'open',
  priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  assigned_to INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  due_at DATETIME NULL,
  resolved_at DATETIME NULL,
  resolution_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_admin_tasks_status_due (status, due_at),
  KEY idx_admin_tasks_assignee_status (assigned_to, status),
  KEY idx_admin_tasks_entity (entity_type, entity_id),
  KEY idx_admin_tasks_priority_due (priority, due_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_export_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  export_type VARCHAR(80) NOT NULL,
  requested_by INT UNSIGNED NULL,
  status ENUM('queued','processing','ready','failed','expired') NOT NULL DEFAULT 'queued',
  filters_json MEDIUMTEXT NULL,
  file_path VARCHAR(255) NULL,
  row_count INT UNSIGNED NULL,
  error_message TEXT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  expires_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_admin_exports_status_requested (status, requested_at),
  KEY idx_admin_exports_type_requested (export_type, requested_at),
  KEY idx_admin_exports_requested_by (requested_by, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_kpi_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  metric_key VARCHAR(120) NOT NULL,
  metric_scope VARCHAR(80) NOT NULL DEFAULT 'global',
  metric_value DECIMAL(18,4) NOT NULL DEFAULT 0,
  meta_json MEDIUMTEXT NULL,
  period_start DATETIME NULL,
  period_end DATETIME NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_kpi_metric_period (metric_key, metric_scope, period_start, period_end),
  KEY idx_admin_kpi_generated (generated_at),
  KEY idx_admin_kpi_metric_generated (metric_key, generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE contact ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'new';
ALTER TABLE contact ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal';
ALTER TABLE contact ADD COLUMN assigned_to INT UNSIGNED NULL;
ALTER TABLE contact ADD COLUMN due_at DATETIME NULL;
ALTER TABLE contact ADD COLUMN resolved_at DATETIME NULL;
ALTER TABLE contact ADD COLUMN resolution_note TEXT NULL;

CREATE INDEX idx_users_role_date ON users(role_id, date_added);
CREATE INDEX idx_users_instructor_date ON users(is_instructor, date_added);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_course_status_date ON course(status, date_added);
CREATE INDEX idx_course_category_status ON course(sub_category_id, status);
CREATE INDEX idx_course_creator_status ON course(creator, status);
CREATE INDEX idx_payment_date ON payment(date_added);
CREATE INDEX idx_payment_user_date ON payment(user_id, date_added);
CREATE INDEX idx_payment_course_date ON payment(course_id, date_added);
CREATE INDEX idx_payment_type_date ON payment(payment_type, date_added);
CREATE INDEX idx_contact_status_due ON contact(status, due_at);
CREATE INDEX idx_contact_priority_status ON contact(priority, status);
CREATE INDEX idx_contact_assignee_status ON contact(assigned_to, status);
