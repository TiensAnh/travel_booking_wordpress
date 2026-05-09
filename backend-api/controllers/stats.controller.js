const db = require('../config/db');

// GET /api/stats/dashboard
// Admin lấy thống kê tổng quan cho dashboard
exports.getDashboard = async (req, res) => {
  try {
    // Tổng số tour đang Active
    const [[tourStats]] = await db.query(
      "SELECT COUNT(*) AS total_tours, SUM(status = 'Active') AS active_tours FROM tours",
    );

    // Tổng số user
    const [[userStats]] = await db.query(
      "SELECT COUNT(*) AS total_users FROM users WHERE role = 'USER'",
    );

    // Tổng booking và phân loại theo trạng thái
    const [[bookingStats]] = await db.query(
      `SELECT 
        COUNT(*) AS total_bookings,
        SUM(COALESCE(NULLIF(booking_status, ''), status) IN ('PENDING_PAYMENT', 'PAYMENT_FAILED', 'PENDING_CONFIRMATION')) AS pending_bookings,
        SUM(COALESCE(NULLIF(booking_status, ''), status) = 'CONFIRMED') AS confirmed_bookings,
        SUM(COALESCE(NULLIF(booking_status, ''), status) = 'CANCELLED') AS cancelled_bookings,
        SUM(COALESCE(NULLIF(booking_status, ''), status) = 'COMPLETED') AS completed_bookings
       FROM bookings`,
    );

    // Tổng doanh thu (chỉ tính booking CONFIRMED hoặc COMPLETED)
    const [[revenueStats]] = await db.query(
      "SELECT COALESCE(SUM(amount), 0) AS total_revenue FROM payments WHERE status = 'SUCCESS'",
    );

    // Doanh thu 6 tháng gần nhất
    const [monthlyRevenue] = await db.query(
      `SELECT 
        DATE_FORMAT(paid_at, '%Y-%m') AS month,
        SUM(amount) AS revenue,
        COUNT(*) AS payment_count
       FROM payments
       WHERE status = 'SUCCESS' AND paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
       GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
       ORDER BY month ASC`,
    );

    // Top 5 tour được đặt nhiều nhất
    const [topTours] = await db.query(
      `SELECT t.id, t.title, t.location, t.image_url,
              COUNT(b.id) AS total_bookings,
              SUM(b.total_price) AS total_revenue
       FROM tours t
       LEFT JOIN bookings b ON b.tour_id = t.id
       GROUP BY t.id
       ORDER BY total_bookings DESC
       LIMIT 5`,
    );

    // 5 booking mới nhất
    const [recentBookings] = await db.query(
      `SELECT b.id, b.travel_date, b.number_of_people, b.total_price,
              COALESCE(NULLIF(b.booking_status, ''), b.status) AS status, b.created_at,
              u.name AS user_name,
              t.title AS tour_title
       FROM bookings b
       JOIN users u ON u.id = b.user_id
       JOIN tours t ON t.id = b.tour_id
       ORDER BY b.created_at DESC
       LIMIT 5`,
    );

    return res.status(200).json({
      message: 'Lay thong ke dashboard thanh cong.',
      stats: {
        tours: {
          total: Number(tourStats.total_tours || 0),
          active: Number(tourStats.active_tours || 0),
        },
        users: {
          total: Number(userStats.total_users || 0),
        },
        bookings: {
          total: Number(bookingStats.total_bookings || 0),
          pending: Number(bookingStats.pending_bookings || 0),
          confirmed: Number(bookingStats.confirmed_bookings || 0),
          cancelled: Number(bookingStats.cancelled_bookings || 0),
          completed: Number(bookingStats.completed_bookings || 0),
        },
        revenue: {
          total: Number(revenueStats.total_revenue || 0),
        },
      },
      monthlyRevenue,
      topTours,
      recentBookings,
    });
  } catch (error) {
    return res.status(500).json({ message: 'Khong the lay thong ke luc nay.', error: error.message });
  }
};
