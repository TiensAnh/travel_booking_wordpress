UPDATE users
SET role = UPPER(TRIM(COALESCE(role, 'USER')));

INSERT INTO admins (username, email, password, role)
SELECT
  COALESCE(NULLIF(TRIM(name), ''), email),
  email,
  password,
  'admin'
FROM users u
WHERE u.role = 'ADMIN'
  AND NOT EXISTS (
    SELECT 1
    FROM admins a
    WHERE a.email = u.email
  );

UPDATE users
SET role = 'STAFF'
WHERE role = 'ADMIN';

UPDATE users
SET role = 'USER'
WHERE role NOT IN ('USER', 'STAFF');

ALTER TABLE users
  MODIFY COLUMN role ENUM('USER', 'STAFF') NOT NULL DEFAULT 'USER';
