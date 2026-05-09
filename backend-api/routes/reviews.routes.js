const express = require('express');

const {
  createReview,
  getAllReviews,
  getMyReviews,
  getReviewsByTourId,
  updateReviewStatus,
} = require('../controllers/reviews.controller');
const { authenticateToken } = require('../middleware/auth.middleware');
const { authenticateAdminToken } = require('../middleware/adminAuth.middleware');

const router = express.Router();

router.get('/tour/:tourId', getReviewsByTourId);
router.get('/my', authenticateToken, getMyReviews);
router.post('/', authenticateToken, createReview);
router.get('/', authenticateAdminToken, getAllReviews);
router.put('/:id/status', authenticateAdminToken, updateReviewStatus);

module.exports = router;
