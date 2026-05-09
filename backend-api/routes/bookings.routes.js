const express = require('express');

const {
  createBooking,
  getMyBookings,
  getMyBookingById,
  cancelMyBooking,
  getAllBookings,
  getBookingById,
  updateBookingStatus,
} = require('../controllers/bookings.controller');
const { authenticateToken } = require('../middleware/auth.middleware');
const { authenticateAdminToken } = require('../middleware/adminAuth.middleware');

const router = express.Router();

// ---- USER ROUTES ----
router.post('/', authenticateToken, createBooking);
router.get('/my', authenticateToken, getMyBookings);
router.get('/my/:id', authenticateToken, getMyBookingById);
router.put('/my/:id/cancel', authenticateToken, cancelMyBooking);

// ---- ADMIN ROUTES ----
router.get('/', authenticateAdminToken, getAllBookings);
router.get('/:id', authenticateAdminToken, getBookingById);
router.put('/:id/status', authenticateAdminToken, updateBookingStatus);

module.exports = router;
