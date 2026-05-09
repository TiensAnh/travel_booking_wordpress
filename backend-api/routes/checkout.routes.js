const express = require('express');

const {
  createCheckoutSession,
  getCheckoutTransaction,
  handleGatewayCallback,
  previewQuote,
  renderMockGateway,
} = require('../controllers/checkout.controller');
const { authenticateToken } = require('../middleware/auth.middleware');

const router = express.Router();

router.post('/quote', previewQuote);
router.post('/session', authenticateToken, createCheckoutSession);
router.get('/gateway/:transactionCode', renderMockGateway);
router.post('/callback/:transactionCode', handleGatewayCallback);
router.get('/transaction/:transactionCode', authenticateToken, getCheckoutTransaction);

module.exports = router;
