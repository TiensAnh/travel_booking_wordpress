const express = require('express');

const { login, register, getMe } = require('../controllers/auth.controller');
const { authenticateToken } = require('../middleware/auth.middleware');

const router = express.Router();

router.post('/login', login);
router.post('/register', register);
router.get('/me', authenticateToken, getMe);

module.exports = router;
