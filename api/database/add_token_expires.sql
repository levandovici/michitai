-- Add token_expires column to users table for 24-hour email verification expiration
-- Run this SQL to update your existing database schema

ALTER TABLE users ADD COLUMN token_expires INT(11) NULL AFTER verification_token;

-- Add index for efficient token expiration queries
CREATE INDEX idx_token_expires ON users(token_expires);

-- Update existing users with NULL token_expires to have 24-hour expiration from now
-- (Only for users who have verification_token but no token_expires)
UPDATE users 
SET token_expires = UNIX_TIMESTAMP() + (24 * 60 * 60)
WHERE verification_token IS NOT NULL 
  AND token_expires IS NULL 
  AND email_verified = 0;

-- Verify the changes
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as users_with_tokens FROM users WHERE verification_token IS NOT NULL;
SELECT COUNT(*) as users_with_expires FROM users WHERE token_expires IS NOT NULL;
