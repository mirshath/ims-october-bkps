-- =====================================================================
-- GOOGLE-FORMS-STYLE FORM BUILDER — DATABASE SCHEMA
-- =====================================================================
-- Import this into the same database used by database/connection.php
-- Example: mysql -u root -p your_db < forms_schema.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. FORMS  (the "form" itself — like a Google Form document)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS forms (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    title                 VARCHAR(255) NOT NULL,
    description           TEXT NULL,
    banner_image          VARCHAR(255) NULL,          -- path relative to google-form/, e.g. uploads/banners/xxx.jpg
    created_by            VARCHAR(100) NULL,          -- username / session user
    status                ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    accepting_responses   TINYINT(1) NOT NULL DEFAULT 1,
    collect_email         TINYINT(1) NOT NULL DEFAULT 0,   -- ask respondent for email
    one_response_per_user TINYINT(1) NOT NULL DEFAULT 0,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. FORM_QUESTIONS  (each question/field on a form)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS form_questions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    form_id        INT NOT NULL,
    question_text  VARCHAR(500) NOT NULL,
    question_image VARCHAR(255) NULL,      -- path relative to google-form/, e.g. uploads/questions/xxx.jpg
    question_type  ENUM(
                        'short_text',       -- single-line text
                        'paragraph',        -- multi-line text
                        'multiple_choice',  -- radio buttons
                        'checkbox',         -- multiple selectable
                        'dropdown',         -- select box
                        'linear_scale',     -- 1-5 / 1-10 rating
                        'date',
                        'time',
                        'file_upload'       -- respondent attaches an image/file as their answer
                    ) NOT NULL,
    is_required    TINYINT(1) NOT NULL DEFAULT 0,
    order_index    INT NOT NULL DEFAULT 0,
    scale_min      INT NULL DEFAULT 1,   -- used only for linear_scale
    scale_max      INT NULL DEFAULT 5,   -- used only for linear_scale
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. QUESTION_OPTIONS  (choices for multiple_choice / checkbox / dropdown)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS question_options (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    question_id   INT NOT NULL,
    option_text   VARCHAR(255) NOT NULL,
    order_index   INT NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES form_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. FORM_RESPONSES  (one row per person who submits the form)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS form_responses (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    form_id            INT NOT NULL,
    respondent_name    VARCHAR(150) NULL,
    respondent_email   VARCHAR(150) NULL,
    submitted_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address         VARCHAR(45) NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. RESPONSE_ANSWERS  (one row per answered question per response)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS response_answers (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    response_id    INT NOT NULL,
    question_id    INT NOT NULL,
    answer_text    TEXT NULL,   -- checkbox answers stored comma-separated; file answers store their path
    answer_type    ENUM('text','file') NOT NULL DEFAULT 'text',
    FOREIGN KEY (response_id) REFERENCES form_responses(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES form_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SAMPLE DATA (optional — remove if you don't want it)
-- =====================================================================
INSERT INTO forms (title, description, created_by, status) VALUES
('Customer Feedback Survey', 'Tell us about your experience', 'admin', 'published');

SET @form_id = LAST_INSERT_ID();

INSERT INTO form_questions (form_id, question_text, question_type, is_required, order_index) VALUES
(@form_id, 'What is your name?', 'short_text', 1, 1),
(@form_id, 'How satisfied are you with our service?', 'multiple_choice', 1, 2),
(@form_id, 'Which features do you use?', 'checkbox', 0, 3),
(@form_id, 'Rate us overall', 'linear_scale', 1, 4),
(@form_id, 'Any additional comments?', 'paragraph', 0, 5);

SET @q2 = (SELECT id FROM form_questions WHERE form_id = @form_id AND order_index = 2);
SET @q3 = (SELECT id FROM form_questions WHERE form_id = @form_id AND order_index = 3);

INSERT INTO question_options (question_id, option_text, order_index) VALUES
(@q2, 'Very Satisfied', 1),
(@q2, 'Satisfied', 2),
(@q2, 'Neutral', 3),
(@q2, 'Dissatisfied', 4),
(@q3, 'Dashboard', 1),
(@q3, 'Reports', 2),
(@q3, 'Notifications', 3);
