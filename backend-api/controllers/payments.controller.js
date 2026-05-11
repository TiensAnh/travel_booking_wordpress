const db = require('../config/db');
const {
  BOOKING_STATUSES,
  BOOKING_PAYMENT_STATUSES,
  PAYMENT_RECORD_STATUSES,
  PAYMENT_PLANS,
  calculatePaymentPlanAmounts,
  buildSuccessfulPaymentState,
} = require('../services/bookingWorkflow.service');

const ALLOWED_METHODS = ['CASH', 'BANK_TRANSFER', 'MOMO', 'VNPAY', 'ZALOPAY', 'CARD'];

function normalizeMethod(method = '') {
  return String(method).trim().toUpperCase();
}

function buildPendingPaymentMessage(method) {
  if (method === 'BANK_TRANSFER') {
    return 'Đã tạo yêu cầu chuyển khoản. Vui lòng chuyển tiền và chờ admin xác nhận.';
  }

  if (method === 'CASH') {
    return 'Đã ghi nhận yêu cầu thanh toán tiền mặt. Booking sẽ được xác nhận sau khi admin kiểm tra.';
  }

  if (method === 'MOMO' || method === 'VNPAY' || method === 'ZALOPAY' || method === 'CARD') {
    return 'Đã tạo yêu cầu thanh toán. Hiện hệ thống đang chờ admin xác nhận giao dịch.';
  }

  return 'Đã tạo yêu cầu thanh toán thành công.';
}

// POST /api/payments
// User thanh toán cho booking
exports.createPayment = async (req, res) => {
  const userId = req.user.id;
  const { booking_id, method } = req.body;
  const normalizedMethod = normalizeMethod(method);

  if (!booking_id || !method) {
    return res.status(400).json({ message: 'Vui lòng cung cấp booking_id và method.' });
  }

  if (!ALLOWED_METHODS.includes(normalizedMethod)) {
    return res.status(400).json({
      message: `Phương thức thanh toán không hợp lệ. Cho phép: ${ALLOWED_METHODS.join(', ')}`,
    });
  }

  try {
    // Kiểm tra booking thuộc về user này
    const [bookings] = await db.query(
      'SELECT id, status, total_price FROM bookings WHERE id = ? AND user_id = ? LIMIT 1',
      [Number(booking_id), userId],
    );

    if (bookings.length === 0) {
      return res.status(404).json({ message: 'Không tìm thấy booking.' });
    }

    const booking = bookings[0];

    if (booking.status === BOOKING_STATUSES.CANCELLED || booking.status === BOOKING_STATUSES.REFUNDED) {
      return res.status(400).json({ message: 'Booking này đã bị hủy, không thể thanh toán.' });
    }

    if (booking.status === BOOKING_STATUSES.COMPLETED) {
      return res.status(400).json({ message: 'Booking nay da hoan thanh.' });
    }

    // Không cho tạo giao dịch mới nếu booking đã thanh toán xong
    const [successfulPayments] = await db.query(
      "SELECT id FROM payments WHERE booking_id = ? AND status = 'SUCCESS' LIMIT 1",
      [booking_id],
    );

    if (successfulPayments.length > 0) {
      return res.status(400).json({ message: 'Booking này đã được thanh toán thành công rồi.' });
    }

    const [pendingPayments] = await db.query(
      "SELECT id, method, status FROM payments WHERE booking_id = ? AND status = 'PENDING' ORDER BY id DESC LIMIT 1",
      [booking_id],
    );

    if (pendingPayments.length > 0) {
      return res.status(409).json({
        message: 'Booking này đã có yêu cầu thanh toán đang chờ xác nhận.',
        payment: pendingPayments[0],
      });
    }

    const paymentAmounts = calculatePaymentPlanAmounts(booking.total_price, PAYMENT_PLANS.FULL);

    const [result] = await db.query(
      `INSERT INTO payments
       (booking_id, amount, method, status, payment_plan, paid_amount, remaining_amount, paid_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        booking_id,
        paymentAmounts.paidAmount,
        normalizedMethod,
        PAYMENT_RECORD_STATUSES.PENDING,
        paymentAmounts.paymentPlan,
        paymentAmounts.paidAmount,
        paymentAmounts.remainingAmount,
        null,
      ],
    );

    return res.status(201).json({
      message: buildPendingPaymentMessage(normalizedMethod),
      payment: {
        id: result.insertId,
        booking_id: Number(booking_id),
        amount: paymentAmounts.paidAmount,
        method: normalizedMethod,
        status: PAYMENT_RECORD_STATUSES.PENDING,
        paid_at: null,
      },
    });
  } catch (error) {
    return res.status(500).json({ message: 'Không thể xử lý thanh toán lúc này.', error: error.message });
  }
};

// PUT /api/payments/:id/confirm
// Admin xác nhận đã nhận tiền cho giao dịch chờ xử lý
exports.confirmPayment = async (req, res) => {
  const paymentId = Number(req.params.id);

  if (!Number.isInteger(paymentId) || paymentId <= 0) {
    return res.status(400).json({ message: 'Mã giao dịch không hợp lệ.' });
  }

  try {
    const [rows] = await db.query(
      `SELECT p.id, p.booking_id, p.amount, p.method, p.status, p.payment_plan, b.total_price,
              COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status
       FROM payments p
       JOIN bookings b ON b.id = p.booking_id
       WHERE p.id = ? LIMIT 1`,
      [paymentId],
    );

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Không tìm thấy giao dịch.' });
    }

    const payment = rows[0];

    if (payment.status === PAYMENT_RECORD_STATUSES.SUCCESS) {
      return res.status(400).json({ message: 'Giao dịch này đã được xác nhận trước đó.' });
    }

    if (payment.status !== PAYMENT_RECORD_STATUSES.PENDING) {
      return res.status(400).json({ message: 'Chỉ có thể xác nhận giao dịch đang chờ xử lý.' });
    }

    if (payment.booking_status === BOOKING_STATUSES.CANCELLED) {
      return res.status(400).json({ message: 'Booking đã bị hủy, không thể xác nhận thanh toán.' });
    }

    const paidAt = new Date();
    const paymentState = buildSuccessfulPaymentState(payment.total_price || payment.amount, payment.payment_plan || PAYMENT_PLANS.FULL);

    await db.query(
      "UPDATE payments SET status = 'SUCCESS', paid_at = ?, paid_amount = ?, remaining_amount = ?, payment_plan = ? WHERE id = ?",
      [paidAt, paymentState.paidAmount, paymentState.remainingAmount, paymentState.paymentPlan, paymentId],
    );

    await db.query(
      `UPDATE bookings
       SET status = ?, booking_status = ?, payment_status = ?, payment_plan = ?, paid_amount = ?, remaining_amount = ?
       WHERE id = ? AND COALESCE(NULLIF(booking_status, ''), status) IN ('PENDING', 'PENDING_PAYMENT', 'PAYMENT_FAILED')`,
      [
        paymentState.legacyStatus,
        paymentState.bookingStatus,
        paymentState.paymentStatus,
        paymentState.paymentPlan,
        paymentState.paidAmount,
        paymentState.remainingAmount,
        payment.booking_id,
      ],
    );

    return res.status(200).json({
      message: 'Xac nhan thanh toan thành công.',
      payment: {
        id: payment.id,
        booking_id: payment.booking_id,
        amount: payment.amount,
        method: payment.method,
        status: PAYMENT_RECORD_STATUSES.SUCCESS,
        paid_at: paidAt,
      },
    });
  } catch (error) {
    return res.status(500).json({ message: 'Không thể xác nhận giao dịch lúc này.', error: error.message });
  }
};

// GET /api/payments/booking/:bookingId
// User/Admin xem lịch sử thanh toán của 1 booking
exports.getPaymentsByBookingId = async (req, res) => {
  const userId = req.user.id;
  const bookingId = Number(req.params.bookingId);

  if (!Number.isInteger(bookingId) || bookingId <= 0) {
    return res.status(400).json({ message: 'Mã booking không hợp lệ.' });
  }

  try {
    // Xác nhận booking thuộc về user
    const [bookings] = await db.query(
      'SELECT id, total_price, status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1',
      [bookingId, userId],
    );

    if (bookings.length === 0) {
      return res.status(404).json({ message: 'Không tìm thấy booking.' });
    }

    const [rows] = await db.query(
      'SELECT * FROM payments WHERE booking_id = ? ORDER BY paid_at DESC',
      [bookingId],
    );

    return res.status(200).json({
      message: 'Lấy lịch sử thanh toán thành công.',
      payments: rows,
    });
  } catch (error) {
    return res.status(500).json({ message: 'Không thể lấy lịch sử thanh toán.', error: error.message });
  }
};

// GET /api/payments
// Admin xem danh sách tất cả giao dịch
exports.getAllPayments = async (req, res) => {
  const { status, method } = req.query;

  try {
    const whereClauses = [];
    const params = [];

    if (status) {
      whereClauses.push('p.status = ?');
      params.push(String(status).toUpperCase());
    }

    if (method) {
      whereClauses.push('p.method = ?');
      params.push(String(method).toUpperCase());
    }

    const whereSql = whereClauses.length ? `WHERE ${whereClauses.join(' AND ')}` : '';

    const [rows] = await db.query(
      `SELECT p.*,
              b.user_id, b.total_price, b.status AS booking_status,
              u.name AS user_name, u.email AS user_email,
              t.title AS tour_title
       FROM payments p
       JOIN bookings b ON b.id = p.booking_id
       JOIN users u ON u.id = b.user_id
       JOIN tours t ON t.id = b.tour_id
       ${whereSql}
       ORDER BY (p.paid_at IS NULL) DESC, p.paid_at DESC, p.id DESC`,
      params,
    );

    return res.status(200).json({
      message: 'Lấy danh sách giao dịch thành công.',
      total: rows.length,
      payments: rows,
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể lấy danh sách giao dịch.',
      error: error.message,
    });
  }
};
