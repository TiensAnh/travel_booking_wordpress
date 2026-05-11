ALTER TABLE tours
  ADD COLUMN badge VARCHAR(100) NULL AFTER tagline,
  ADD COLUMN season VARCHAR(100) NULL AFTER badge,
  ADD COLUMN departure_schedule VARCHAR(255) NULL AFTER season,
  ADD COLUMN meeting_point VARCHAR(255) NULL AFTER departure_schedule,
  ADD COLUMN curator_note TEXT NULL AFTER meeting_point,
  ADD COLUMN curator_name VARCHAR(255) NULL AFTER curator_note,
  ADD COLUMN promise_items_json TEXT NULL AFTER included_items_json,
  ADD COLUMN overview_cards_json TEXT NULL AFTER promise_items_json;
