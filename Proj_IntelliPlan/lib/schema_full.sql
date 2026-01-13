

CREATE DATABASE IF NOT EXISTS student_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE student_prod;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120),
  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_files_user (user_id),

  CONSTRAINT fk_files_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  file_id INT UNSIGNED NULL,

  title VARCHAR(255) NOT NULL,
  details TEXT,
  due_date DATETIME,
  status ENUM('open','done','archived') DEFAULT 'open',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_tasks_user (user_id),
  INDEX idx_tasks_file (file_id),

  CONSTRAINT fk_tasks_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_tasks_file
    FOREIGN KEY (file_id) REFERENCES files(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  file_id INT UNSIGNED NULL,

  title VARCHAR(255) NOT NULL,
  exam_date DATE NOT NULL,
  status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_exams_user (user_id),
  INDEX idx_exams_file (file_id),

  CONSTRAINT fk_exams_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_exams_file
    FOREIGN KEY (file_id) REFERENCES files(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE classes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,

  name VARCHAR(255) NOT NULL,
  subject VARCHAR(100),
  start_time TIME,
  end_time TIME,
  days VARCHAR(100),
  professor VARCHAR(255),
  status ENUM('active','inactive') DEFAULT 'active',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_classes_user (user_id),

  CONSTRAINT fk_classes_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE calendar_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,

  title VARCHAR(255) NOT NULL,
  description TEXT,
  start DATETIME NOT NULL,
  end DATETIME,
  all_day TINYINT(1) DEFAULT 0,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_calendar_user (user_id),

  CONSTRAINT fk_calendar_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;





SELECT u.id AS user_id, u.name AS username, t.title AS task_title, t.due_date FROM users u LEFT JOIN  tasks t ON u.id = t.user_id;

SELECT
    user_id,
    'task' AS item_type,
    title,
    due_date,
    status
FROM tasks

UNION ALL

SELECT
    user_id,
    'exam' AS item_type,
    title,
    exam_date AS due_date,
    status
FROM exams;

SELECT
    u.id AS user_id,
    u.name AS username,
    i.item_type,
    i.title,
    i.due_date,
    CASE
        WHEN i.status IN ('done','completed') THEN 'Done'
        WHEN i.status IS NULL THEN '—'
        ELSE 'Not Done'
    END AS status
FROM users u
LEFT JOIN (
    SELECT user_id, 'task' AS item_type, title, due_date, status
    FROM tasks
    UNION ALL
    SELECT user_id, 'exam', title, exam_date,  status
    FROM exams
) i ON u.id = i.user_id;
select * from tasks;
select * from exams;

