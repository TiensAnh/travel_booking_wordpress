const express = require('express');

const {
  getAllUsers,
  getUserById,
  updateUserRole,
  deleteUser,
} = require('../controllers/users.controller');
const { authenticateAdminToken } = require('../middleware/adminAuth.middleware');

const router = express.Router();

// Tất cả route users đều cần Admin token
router.get('/', authenticateAdminToken, getAllUsers);
router.get('/:id', authenticateAdminToken, getUserById);
router.put('/:id/role', authenticateAdminToken, updateUserRole);
router.delete('/:id', authenticateAdminToken, deleteUser);

module.exports = router;
