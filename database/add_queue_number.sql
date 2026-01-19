-- Add queue_number column to transactions table
ALTER TABLE transactions ADD COLUMN queue_number INT DEFAULT NULL AFTER status;
