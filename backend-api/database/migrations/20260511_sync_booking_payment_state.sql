ALTER TABLE bookings ADD COLUMN IF NOT EXISTS booking_status VARCHAR(30) NOT NULL DEFAULT 'PENDING_PAYMENT' AFTER status;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status VARCHAR(30) NOT NULL DEFAULT 'PENDING' AFTER booking_status;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL' AFTER payment_status;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS confirmed_by VARCHAR(191) NULL AFTER payment_plan;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS confirmed_at DATETIME NULL AFTER confirmed_by;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER confirmed_at;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER paid_amount;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_receipt_sent_at DATETIME NULL AFTER remaining_amount;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS confirmation_sent_at DATETIME NULL AFTER payment_receipt_sent_at;

ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL' AFTER status;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER payment_plan;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER paid_amount;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER remaining_amount;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_sent_at DATETIME NULL AFTER refund_amount;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS refunded_at DATETIME NULL AFTER receipt_sent_at;

CREATE TABLE IF NOT EXISTS booking_details (
  booking_id INT PRIMARY KEY,
  contact_name VARCHAR(255) NOT NULL,
  contact_phone VARCHAR(50) NOT NULL,
  contact_email VARCHAR(255) NOT NULL,
  contact_country VARCHAR(120) NOT NULL,
  adults_count INT NOT NULL DEFAULT 1,
  children_count INT NOT NULL DEFAULT 0,
  special_requests TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_details_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS coupons (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_type ENUM('PERCENT', 'FIXED') NOT NULL DEFAULT 'PERCENT',
  discount_value DECIMAL(10,2) NOT NULL,
  min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_discount_amount DECIMAL(10,2) NULL,
  status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  description VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payment_transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  payment_id INT NOT NULL UNIQUE,
  booking_id INT NOT NULL,
  request_id VARCHAR(100) NOT NULL UNIQUE,
  provider VARCHAR(50) NOT NULL,
  payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL',
  transaction_code VARCHAR(100) NOT NULL UNIQUE,
  checkout_token VARCHAR(120) NOT NULL,
  coupon_code VARCHAR(50) NULL,
  base_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  return_url TEXT NULL,
  gateway_reference VARCHAR(120) NULL,
  gateway_payload_json TEXT NULL,
  completed_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_transactions_payment
    FOREIGN KEY (payment_id) REFERENCES payments(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_payment_transactions_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS booking_audit_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,
  actor_type VARCHAR(30) NOT NULL DEFAULT 'system',
  actor_id VARCHAR(100) NULL,
  actor_name VARCHAR(255) NULL,
  note TEXT NULL,
  payload_json TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_audit_logs_booking
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
    ON DELETE CASCADE
);

ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL' AFTER provider;
ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount;
ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER paid_amount;

UPDATE payments
SET
  method = UPPER(TRIM(COALESCE(method, ''))),
  status = UPPER(TRIM(COALESCE(status, 'PENDING'))),
  payment_plan = COALESCE(NULLIF(payment_plan, ''), 'FULL'),
  paid_amount = CASE
    WHEN status = 'SUCCESS' THEN COALESCE(NULLIF(paid_amount, 0), amount, 0)
    WHEN paid_amount IS NULL THEN 0
    ELSE paid_amount
  END,
  remaining_amount = CASE
    WHEN status = 'SUCCESS' THEN GREATEST(COALESCE(remaining_amount, 0), 0)
    WHEN remaining_amount IS NULL THEN 0
    ELSE remaining_amount
  END;

UPDATE payments
SET status = 'PENDING'
WHERE status = ''
   OR status NOT IN ('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED', 'EXPIRED', 'REFUNDED');

UPDATE bookings
SET
  status = CASE
    WHEN UPPER(TRIM(COALESCE(status, ''))) = 'PENDING' THEN 'PENDING_PAYMENT'
    WHEN UPPER(TRIM(COALESCE(status, ''))) = '' THEN 'PENDING_PAYMENT'
    ELSE UPPER(TRIM(status))
  END,
  booking_status = CASE
    WHEN UPPER(TRIM(COALESCE(booking_status, ''))) IN ('', 'PENDING') THEN 'PENDING_PAYMENT'
    ELSE UPPER(TRIM(booking_status))
  END,
  payment_status = CASE
    WHEN UPPER(TRIM(COALESCE(payment_status, ''))) = '' THEN 'PENDING'
    ELSE UPPER(TRIM(payment_status))
  END,
  payment_plan = COALESCE(NULLIF(payment_plan, ''), 'FULL'),
  paid_amount = COALESCE(paid_amount, 0),
  remaining_amount = COALESCE(remaining_amount, total_price, 0);

UPDATE bookings
SET booking_status = status
WHERE status IN ('CONFIRMED', 'COMPLETED', 'CANCELLED', 'REFUNDED')
  AND booking_status IN ('PENDING', 'PENDING_PAYMENT', 'PAYMENT_FAILED');

UPDATE bookings b
JOIN (
  SELECT
    booking_id,
    SUM(CASE WHEN status = 'SUCCESS' THEN COALESCE(NULLIF(paid_amount, 0), amount, 0) ELSE 0 END) AS success_paid,
    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) AS success_count,
    MAX(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) AS has_failed
  FROM payments
  GROUP BY booking_id
) p ON p.booking_id = b.id
SET
  b.payment_status = CASE
    WHEN p.success_count > 0 AND p.success_paid >= COALESCE(b.total_price, 0) THEN 'PAID'
    WHEN p.success_count > 0 THEN 'PARTIALLY_PAID'
    WHEN p.has_failed = 1 THEN 'FAILED'
    ELSE b.payment_status
  END,
  b.paid_amount = CASE
    WHEN p.success_count > 0 THEN p.success_paid
    ELSE b.paid_amount
  END,
  b.remaining_amount = CASE
    WHEN p.success_count > 0 THEN GREATEST(COALESCE(b.total_price, 0) - p.success_paid, 0)
    ELSE b.remaining_amount
  END,
  b.booking_status = CASE
    WHEN p.success_count > 0 AND b.status IN ('CONFIRMED', 'COMPLETED', 'CANCELLED', 'REFUNDED') THEN b.status
    WHEN p.success_count > 0 AND b.booking_status IN ('PENDING', 'PENDING_PAYMENT', 'PAYMENT_FAILED') THEN 'PENDING_CONFIRMATION'
    WHEN p.has_failed = 1 AND b.booking_status IN ('PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION') THEN 'PAYMENT_FAILED'
    ELSE b.booking_status
  END,
  b.status = CASE
    WHEN p.success_count > 0 AND b.status IN ('PENDING', 'PENDING_PAYMENT', 'PAYMENT_FAILED') THEN 'PENDING_CONFIRMATION'
    WHEN p.has_failed = 1 AND b.status IN ('PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION') THEN 'PAYMENT_FAILED'
    ELSE b.status
  END;

INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, status, description)
VALUES
  ('ADN10', 'PERCENT', 10, 1000000, 500000, 'ACTIVE', 'Giam 10% toi da 500.000d cho booking tu 1.000.000d'),
  ('SUMMER300', 'FIXED', 300000, 2500000, NULL, 'ACTIVE', 'Giam truc tiep 300.000d cho don tu 2.500.000d'),
  ('FAMILY5', 'PERCENT', 5, 1500000, 250000, 'ACTIVE', 'Danh cho gia dinh, giam 5% toi da 250.000d')
ON DUPLICATE KEY UPDATE
  discount_type = VALUES(discount_type),
  discount_value = VALUES(discount_value),
  min_order_amount = VALUES(min_order_amount),
  max_discount_amount = VALUES(max_discount_amount),
  status = VALUES(status),
  description = VALUES(description);
