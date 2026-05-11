const db = require('../config/db');

function normalizeReviewStatus(value = '') {
  return String(value).trim().toUpperCase() === 'HIDDEN' ? 'HIDDEN' : 'VISIBLE';
}

function serializeReview(row) {
  return {
    id: row.id,
    bookingId: row.booking_id,
    userId: row.user_id,
    userName: row.user_name || '',
    userEmail: row.user_email || '',
    tourId: row.tour_id,
    tourTitle: row.tour_title || '',
    rating: Number(row.rating || 0),
    comment: row.comment || '',
    status: normalizeReviewStatus(row.status),
    createdAt: row.created_at,
    updatedAt: row.updated_at,
    travelDate: row.travel_date || null,
  };
}

async function getReviewById(reviewId) {
  const [rows] = await db.query(
    `SELECT r.*,
            u.name AS user_name,
            u.email AS user_email,
            t.title AS tour_title,
            b.travel_date
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     JOIN tours t ON t.id = r.tour_id
     JOIN bookings b ON b.id = r.booking_id
     WHERE r.id = ?
     LIMIT 1`,
    [reviewId],
  );

  return rows[0] ? serializeReview(rows[0]) : null;
}

exports.createReview = async (req, res) => {
  const userId = req.user.id;
  const bookingId = Number(req.body.booking_id);
  const rating = Number(req.body.rating);
  const comment = String(req.body.comment || '').trim();

  if (!Number.isInteger(bookingId) || bookingId <= 0) {
    return res.status(400).json({ message: 'Booking không hợp lệ.' });
  }

  if (!Number.isInteger(rating) || rating < 1 || rating > 5) {
    return res.status(400).json({ message: 'Số sao phải từ 1 đến 5.' });
  }

  if (!comment) {
    return res.status(400).json({ message: 'Vui lòng nhập nội dung đánh giá.' });
  }

  try {
    const [bookings] = await db.query(
      `SELECT id, user_id, tour_id, COALESCE(NULLIF(booking_status, ''), status) AS booking_status
       FROM bookings
       WHERE id = ? AND user_id = ?
       LIMIT 1`,
      [bookingId, userId],
    );

    if (bookings.length === 0) {
      return res.status(404).json({ message: 'Không tìm thấy booking để đánh giá.' });
    }

    if (String(bookings[0].booking_status || '').toUpperCase() !== 'COMPLETED') {
      return res.status(400).json({ message: 'Chỉ booking đã hoàn thành mới được đánh giá.' });
    }

    const [existingReviews] = await db.query(
      'SELECT id FROM reviews WHERE booking_id = ? LIMIT 1',
      [bookingId],
    );

    if (existingReviews.length > 0) {
      return res.status(400).json({ message: 'Booking này đã được đánh giá trước đó.' });
    }

    const [insertResult] = await db.query(
      `INSERT INTO reviews (booking_id, user_id, tour_id, rating, comment, status)
       VALUES (?, ?, ?, ?, ?, 'VISIBLE')`,
      [bookingId, userId, bookings[0].tour_id, rating, comment],
    );

    const review = await getReviewById(insertResult.insertId);

    return res.status(201).json({
      message: 'Gửi đánh giá thành công.',
      review,
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể gửi đánh giá lúc này.',
      error: error.message,
    });
  }
};

exports.getMyReviews = async (req, res) => {
  try {
    const [rows] = await db.query(
      `SELECT r.*,
              u.name AS user_name,
              u.email AS user_email,
              t.title AS tour_title,
              b.travel_date
       FROM reviews r
       JOIN users u ON u.id = r.user_id
       JOIN tours t ON t.id = r.tour_id
       JOIN bookings b ON b.id = r.booking_id
       WHERE r.user_id = ?
       ORDER BY r.created_at DESC, r.id DESC`,
      [req.user.id],
    );

    return res.status(200).json({
      message: 'Lấy danh sách đánh giá thành công.',
      reviews: rows.map(serializeReview),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể lấy danh sách đánh giá.',
      error: error.message,
    });
  }
};

exports.getReviewsByTourId = async (req, res) => {
  const tourId = Number(req.params.tourId);

  if (!Number.isInteger(tourId) || tourId <= 0) {
    return res.status(400).json({ message: 'Mã tour không hợp lệ.' });
  }

  try {
    const [rows] = await db.query(
      `SELECT r.*,
              u.name AS user_name,
              u.email AS user_email,
              t.title AS tour_title,
              b.travel_date
       FROM reviews r
       JOIN users u ON u.id = r.user_id
       JOIN tours t ON t.id = r.tour_id
       JOIN bookings b ON b.id = r.booking_id
       WHERE r.tour_id = ? AND r.status = 'VISIBLE'
       ORDER BY r.created_at DESC, r.id DESC`,
      [tourId],
    );

    return res.status(200).json({
      message: 'Lấy danh sách đánh giá tour thành công.',
      reviews: rows.map(serializeReview),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể lấy đánh giá của tour.',
      error: error.message,
    });
  }
};

exports.getAllReviews = async (req, res) => {
  const whereClauses = [];
  const params = [];

  if (req.query.status && req.query.status !== 'ALL') {
    whereClauses.push('r.status = ?');
    params.push(normalizeReviewStatus(req.query.status));
  }

  if (req.query.search) {
    whereClauses.push('(u.name LIKE ? OR u.email LIKE ? OR t.title LIKE ? OR r.comment LIKE ?)');
    params.push(
      `%${req.query.search}%`,
      `%${req.query.search}%`,
      `%${req.query.search}%`,
      `%${req.query.search}%`,
    );
  }

  const whereSql = whereClauses.length ? `WHERE ${whereClauses.join(' AND ')}` : '';

  try {
    const [rows] = await db.query(
      `SELECT r.*,
              u.name AS user_name,
              u.email AS user_email,
              t.title AS tour_title,
              b.travel_date
       FROM reviews r
       JOIN users u ON u.id = r.user_id
       JOIN tours t ON t.id = r.tour_id
       JOIN bookings b ON b.id = r.booking_id
       ${whereSql}
       ORDER BY r.created_at DESC, r.id DESC`,
      params,
    );

    return res.status(200).json({
      message: 'Lấy danh sách đánh giá thành công.',
      reviews: rows.map(serializeReview),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể lấy danh sách đánh giá.',
      error: error.message,
    });
  }
};

exports.updateReviewStatus = async (req, res) => {
  const reviewId = Number(req.params.id);
  const status = normalizeReviewStatus(req.body.status);

  if (!Number.isInteger(reviewId) || reviewId <= 0) {
    return res.status(400).json({ message: 'Mã đánh giá không hợp lệ.' });
  }

  try {
    const [result] = await db.query(
      'UPDATE reviews SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
      [status, reviewId],
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({ message: 'Không tìm thấy đánh giá.' });
    }

    return res.status(200).json({
      message: 'Cập nhật trạng thái đánh giá thành công.',
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể cập nhật trạng thái đánh giá.',
      error: error.message,
    });
  }
};
