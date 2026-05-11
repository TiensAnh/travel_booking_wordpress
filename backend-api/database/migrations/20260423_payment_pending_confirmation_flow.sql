UPDATE payments
SET method = UPPER(TRIM(COALESCE(method, '')));

UPDATE payments
SET status = UPPER(TRIM(COALESCE(status, 'PENDING')));

UPDATE payments
SET status = 'PENDING'
WHERE status = ''
   OR status NOT IN ('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED');

ALTER TABLE payments
  MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  MODIFY COLUMN paid_at DATETIME NULL;
