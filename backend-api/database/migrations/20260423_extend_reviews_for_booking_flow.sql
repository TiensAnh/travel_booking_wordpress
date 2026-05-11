ALTER TABLE reviews
ADD COLUMN booking_id INT NULL AFTER id,
ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'VISIBLE' AFTER comment,
ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at;

UPDATE reviews r
JOIN bookings b
  ON b.user_id = r.user_id
 AND b.tour_id = r.tour_id
SET r.booking_id = b.id
WHERE r.booking_id IS NULL;

ALTER TABLE reviews
MODIFY COLUMN booking_id INT NOT NULL,
ADD CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
ADD CONSTRAINT uq_reviews_booking UNIQUE (booking_id);
