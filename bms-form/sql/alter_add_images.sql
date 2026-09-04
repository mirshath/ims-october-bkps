-- =====================================================================
-- ALTER SCRIPT — adds image / banner / file-upload support
-- Run this AFTER forms_schema.sql has already been imported once.
-- Example: mysql -u root -p your_db < alter_add_images.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. FORMS — add a banner image (shown at the top of the public form,
--    like the cover photo on a Google Form)
-- ---------------------------------------------------------------------
ALTER TABLE forms
    ADD COLUMN banner_image VARCHAR(255) NULL DEFAULT NULL AFTER description;
    -- stores a path relative to /google-form/, e.g. "uploads/banners/64f1a2c9.jpg"

-- ---------------------------------------------------------------------
-- 2. FORM_QUESTIONS — allow a question to carry its own image,
--    and add a new answer type so respondents can upload a file/photo
-- ---------------------------------------------------------------------
ALTER TABLE form_questions
    ADD COLUMN question_image VARCHAR(255) NULL DEFAULT NULL AFTER question_text;
    -- stores a path relative to /google-form/, e.g. "uploads/questions/64f1a2c9.jpg"

ALTER TABLE form_questions
    MODIFY COLUMN question_type ENUM(
        'short_text',
        'paragraph',
        'multiple_choice',
        'checkbox',
        'dropdown',
        'linear_scale',
        'date',
        'time',
        'file_upload'      -- NEW: respondent can attach an image/file as their answer
    ) NOT NULL;

-- ---------------------------------------------------------------------
-- 3. RESPONSE_ANSWERS — tag whether an answer is plain text or an
--    uploaded file, so the admin views can render it correctly
--    (a link/thumbnail instead of plain text)
-- ---------------------------------------------------------------------
ALTER TABLE response_answers
    ADD COLUMN answer_type ENUM('text','file') NOT NULL DEFAULT 'text' AFTER answer_text;
    -- when answer_type = 'file', answer_text holds the stored file path
    -- relative to /google-form/, e.g. "uploads/responses/64f1a2c9.jpg"

-- ---------------------------------------------------------------------
-- Notes
-- ---------------------------------------------------------------------
-- * No existing rows are broken: banner_image / question_image default to
--   NULL (no image), and answer_type defaults to 'text' for every row
--   that already exists, which matches how they were being used before.
-- * Actual image files are NOT stored in the database — only their path
--   is. The files themselves live under google-form/uploads/ on disk.
--   See google-form/upload_image.php and the updated submit_response.php.
