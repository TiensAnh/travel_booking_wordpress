const express = require('express');

const {
  loginAdmin,
  registerAdmin,
  getAdminMe,
} = require('../controllers/adminAuth.controller');
const { authenticateAdminToken } = require('../middleware/adminAuth.middleware');

const router = express.Router();

router.post('/login', loginAdmin);
router.post('/register', authenticateAdminToken, registerAdmin);
router.get('/me', authenticateAdminToken, getAdminMe);

module.exports = router;
