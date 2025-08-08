-- =====================================================
-- Multiplayer API Database Schema Updates
-- Email Verification System Implementation
-- Date: 2025-08-08
-- =====================================================

-- Add verification_token column to users table
-- This stores the email verification token sent to users
ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) NULL 
COMMENT 'Email verification token for account confirmation';

-- Add email_verified column to users table  
-- This tracks whether the user has verified their email address
ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 
COMMENT 'Boolean flag indicating if email is verified (0=false, 1=true)';

-- Add index on verification_token for faster lookups during email confirmation
CREATE INDEX idx_users_verification_token ON users(verification_token);

-- Add index on email_verified for filtering verified/unverified users
CREATE INDEX idx_users_email_verified ON users(email_verified);

-- =====================================================
-- Optional: Update existing users to have email_verified = 0
-- This ensures all existing users are marked as unverified
-- =====================================================
UPDATE users SET email_verified = 0 WHERE email_verified IS NULL;

-- =====================================================
-- Verification: Check the updated table structure
-- =====================================================
-- Run this to verify the changes:
-- DESCRIBE users;

-- =====================================================
-- Example queries for email verification system:
-- =====================================================

-- Find user by verification token (used during email confirmation)
-- SELECT user_id, email, email_verified FROM users WHERE verification_token = 'your_token_here';

-- Mark email as verified and clear token (used after successful confirmation)
-- UPDATE users SET email_verified = 1, verification_token = NULL, updated_at = UNIX_TIMESTAMP() WHERE verification_token = 'your_token_here';

-- Find unverified users (for admin purposes)
-- SELECT user_id, email, created_at FROM users WHERE email_verified = 0;

-- Count verified vs unverified users (for statistics)
-- SELECT 
--   SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
--   SUM(CASE WHEN email_verified = 0 THEN 1 ELSE 0 END) as unverified_users,
--   COUNT(*) as total_users
-- FROM users;

-- =====================================================
-- Notes:
-- =====================================================
-- 1. verification_token is nullable because it gets cleared after verification
-- 2. email_verified defaults to 0 (false) for new registrations
-- 3. Indexes are added for performance on common lookup operations
-- 4. All existing users will be marked as unverified initially
-- 5. The system will generate verification tokens during registration
-- =====================================================
