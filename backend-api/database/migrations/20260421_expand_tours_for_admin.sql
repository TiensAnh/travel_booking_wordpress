ALTER TABLE tours
  ADD COLUMN duration_text VARCHAR(100) NULL AFTER duration,
  ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Draft' AFTER max_people,
  ADD COLUMN image_url VARCHAR(500) NULL AFTER status,
  ADD COLUMN transport VARCHAR(100) NULL AFTER image_url,
  ADD COLUMN departure_note VARCHAR(255) NULL AFTER transport,
  ADD COLUMN tagline VARCHAR(255) NULL AFTER departure_note,
  ADD COLUMN included_items_json TEXT NULL AFTER tagline,
  ADD COLUMN highlights_json TEXT NULL AFTER included_items_json,
  ADD COLUMN itinerary_json TEXT NULL AFTER highlights_json,
  ADD COLUMN created_by_admin_id INT NULL AFTER created_by;

ALTER TABLE tours
  ADD CONSTRAINT fk_tours_created_by_admin
  FOREIGN KEY (created_by_admin_id) REFERENCES admins(id);
