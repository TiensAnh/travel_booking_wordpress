ALTER TABLE tours
  ADD COLUMN gallery_images_json LONGTEXT NULL AFTER image_url,
  ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER badge,
  ADD COLUMN departure_dates_json TEXT NULL AFTER departure_schedule,
  ADD COLUMN excluded_items_json TEXT NULL AFTER included_items_json;

UPDATE tours
SET gallery_images_json = JSON_ARRAY(image_url)
WHERE (gallery_images_json IS NULL OR gallery_images_json = '')
  AND image_url IS NOT NULL
  AND image_url <> '';

UPDATE tours
SET is_featured = 1
WHERE is_featured = 0
  AND badge IS NOT NULL
  AND badge <> '';
