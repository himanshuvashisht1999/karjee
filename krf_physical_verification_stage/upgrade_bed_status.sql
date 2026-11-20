-- Run ONCE on the karjeefarm database BEFORE deploying updated index.php.
-- Existing rows, unique key and status values are preserved.
ALTER TABLE Physical_Verification_Bed_Status
MODIFY COLUMN status ENUM('In Progress','Complete','Empty') NOT NULL;
