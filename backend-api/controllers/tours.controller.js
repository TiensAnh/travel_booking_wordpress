const fs = require('fs');
const db = require('../config/db');
// hàm xoá bỏ khảong trắng và trả về rỗng nếu không phải 1 chuỗi 
function normalizeText(value) {
  return typeof value === 'string' ? value.trim() : '';
}

function normalizeStatus(value = '') {
  const normalized = normalizeText(value).toLowerCase();

  if (normalized === 'draft') {
    return 'Draft';
  }

  if (normalized === 'closed') {
    return 'Closed';
  }

  return 'Active';
}

function parseNumber(value) {
  if (value === undefined || value === null || value === '') {
    return null;
  }

  const normalized = String(value).replace(/[^\d.-]/g, ''); // thay thế các kí tự không phải số thành chuỗi rỗng
  const parsedValue = Number(normalized); // trả về NAn nếu là --5 hoặc 123.123.123
  return Number.isFinite(parsedValue) ? parsedValue : null; // isFitnite kiểm tra số có hợp lệ không không phải NaN và inf
}

function parsePositiveInteger(value) { // đây là hàm lấy chuỗi số nguyên đầu tiên không âm
  if (value === undefined || value === null || value === '') {
    return null;
  }

  const matchedValue = String(value).match(/\d+/); // chọn ra chuỗi số trong 1 chuỗi vd abc123xyz345 -> 123 regex match
  const parsedValue = matchedValue ? Number(matchedValue[0]) : Number(value);

  if (!Number.isInteger(parsedValue) || parsedValue <= 0) {
    return null;
  }

  return parsedValue;
}

function buildDurationText(value, fallbackDays) { // fallbacksDays là dữ liệu phụ khi dữ liệu chính thiếu
  const durationText = normalizeText(value);

  if (durationText) {
    return durationText;
  }

  if (fallbackDays) {
    const nights = Math.max(fallbackDays - 1, 0);
    return `${fallbackDays} ngay ${nights} dem`;
  }

  return '';
}

function normalizeStringArray(value) {
  if (Array.isArray(value)) {
    return value.map((item) => normalizeText(item)).filter(Boolean);
  }

  if (typeof value === 'string') {
    return value
      .split('\n')
      .map((item) => item.trim())
      .filter(Boolean);
  }

  return [];
}

function normalizeBoolean(value) {
  if (typeof value === 'boolean') {
    return value;
  }

  const normalized = normalizeText(value).toLowerCase();
  return ['1', 'true', 'yes', 'on', 'active', 'featured'].includes(normalized);
}

function normalizeGalleryImages(value, fallbackImage = '') {
  const images = [];
  const appendImage = (item) => {
    const image = normalizeText(item);

    if (image && !images.includes(image)) {
      images.push(image);
    }
  };

  if (Array.isArray(value)) {
    value.forEach((item) => {
      if (typeof item === 'string') {
        appendImage(item);
        return;
      }

      if (item && typeof item === 'object') {
        appendImage(item.url || item.imageUrl || item.image_url || item.path || '');
      }
    });
  } else if (typeof value === 'string') {
    const trimmed = value.trim();

    if (trimmed.startsWith('[')) {
      try {
        normalizeGalleryImages(JSON.parse(trimmed)).forEach(appendImage);
      } catch (error) {
        trimmed.split(/\r?\n/).forEach(appendImage);
      }
    } else {
      trimmed.split(/\r?\n/).forEach(appendImage);
    }
  }

  appendImage(fallbackImage);

  return images;
}

function normalizeDepartureDates(value) {
  const rows = [];

  if (Array.isArray(value)) {
    value.forEach((item) => {
      if (typeof item === 'string') {
        const parts = item.split('|').map((part) => part.trim());
        const rowValue = normalizeText(parts[0]);
        const rowLabel = normalizeText(parts[1] || parts[0]);

        if (rowValue) {
          rows.push({
            value: rowValue,
            label: rowLabel || rowValue,
          });
        }

        return;
      }

      if (!item || typeof item !== 'object') {
        return;
      }

      const rowValue = normalizeText(item.value);
      const rowLabel = normalizeText(item.label || item.value);

      if (!rowValue) {
        return;
      }

      rows.push({
        value: rowValue,
        label: rowLabel || rowValue,
      });
    });

    return rows;
  }

  if (typeof value === 'string') {
    return value
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
      .map((line) => {
        const parts = line.split('|').map((part) => part.trim());
        return {
          value: normalizeText(parts[0]),
          label: normalizeText(parts[1] || parts[0]),
        };
      })
      .filter((item) => item.value);
  }

  return [];
}

function normalizeOverviewCards(value) {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((item, index) => {
      const title = normalizeText(item?.title) || `Muc noi dung ${index + 1}`;
      const description = normalizeText(item?.description);
      const icon = normalizeText(item?.icon);

      if (!title && !description) {
        return null;
      }

      return {
        title,
        description,
        ...(icon ? { icon } : {}),
      };
    })
    .filter((item) => item && (item.title || item.description));
}

function normalizeHighlights(value) {
  if (Array.isArray(value)) {
    return value
      .map((item, index) => {
        if (typeof item === 'string') {
          const description = normalizeText(item);
          return description
            ? {
              title: `Diem nhan ${index + 1}`,
              description,
            }
            : null;
        }

        const title = normalizeText(item?.title) || `Diem nhan ${index + 1}`;
        const description = normalizeText(item?.description || item?.title || '');
        const icon = normalizeText(item?.icon);

        if (!title && !description) {
          return null;
        }

        return {
          title,
          description: description || title,
          ...(icon ? { icon } : {}),
        };
      })
      .filter(Boolean);
  }

  return normalizeStringArray(value).map((item, index) => ({
    title: `Diem nhan ${index + 1}`,
    description: item,
  }));
}

function normalizeItinerary(body = {}) {
  if (Array.isArray(body.itinerary)) {
    return body.itinerary
      .map((item, index) => {
        let label = normalizeText(item?.label);
        let title = normalizeText(item?.title);
        let description = normalizeText(item?.description);

        if (!title && !description && label) {
          description = label;
          const matchedDay = label.match(/^(ngày|ngay|day)\s*([0-9]+)\s*[:\-–]\s*(.+)$/iu);

          if (matchedDay) {
            label = `Ngày ${matchedDay[2]}`;
            title = normalizeText(matchedDay[3]);
            description = title;
          }
        } else if (title && !description) {
          description = title;
        }

        if (!label) {
          label = `Ngày ${index + 1}`;
        }

        if (!title) {
          title = `Lich trinh ${index + 1}`;
        }

        return description
          ? {
            label,
            title,
            description,
          }
          : null;
      })
      .filter(Boolean);
  }

  const fallbackItems = [body.dayOne, body.dayTwo, body.dayThree]
    .map((item, index) => ({
      label: `Ngày ${index + 1}`,
      title: `Lich trinh ${index + 1}`,
      description: normalizeText(item),
    }))
    .filter((item) => item.description);

  return fallbackItems;
}

function safelyParseJsonArray(value, fallback = []) {
  if (!value) {
    return fallback;
  }

  if (Array.isArray(value)) {
    return value;
  }

  try {
    const parsedValue = JSON.parse(value);
    return Array.isArray(parsedValue) ? parsedValue : fallback;
  } catch (error) {
    return fallback;
  }
}

function getUploadedTourFiles(req) {
  const fileGroups = req && req.files && typeof req.files === 'object' ? req.files : {};
  const groupedImage = Array.isArray(fileGroups.image) && fileGroups.image[0] ? fileGroups.image[0] : null;
  const galleryFiles = [
    ...(Array.isArray(fileGroups.galleryImages) ? fileGroups.galleryImages : []),
    ...(Array.isArray(fileGroups.gallery_images) ? fileGroups.gallery_images : []),
    ...(Array.isArray(fileGroups.gallery) ? fileGroups.gallery : []),
  ];

  return {
    image: req && req.file ? req.file : groupedImage,
    gallery: galleryFiles,
  };
}

function buildUploadedAssetPath(file) {
  return file && file.filename ? `/uploads/tours/${file.filename}` : '';
}

async function getTourColumnSet() {
  const [columns] = await db.query('SHOW COLUMNS FROM tours');
  return new Set(columns.map((column) => column.Field));
}

function buildTourSelect(columnSet) {
  const selectParts = [
    'id',
    'title',
    'description',
    'price',
    'location',
    'duration',
    'max_people',
    'created_by',
    'created_at',
  ];

  selectParts.push(columnSet.has('status') ? 'status' : "'Active' AS status");
  selectParts.push(columnSet.has('image_url') ? 'image_url' : 'NULL AS image_url');
  selectParts.push(columnSet.has('gallery_images_json') ? 'gallery_images_json' : 'NULL AS gallery_images_json');
  selectParts.push(columnSet.has('transport') ? 'transport' : "'' AS transport");
  selectParts.push(columnSet.has('departure_note') ? 'departure_note' : "'' AS departure_note");
  selectParts.push(columnSet.has('tagline') ? 'tagline' : "'' AS tagline");
  selectParts.push(columnSet.has('badge') ? 'badge' : "'' AS badge");
  selectParts.push(columnSet.has('is_featured') ? 'is_featured' : '0 AS is_featured');
  selectParts.push(columnSet.has('season') ? 'season' : "'' AS season");
  selectParts.push(columnSet.has('departure_schedule') ? 'departure_schedule' : "'' AS departure_schedule");
  selectParts.push(columnSet.has('departure_dates_json') ? 'departure_dates_json' : 'NULL AS departure_dates_json');
  selectParts.push(columnSet.has('meeting_point') ? 'meeting_point' : "'' AS meeting_point");
  selectParts.push(columnSet.has('curator_note') ? 'curator_note' : "'' AS curator_note");
  selectParts.push(columnSet.has('curator_name') ? 'curator_name' : "'' AS curator_name");
  selectParts.push(columnSet.has('duration_text') ? 'duration_text' : "'' AS duration_text");
  selectParts.push(columnSet.has('included_items_json') ? 'included_items_json' : 'NULL AS included_items_json');
  selectParts.push(columnSet.has('excluded_items_json') ? 'excluded_items_json' : 'NULL AS excluded_items_json');
  selectParts.push(columnSet.has('promise_items_json') ? 'promise_items_json' : 'NULL AS promise_items_json');
  selectParts.push(columnSet.has('overview_cards_json') ? 'overview_cards_json' : 'NULL AS overview_cards_json');
  selectParts.push(columnSet.has('highlights_json') ? 'highlights_json' : 'NULL AS highlights_json');
  selectParts.push(columnSet.has('itinerary_json') ? 'itinerary_json' : 'NULL AS itinerary_json');
  selectParts.push(
    columnSet.has('created_by_admin_id')
      ? 'created_by_admin_id'
      : 'NULL AS created_by_admin_id',
  );
  selectParts.push('(SELECT COUNT(*) FROM bookings WHERE bookings.tour_id = tours.id) AS booking_count');
  selectParts.push("(SELECT ROUND(AVG(reviews.rating), 1) FROM reviews WHERE reviews.tour_id = tours.id AND reviews.status = 'VISIBLE') AS average_rating");
  selectParts.push("(SELECT COUNT(*) FROM reviews WHERE reviews.tour_id = tours.id AND reviews.status = 'VISIBLE') AS total_reviews");

  return `SELECT ${selectParts.join(', ')} FROM tours`;
}

function serializeTour(row) {
  const durationDays = row.duration === null || row.duration === undefined ? null : Number(row.duration);
  const galleryImages = normalizeGalleryImages(safelyParseJsonArray(row.gallery_images_json), row.image_url || '');
  const imageUrl = row.image_url || galleryImages[0] || null;

  return {
    id: row.id,
    title: row.title || '',
    description: row.description || '',
    price: row.price === null || row.price === undefined ? null : Number(row.price),
    location: row.location || '',
    duration: durationDays,
    durationText: row.duration_text || buildDurationText('', durationDays),
    maxPeople: row.max_people === null || row.max_people === undefined ? null : Number(row.max_people),
    status: normalizeStatus(row.status),
    imageUrl,
    galleryImages,
    transport: row.transport || '',
    departureNote: row.departure_note || '',
    tagline: row.tagline || '',
    badge: row.badge || '',
    featured: row.is_featured === null || row.is_featured === undefined ? !!(row.badge || '') : Boolean(Number(row.is_featured)),
    season: row.season || '',
    departureSchedule: row.departure_schedule || '',
    departureDates: normalizeDepartureDates(safelyParseJsonArray(row.departure_dates_json)),
    meetingPoint: row.meeting_point || '',
    curatorNote: row.curator_note || '',
    curatorName: row.curator_name || '',
    rating: row.average_rating === null || row.average_rating === undefined ? null : Number(row.average_rating),
    reviews: row.total_reviews === null || row.total_reviews === undefined ? 0 : Number(row.total_reviews),
    includes: safelyParseJsonArray(row.included_items_json),
    excludes: safelyParseJsonArray(row.excluded_items_json),
    promiseItems: safelyParseJsonArray(row.promise_items_json),
    overviewCards: safelyParseJsonArray(row.overview_cards_json),
    highlights: safelyParseJsonArray(row.highlights_json),
    itinerary: safelyParseJsonArray(row.itinerary_json),
    bookingCount: row.booking_count === null || row.booking_count === undefined ? 0 : Number(row.booking_count),
    createdBy: row.created_by ?? null,
    createdByAdminId: row.created_by_admin_id ?? null,
    createdAt: row.created_at,
  };
}

async function getExistingTour(id, columnSet) {
  const [rows] = await db.query(`${buildTourSelect(columnSet)} WHERE id = ? LIMIT 1`, [id]);
  return rows[0] ? serializeTour(rows[0]) : null;
}

function getRequestPayload(req) {
  const requestBody = req.body && typeof req.body === 'object' ? { ...req.body } : {};

  if (typeof requestBody.payload === 'string') {
    try {
      const parsedPayload = JSON.parse(requestBody.payload);

      if (!parsedPayload || typeof parsedPayload !== 'object' || Array.isArray(parsedPayload)) {
        return {
          error: 'Dữ liệu tour gửi lên không hợp lệ.',
        };
      }

      delete requestBody.payload;
      Object.assign(requestBody, parsedPayload);
    } catch (error) {
      return {
        error: 'Dữ liệu tour gửi lên không hợp lệ.',
      };
    }
  }

  const uploadedFiles = getUploadedTourFiles(req);
  const submittedGallery = normalizeGalleryImages(
    requestBody.galleryImages ?? requestBody.gallery_images,
    requestBody.imageUrl || requestBody.image_url,
  );
  const uploadedImage = uploadedFiles.image ? buildUploadedAssetPath(uploadedFiles.image) : '';
  const uploadedGallery = Array.isArray(uploadedFiles.gallery)
    ? uploadedFiles.gallery.map(buildUploadedAssetPath).filter(Boolean)
    : [];

  requestBody.imageUrl = uploadedImage || normalizeText(requestBody.imageUrl || requestBody.image_url || submittedGallery[0] || uploadedGallery[0] || '');
  requestBody.galleryImages = normalizeGalleryImages([
    requestBody.imageUrl,
    ...submittedGallery,
    ...uploadedGallery,
  ]);

  return {
    body: requestBody,
  };
}

async function removeUploadedFile(file) {
  if (!file) {
    return;
  }

  if (Array.isArray(file)) {
    await Promise.all(file.map((item) => removeUploadedFile(item)));
    return;
  }

  if (typeof file === 'object' && !file.path) {
    await Promise.all(Object.values(file).map((item) => removeUploadedFile(item)));
    return;
  }

  if (!file.path) {
    return;
  }

  try {
    await fs.promises.unlink(file.path);
  } catch (error) {
    // Ignore cleanup failures so the original API error can be returned.
  }
}

function buildWritePayload(body = {}, adminId = null) {
  const title = normalizeText(body.title || body.name);
  const description = normalizeText(body.description);
  const location = normalizeText(body.location);
  const duration = parsePositiveInteger(body.durationDays ?? body.duration);
  const durationText = buildDurationText(body.durationText ?? body.duration, duration);
  const price = parseNumber(body.price);
  const maxPeople = parsePositiveInteger(body.maxPeople ?? body.max_people ?? body.groupSize);
  const status = normalizeStatus(body.status || 'Draft');
  const galleryImages = normalizeGalleryImages(body.galleryImages ?? body.gallery_images, body.imageUrl || body.image_url);
  const imageUrl = normalizeText(body.imageUrl || body.image_url || galleryImages[0] || '');
  const transport = normalizeText(body.transport);
  const departureNote = normalizeText(body.departureNote || body.departure_note || body.departure);
  const tagline = normalizeText(body.tagline);
  const badge = normalizeText(body.badge);
  const featured = normalizeBoolean(body.featured ?? body.is_featured ?? body.isFeatured ?? badge);
  const season = normalizeText(body.season);
  const departureSchedule = normalizeText(body.departureSchedule || body.departure_schedule);
  const departureDates = normalizeDepartureDates(body.departureDates ?? body.departure_dates);
  const meetingPoint = normalizeText(body.meetingPoint || body.meeting_point);
  const curatorNote = normalizeText(body.curatorNote || body.curator_note);
  const curatorName = normalizeText(body.curatorName || body.curator_name);
  const includes = normalizeStringArray(body.includes ?? body.includedItems);
  const excludes = normalizeStringArray(body.excludes ?? body.excludedItems);
  const promiseItems = normalizeStringArray(body.promiseItems ?? body.promise_items);
  const overviewCards = normalizeOverviewCards(body.overviewCards ?? body.overview_cards);
  const highlights = normalizeHighlights(body.highlights);
  const itinerary = normalizeItinerary(body);
  const validationErrors = {};

  if (!title) {
    validationErrors.title = 'Vui lòng nhập tên tour.';
  }

  if (!location) {
    validationErrors.location = 'Vui lòng nhập địa điểm.';
  }

  if (!duration) {
    validationErrors.duration = 'Thoi luong tour phai la so nguyen duong.';
  }

  if (price === null || price < 0) {
    validationErrors.price = 'Gia tour phai la so hop le.';
  }


  return {
    data: {
      title,
      description: description || null,
      location,
      duration,
      durationText: durationText || null,
      price,
      maxPeople,
      status,
      imageUrl: imageUrl || null,
      galleryImages,
      transport: transport || null,
      departureNote: departureNote || null,
      tagline: tagline || null,
      badge: badge || null,
      featured,
      season: season || null,
      departureSchedule: departureSchedule || null,
      departureDates,
      meetingPoint: meetingPoint || null,
      curatorNote: curatorNote || null,
      curatorName: curatorName || null,
      includes,
      excludes,
      promiseItems,
      overviewCards,
      highlights,
      itinerary,
      createdByAdminId: adminId,
    },
    validationErrors,
  };
}

function buildInsertParts(columnSet, payload) {
  const columns = ['title', 'description', 'price', 'location', 'duration', 'max_people'];
  const values = [
    payload.title,
    payload.description,
    payload.price,
    payload.location,
    payload.duration,
    payload.maxPeople,
  ];

  if (columnSet.has('status')) {
    columns.push('status');
    values.push(payload.status);
  }

  if (columnSet.has('image_url')) {
    columns.push('image_url');
    values.push(payload.imageUrl);
  }

  if (columnSet.has('gallery_images_json')) {
    columns.push('gallery_images_json');
    values.push(JSON.stringify(payload.galleryImages));
  }

  if (columnSet.has('transport')) {
    columns.push('transport');
    values.push(payload.transport);
  }

  if (columnSet.has('departure_note')) {
    columns.push('departure_note');
    values.push(payload.departureNote);
  }

  if (columnSet.has('tagline')) {
    columns.push('tagline');
    values.push(payload.tagline);
  }

  if (columnSet.has('badge')) {
    columns.push('badge');
    values.push(payload.badge);
  }

  if (columnSet.has('is_featured')) {
    columns.push('is_featured');
    values.push(payload.featured ? 1 : 0);
  }

  if (columnSet.has('season')) {
    columns.push('season');
    values.push(payload.season);
  }

  if (columnSet.has('departure_schedule')) {
    columns.push('departure_schedule');
    values.push(payload.departureSchedule);
  }

  if (columnSet.has('departure_dates_json')) {
    columns.push('departure_dates_json');
    values.push(JSON.stringify(payload.departureDates));
  }

  if (columnSet.has('meeting_point')) {
    columns.push('meeting_point');
    values.push(payload.meetingPoint);
  }

  if (columnSet.has('curator_note')) {
    columns.push('curator_note');
    values.push(payload.curatorNote);
  }

  if (columnSet.has('curator_name')) {
    columns.push('curator_name');
    values.push(payload.curatorName);
  }

  if (columnSet.has('duration_text')) {
    columns.push('duration_text');
    values.push(payload.durationText);
  }

  if (columnSet.has('included_items_json')) {
    columns.push('included_items_json');
    values.push(JSON.stringify(payload.includes));
  }

  if (columnSet.has('excluded_items_json')) {
    columns.push('excluded_items_json');
    values.push(JSON.stringify(payload.excludes));
  }

  if (columnSet.has('promise_items_json')) {
    columns.push('promise_items_json');
    values.push(JSON.stringify(payload.promiseItems));
  }

  if (columnSet.has('overview_cards_json')) {
    columns.push('overview_cards_json');
    values.push(JSON.stringify(payload.overviewCards));
  }

  if (columnSet.has('highlights_json')) {
    columns.push('highlights_json');
    values.push(JSON.stringify(payload.highlights));
  }

  if (columnSet.has('itinerary_json')) {
    columns.push('itinerary_json');
    values.push(JSON.stringify(payload.itinerary));
  }

  if (columnSet.has('created_by_admin_id')) {
    columns.push('created_by_admin_id');
    values.push(payload.createdByAdminId);
  }

  return { columns, values };
}

function buildUpdateParts(columnSet, payload) {
  const assignments = [
    'title = ?',
    'description = ?',
    'price = ?',
    'location = ?',
    'duration = ?',
    'max_people = ?',
  ];
  const values = [
    payload.title,
    payload.description,
    payload.price,
    payload.location,
    payload.duration,
    payload.maxPeople,
  ];

  if (columnSet.has('status')) {
    assignments.push('status = ?');
    values.push(payload.status);
  }

  if (columnSet.has('image_url')) {
    assignments.push('image_url = ?');
    values.push(payload.imageUrl);
  }

  if (columnSet.has('gallery_images_json')) {
    assignments.push('gallery_images_json = ?');
    values.push(JSON.stringify(payload.galleryImages));
  }

  if (columnSet.has('transport')) {
    assignments.push('transport = ?');
    values.push(payload.transport);
  }

  if (columnSet.has('departure_note')) {
    assignments.push('departure_note = ?');
    values.push(payload.departureNote);
  }

  if (columnSet.has('tagline')) {
    assignments.push('tagline = ?');
    values.push(payload.tagline);
  }

  if (columnSet.has('badge')) {
    assignments.push('badge = ?');
    values.push(payload.badge);
  }

  if (columnSet.has('is_featured')) {
    assignments.push('is_featured = ?');
    values.push(payload.featured ? 1 : 0);
  }

  if (columnSet.has('season')) {
    assignments.push('season = ?');
    values.push(payload.season);
  }

  if (columnSet.has('departure_schedule')) {
    assignments.push('departure_schedule = ?');
    values.push(payload.departureSchedule);
  }

  if (columnSet.has('departure_dates_json')) {
    assignments.push('departure_dates_json = ?');
    values.push(JSON.stringify(payload.departureDates));
  }

  if (columnSet.has('meeting_point')) {
    assignments.push('meeting_point = ?');
    values.push(payload.meetingPoint);
  }

  if (columnSet.has('curator_note')) {
    assignments.push('curator_note = ?');
    values.push(payload.curatorNote);
  }

  if (columnSet.has('curator_name')) {
    assignments.push('curator_name = ?');
    values.push(payload.curatorName);
  }

  if (columnSet.has('duration_text')) {
    assignments.push('duration_text = ?');
    values.push(payload.durationText);
  }

  if (columnSet.has('included_items_json')) {
    assignments.push('included_items_json = ?');
    values.push(JSON.stringify(payload.includes));
  }

  if (columnSet.has('excluded_items_json')) {
    assignments.push('excluded_items_json = ?');
    values.push(JSON.stringify(payload.excludes));
  }

  if (columnSet.has('promise_items_json')) {
    assignments.push('promise_items_json = ?');
    values.push(JSON.stringify(payload.promiseItems));
  }

  if (columnSet.has('overview_cards_json')) {
    assignments.push('overview_cards_json = ?');
    values.push(JSON.stringify(payload.overviewCards));
  }

  if (columnSet.has('highlights_json')) {
    assignments.push('highlights_json = ?');
    values.push(JSON.stringify(payload.highlights));
  }

  if (columnSet.has('itinerary_json')) {
    assignments.push('itinerary_json = ?');
    values.push(JSON.stringify(payload.itinerary));
  }

  return { assignments, values };
}

exports.getTours = async (req, res) => {
  const search = normalizeText(req.query.search);
  const requestedLocation = normalizeText(req.query.location || req.query.destination);
  const requestedStatus = normalizeText(req.query.status);

  // Phân trang: mặc định 9 tours/trang, số trang lấy động từ DB
  const LIMIT = Math.min(parsePositiveInteger(req.query.limit) || 9, 500);
  const page = Math.max(1, parsePositiveInteger(req.query.page) || 1);
  const offset = (page - 1) * LIMIT;

  try {
    const columnSet = await getTourColumnSet();
    const whereClauses = [];
    const params = [];

    if (search) {
      whereClauses.push('(title LIKE ? OR location LIKE ?)');
      params.push(`%${search}%`, `%${search}%`);
    }

    if (requestedLocation) {
      whereClauses.push('location LIKE ?');
      params.push(`%${requestedLocation}%`);
    }

    if (requestedStatus && columnSet.has('status')) {
      whereClauses.push('status = ?');
      params.push(normalizeStatus(requestedStatus));
    }

    const whereSql = whereClauses.length ? ` WHERE ${whereClauses.join(' AND ')}` : '';

    // Đếm tổng số tour trong DB để tính số trang thực tế
    const [[countResult]] = await db.query(
      `SELECT COUNT(*) AS total FROM tours${whereSql}`,
      params,
    );
    const total = Number(countResult.total || 0);
    const totalPages = Math.max(1, Math.ceil(total / LIMIT));

    // Lấy tours của trang hiện tại
    const [rows] = await db.query(
      `${buildTourSelect(columnSet)}${whereSql} ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?`,
      [...params, LIMIT, offset],
    );

    return res.status(200).json({
      message: 'Lấy danh sách tour thành công.',
      pagination: {
        total,          // tổng số tours trong DB
        page,           // trang hiện tại
        limit: LIMIT,   // số tours mỗi trang
        totalPages,     // tổng số trang (tính từ DB)
        hasNextPage: page < totalPages,
        hasPrevPage: page > 1,
      },
      tours: rows.map(serializeTour),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể lấy danh sách tour lúc này.',
      error: error.message,
    });
  }
};

exports.getTourById = async (req, res) => {
  const tourId = Number(req.params.id);

  if (!Number.isInteger(tourId) || tourId <= 0) {
    return res.status(400).json({
      message: 'Mã tour không hợp lệ.',
    });
  }

  try {
    const columnSet = await getTourColumnSet();
    const tour = await getExistingTour(tourId, columnSet);

    if (!tour) {
      return res.status(404).json({
        message: 'Không tìm thấy tour.',
      });
    }

    return res.status(200).json({
      message: 'Lấy chi tiết tour thành công.',
      tour,
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể lấy chi tiết tour lúc này.',
      error: error.message,
    });
  }
};

exports.createTour = async (req, res) => {
  const { body: requestBody, error: payloadError } = getRequestPayload(req);
  const uploadedFiles = req.files || req.file;

  if (payloadError) {
    await removeUploadedFile(uploadedFiles);
    return res.status(400).json({
      message: payloadError,
    });
  }

  const { data, validationErrors } = buildWritePayload(requestBody, req.admin?.id || null);

  if (Object.keys(validationErrors).length > 0) {
    await removeUploadedFile(uploadedFiles);
    return res.status(400).json({
      message: 'Du lieu tour chua hop le.',
      errors: validationErrors,
    });
  }

  try {
    const columnSet = await getTourColumnSet();
    const { columns, values } = buildInsertParts(columnSet, data);
    const placeholders = columns.map(() => '?').join(', ');

    const [insertResult] = await db.query(
      `INSERT INTO tours (${columns.join(', ')}) VALUES (${placeholders})`,
      values,
    );

    const createdTour = await getExistingTour(insertResult.insertId, columnSet);

    return res.status(201).json({
      message: 'Tạo tour thành công.',
      tour: createdTour,
    });
  } catch (error) {
    await removeUploadedFile(uploadedFiles);
    return res.status(500).json({
      message: 'Không thể tạo tour lúc này.',
      error: error.message,
    });
  }
};

exports.updateTour = async (req, res) => {
  const tourId = Number(req.params.id);
  const uploadedFiles = req.files || req.file;

  if (!Number.isInteger(tourId) || tourId <= 0) {
    await removeUploadedFile(uploadedFiles);
    return res.status(400).json({
      message: 'Mã tour không hợp lệ.',
    });
  }

  const { body: requestBody, error: payloadError } = getRequestPayload(req);

  if (payloadError) {
    await removeUploadedFile(uploadedFiles);
    return res.status(400).json({
      message: payloadError,
    });
  }

  const { data, validationErrors } = buildWritePayload(requestBody, req.admin?.id || null);

  if (Object.keys(validationErrors).length > 0) {
    await removeUploadedFile(uploadedFiles);
    return res.status(400).json({
      message: 'Du lieu tour chua hop le.',
      errors: validationErrors,
    });
  }

  try {
    const columnSet = await getTourColumnSet();
    const existingTour = await getExistingTour(tourId, columnSet);

    if (!existingTour) {
      await removeUploadedFile(uploadedFiles);
      return res.status(404).json({
        message: 'Không tìm thấy tour để cập nhật.',
      });
    }

    const { assignments, values } = buildUpdateParts(columnSet, data);
    values.push(tourId);

    await db.query(`UPDATE tours SET ${assignments.join(', ')} WHERE id = ?`, values);

    const updatedTour = await getExistingTour(tourId, columnSet);

    return res.status(200).json({
      message: 'Cập nhật tour thành công.',
      tour: updatedTour,
    });
  } catch (error) {
    await removeUploadedFile(uploadedFiles);
    return res.status(500).json({
      message: 'Không thể cập nhật tour lúc này.',
      error: error.message,
    });
  }
};

exports.deleteTour = async (req, res) => {
  const tourId = Number(req.params.id);

  if (!Number.isInteger(tourId) || tourId <= 0) {
    return res.status(400).json({
      message: 'Mã tour không hợp lệ.',
    });
  }

  try {
    const [bookings] = await db.query(
      'SELECT COUNT(*) AS total FROM bookings WHERE tour_id = ?',
      [tourId],
    );

    if (Number(bookings[0]?.total || 0) > 0) {
      return res.status(409).json({
        message: 'Tour đã có booking, không thể xóa. Hãy chuyển trạng thái sang Closed để ẩn tour.',
      });
    }

    const [result] = await db.query('DELETE FROM tours WHERE id = ?', [tourId]);

    if (result.affectedRows === 0) {
      return res.status(404).json({
        message: 'Không tìm thấy tour để xóa.',
      });
    }

    return res.status(200).json({
      message: 'Xoa tour thành công.',
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể xóa tour lúc này.',
      error: error.message,
    });
  }
};
