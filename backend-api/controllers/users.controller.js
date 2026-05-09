const db = require('../config/db');

// GET /api/users
// Admin lấy danh sách tất cả user (có thể search theo tên, email)
exports.getAllUsers = async (req, res) => {
  const { search } = req.query;

  try {
    const whereClauses = [];
    const params = [];

    if (search) {
      whereClauses.push('(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)');
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }

    const whereSql = whereClauses.length ? `WHERE ${whereClauses.join(' AND ')}` : '';

    const [rows] = await db.query(
      `SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at,
              COUNT(b.id) AS total_bookings
       FROM users u
       LEFT JOIN bookings b ON b.user_id = u.id
       ${whereSql}
       GROUP BY u.id
       ORDER BY u.created_at DESC`,
      params,
    );

    return res.status(200).json({
      message: 'Lay danh sach nguoi dung thanh cong.',
      total: rows.length,
      users: rows,
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay danh sach nguoi dung.', error: error.message });
  }
};

// GET /api/users/:id
// Admin xem chi tiết 1 user
exports.getUserById = async (req, res) => {
  const userId = Number(req.params.id);

  if (!Number.isInteger(userId) || userId <= 0) {
    return res.status(400).json({ message: 'Ma nguoi dung khong hop le.' });
  }

  try {
    const [rows] = await db.query(
      'SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1',
      [userId],
    );

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay nguoi dung.' });
    }

    // Lấy lịch sử booking của user này
    const [bookings] = await db.query(
      `SELECT b.id, b.travel_date, b.number_of_people, b.total_price,
              COALESCE(NULLIF(b.booking_status, ''), b.status) AS status, b.created_at,
              t.title AS tour_title
       FROM bookings b
       JOIN tours t ON t.id = b.tour_id
       WHERE b.user_id = ?
       ORDER BY b.created_at DESC`,
      [userId],
    );

    return res.status(200).json({
      message: 'Lay chi tiet nguoi dung thanh cong.',
      user: { ...rows[0], bookings },
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay chi tiet nguoi dung.', error: error.message });
  }
};

// PUT /api/users/:id/role
// Admin cập nhật role của user (USER / STAFF)
exports.updateUserRole = async (req, res) => {
  const userId = Number(req.params.id);
  const { role } = req.body;

  if (!Number.isInteger(userId) || userId <= 0) {
    return res.status(400).json({ message: 'Ma nguoi dung khong hop le.' });
  }

  const allowedRoles = ['USER', 'STAFF'];
  if (!role || !allowedRoles.includes(String(role).toUpperCase())) {
    return res.status(400).json({
      message: `Role khong hop le. Cho phep: ${allowedRoles.join(', ')}`,
    });
  }

  try {
    const [rows] = await db.query('SELECT id FROM users WHERE id = ? LIMIT 1', [userId]);

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay nguoi dung.' });
    }

    await db.query('UPDATE users SET role = ? WHERE id = ?', [role.toUpperCase(), userId]);

    return res.status(200).json({ message: 'Cap nhat role nguoi dung thanh cong.' });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the cap nhat role.', error: error.message });
  }
};

// DELETE /api/users/:id
// Admin xoá tài khoản user
exports.deleteUser = async (req, res) => {
  const userId = Number(req.params.id);

  if (!Number.isInteger(userId) || userId <= 0) {
    return res.status(400).json({ message: 'Ma nguoi dung khong hop le.' });
  }

  try {
    const [rows] = await db.query('SELECT id FROM users WHERE id = ? LIMIT 1', [userId]);

    if (rows.length === 0) {
      return res.status(404).json({ message: 'Khong tim thay nguoi dung.' });
    }

    // Kiểm tra user có booking đang active không
    const [activeBookings] = await db.query(
      "SELECT COUNT(*) AS total FROM bookings WHERE user_id = ? AND COALESCE(NULLIF(booking_status, ''), status) IN ('PENDING_PAYMENT', 'PAYMENT_FAILED', 'PENDING_CONFIRMATION', 'CONFIRMED')",
      [userId],
    );

    if (Number(activeBookings[0]?.total || 0) > 0) {
      return res.status(409).json({
        message: 'Nguoi dung con booking dang hoat dong, khong the xoa.',
      });
    }

    await db.query('DELETE FROM users WHERE id = ?', [userId]);

    return res.status(200).json({ message: 'Xoa nguoi dung thanh cong.' });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the xoa nguoi dung.', error: error.message });
  }
};
