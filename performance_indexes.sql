-- Performance indexes for the medex_system database
-- Run these to improve query performance, especially for lecturer assignments

-- Indexes for applications table
ALTER TABLE applications ADD INDEX idx_status (status);
ALTER TABLE applications ADD INDEX idx_student_id (student_id);
ALTER TABLE applications ADD INDEX idx_course_id (course_id);
ALTER TABLE applications ADD INDEX idx_created_at (created_at);

-- Indexes for users table
ALTER TABLE users ADD INDEX idx_role_status (role, status);
ALTER TABLE users ADD INDEX idx_department (department);
ALTER TABLE users ADD INDEX idx_email (email);

-- Indexes for notifications table
ALTER TABLE notifications ADD INDEX idx_user_id (user_id);
ALTER TABLE notifications ADD INDEX idx_is_read (is_read);
ALTER TABLE notifications ADD INDEX idx_created_at (created_at);
ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read);

-- Indexes for lecturer_notifications table
ALTER TABLE lecturer_notifications ADD INDEX idx_application_id (application_id);
ALTER TABLE lecturer_notifications ADD INDEX idx_lecturer_id (lecturer_id);
ALTER TABLE lecturer_notifications ADD INDEX idx_is_acknowledged (is_acknowledged);

-- Indexes for system_logs table
ALTER TABLE system_logs ADD INDEX idx_user_id (user_id);
ALTER TABLE system_logs ADD INDEX idx_created_at (created_at);

-- Indexes for courses table
ALTER TABLE courses ADD INDEX idx_department (department);
ALTER TABLE courses ADD INDEX idx_year (year);
ALTER TABLE courses ADD INDEX idx_status (status);