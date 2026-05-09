const db = require('../config/db');
const {
  BOOKING_STATUSES,
  BOOKING_PAYMENT_STATUSES,
} = require('../services/bookingWorkflow.service');

function resolveBookingStatus(booking) {
  return String(booking.booking_status || booking.status || '').toUpperCase();
}

function addReviewState(booking) {
  const hasReview = Boolean(booking.review_id);
  const resolvedStatus = resolveBookingStatus(booking);

  return {
    ...booking,
    review_id: booking.review_id || null,
    status: resolvedStatus,
    has_review: hasReview,
    can_review: resolvedStatus === BOOKING_STATUSES.COMPLETED && !hasReview,
  };
}

// =====================================================
// USER APIS
// =====================================================

// POST /api/bookings
// User tạo đơn đặt tour mới
exports.createBooking = async (req, res) => {
  const userId = req.user.id;
  const { tour_id, travel_date, number_of_people } = req.body;

  if (!tour_id || !travel_date || !number_of_people) {
    return res.status(400).json({
      message: 'Vui long cung cap tour_id, travel_date va number_of_people.',
    });
  }

  const numPeople = Number(number_of_people);
  if (!Number.isInteger(numPeople) || numPeople <= 0) {
    return res.status(400).json({ message: 'So nguoi phai la so nguyen duong.' });
  }

  try {
    const [tours] = await db.query(
      'SELECT id, price, max_people, status, title FROM tours WHERE id = ? LIMIT 1',
      [Number(tour_id)],
    );

    if (tours.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay tour.' });
    }

    const tour = tours[0];

    if (tour.status !== 'Active') {
      return res.status(400).json({ message: 'Tour nay hien khong nhan dat.' });
    }

    if (tour.max_people && numPeople > tour.max_people) {
      return res.status(400).json({
        message: `So nguoi vuot qua gioi han cua tour (toi da ${tour.max_people} nguoi).`,
      });
    }

    const total_price = Number(tour.price) * numPeople;

    const [result] = await db.query(
      `INSERT INTO bookings
       (user_id, tour_id, travel_date, number_of_people, total_price, status, booking_status, payment_status, payment_plan, paid_amount, remaining_amount)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        userId,
        tour.id,
        travel_date,
        numPeople,
        total_price,
        BOOKING_STATUSES.PENDING_PAYMENT,
        BOOKING_STATUSES.PENDING_PAYMENT,
        BOOKING_PAYMENT_STATUSES.PENDING,
        'FULL',
        0,
        total_price,
      ],
    );

    const [rows] = await db.query(
      `SELECT b.*, t.title AS tour_title, t.location, t.image_url, t.duration
       FROM bookings b
       JOIN tours t ON t.id = b.tour_id
       WHERE b.id = ? LIMIT 1`,
      [result.insertId],
    );

    return res.status(201).json({
      message: 'Dat tour thanh cong.',
      booking: rows[0],
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the dat tour luc nay.', error: error.message });
  }
};

// GET /api/bookings/my
// User xem danh sách booking của bản thân
exports.getMyBookings = async (req, res) => {
  const userId = req.user.id;

  try {
    const [rows] = await db.query(
      `SELECT b.*, COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
              t.title AS tour_title, t.location, t.image_url, t.duration, t.duration_text,
              r.id AS review_id,
              (SELECT p.id FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_id,
              (SELECT p.amount FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_amount,
              (SELECT p.method FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_method,
              (SELECT p.status FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_status,
              (SELECT p.paid_at FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_paid_at
       FROM bookings b
       JOIN tours t ON t.id = b.tour_id
       LEFT JOIN reviews r ON r.booking_id = b.id
       WHERE b.user_id = ?
       ORDER BY b.created_at DESC`,
      [userId],
    );

    return res.status(200).json({
      message: 'Lay danh sach booking thanh cong.',
      bookings: rows.map((booking) => addReviewState({
        ...booking,
        payment: booking.payment_id
          ? {
            id: booking.payment_id,
            amount: booking.payment_amount,
            method: booking.payment_method,
            status: booking.payment_status,
            paid_at: booking.payment_paid_at,
          }
          : null,
      })),
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay danh sach booking.', error: error.message });
  }
};

// GET /api/bookings/my/:id
// User xem chi tiết booking của mình
exports.getMyBookingById = async (req, res) => {
  const userId = req.user.id;
  const bookingId = Number(req.params.id);

  if (!Number.isInteger(bookingId) || bookingId <= 0) {
    return res.status(400).json({ message: 'Ma booking khong hop le.' });
  }

  try {
    const [rows] = await db.query(
      `SELECT b.*, COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
              t.title AS tour_title, t.location, t.image_url, t.duration, t.duration_text, t.transport, t.meeting_point,
              r.id AS review_id
       FROM bookings b
       JOIN tours t ON t.id = b.tour_id
       LEFT JOIN reviews r ON r.booking_id = b.id
       WHERE b.id = ? AND b.user_id = ? LIMIT 1`,
      [bookingId, userId],
    );

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay booking.' });
    }

    // Lấy thêm payment info
    const [payments] = await db.query(
      'SELECT * FROM payments WHERE booking_id = ? ORDER BY paid_at DESC LIMIT 1',
      [bookingId],
    );

    return res.status(200).json({
      message: 'Lay chi tiet booking thanh cong.',
      booking: { ...addReviewState(rows[0]), payment: payments[0] || null },
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay chi tiet booking.', error: error.message });
  }
};

// PUT /api/bookings/my/:id/cancel
// User tự huỷ booking của mình (chỉ được hủy khi status là PENDING)
exports.cancelMyBooking = async (req, res) => {
  const userId = req.user.id;
  const bookingId = Number(req.params.id);

  if (!Number.isInteger(bookingId) || bookingId <= 0) {
    return res.status(400).json({ message: 'Ma booking khong hop le.' });
  }

  try {
    const [rows] = await db.query(
      'SELECT id, COALESCE(NULLIF(booking_status, \'\'), status) AS booking_status, payment_status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1',
      [bookingId, userId],
    );

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay booking.' });
    }

    const booking = rows[0];

    if (booking.booking_status === BOOKING_STATUSES.CANCELLED) {
      return res.status(400).json({ message: 'Booking nay da duoc huy truoc do.' });
    }

    if (booking.booking_status === BOOKING_STATUSES.COMPLETED) {
      return res.status(400).json({ message: 'Booking da hoan thanh, khong the huy.' });
    }

    if (booking.booking_status === BOOKING_STATUSES.CONFIRMED) {
      return res.status(400).json({ message: 'Booking da duoc xac nhan, lien he admin de huy.' });
    }

    await db.query(
      'UPDATE bookings SET status = ?, booking_status = ? WHERE id = ?',
      [BOOKING_STATUSES.CANCELLED, BOOKING_STATUSES.CANCELLED, bookingId],
    );

    return res.status(200).json({ message: 'Huy booking thanh cong.' });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the huy booking luc nay.', error: error.message });
  }
};

// =====================================================
// ADMIN APIS
// =====================================================

// GET /api/bookings
// Admin lấy toàn bộ danh sách booking có thể filter theo status
exports.getAllBookings = async (req, res) => {
  const { status, tour_id } = req.query;

  try {
    const whereClauses = [];
    const params = [];

    if (status) {
      whereClauses.push('COALESCE(NULLIF(b.booking_status, \'\'), b.status) = ?');
      params.push(String(status).toUpperCase());
    }

    if (tour_id) {
      whereClauses.push('b.tour_id = ?');
      params.push(Number(tour_id));
    }

    const whereSql = whereClauses.length ? `WHERE ${whereClauses.join(' AND ')}` : '';

    const [rows] = await db.query(
      `SELECT b.*, COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
              u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
              t.title AS tour_title, t.location, t.image_url
       FROM bookings b
       JOIN users u ON u.id = b.user_id
       JOIN tours t ON t.id = b.tour_id
       ${whereSql}
       ORDER BY b.created_at DESC`,
      params,
    );

    return res.status(200).json({
      message: 'Lay danh sach booking thanh cong.',
      total: rows.length,
      bookings: rows,
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay danh sach booking.', error: error.message });
  }
};

// GET /api/bookings/:id
// Admin xem chi tiết 1 booking
exports.getBookingById = async (req, res) => {
  const bookingId = Number(req.params.id);

  if (!Number.isInteger(bookingId) || bookingId <= 0) {
    return res.status(400).json({ message: 'Ma booking khong hop le.' });
  }

  try {
    const [rows] = await db.query(
      `SELECT b.*, COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
              u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
              t.title AS tour_title, t.location, t.image_url, t.duration, t.transport
       FROM bookings b
       JOIN users u ON u.id = b.user_id
       JOIN tours t ON t.id = b.tour_id
       WHERE b.id = ? LIMIT 1`,
      [bookingId],
    );

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay booking.' });
    }

    // Lấy danh sách hành khách
    const [customers] = await db.query(
      'SELECT * FROM booking_customers WHERE booking_id = ?',
      [bookingId],
    );

    // Lấy payment
    const [payments] = await db.query(
      'SELECT * FROM payments WHERE booking_id = ? ORDER BY paid_at DESC',
      [bookingId],
    );

    return res.status(200).json({
      message: 'Lay chi tiet booking thanh cong.',
      booking: {
        ...rows[0],
        customers,
        payments,
      },
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay chi tiet booking.', error: error.message });
  }
};

// PUT /api/bookings/:id/status
// Admin cập nhật trạng thái booking
exports.updateBookingStatus = async (req, res) => {
  const bookingId = Number(req.params.id);
  const { status } = req.body;

  if (!Number.isInteger(bookingId) || bookingId <= 0) {
    return res.status(400).json({ message: 'Ma booking khong hop le.' });
  }

  const allowedStatuses = [
    BOOKING_STATUSES.PENDING_PAYMENT,
    BOOKING_STATUSES.PAYMENT_FAILED,
    BOOKING_STATUSES.PAID,
    BOOKING_STATUSES.PENDING_CONFIRMATION,
    BOOKING_STATUSES.CONFIRMED,
    BOOKING_STATUSES.CANCELLED,
    BOOKING_STATUSES.COMPLETED,
    BOOKING_STATUSES.REFUNDED,
  ];
  if (!status || !allowedStatuses.includes(String(status).toUpperCase())) {
    return res.status(400).json({
      message: `Trang thai khong hop le. Cho phep: ${allowedStatuses.join(', ')}`,
    });
  }

  try {
    const [rows] = await db.query('SELECT id FROM bookings WHERE id = ? LIMIT 1', [bookingId]);

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay booking.' });
    }

    await db.query(
      'UPDATE bookings SET status = ?, booking_status = ? WHERE id = ?',
      [status.toUpperCase(), status.toUpperCase(), bookingId],
    );

    return res.status(200).json({ message: 'Cap nhat trang thai booking thanh cong.' });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the cap nhat trang thai.', error: error.message });
  }
};
