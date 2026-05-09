const express = require('express');

const { getDashboard } = require('../controllers/stats.controller');
const { authenticateAdminToken } = require('../middleware/adminAuth.middleware');

const router = express.Router();

router.get('/dashboard', authenticateAdminToken, getDashboard);

module.exports = router;
