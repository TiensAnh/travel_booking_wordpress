const jwt = require('jsonwebtoken');

const db = require('../config/db');

function extractBearerToken(authorizationHeader = '') {
  if (!authorizationHeader.startsWith('Bearer ')) {
    return null;
  }

  return authorizationHeader.slice(7).trim() || null;
}

function sanitizeAdmin(admin) {
  return {
    id: admin.id,
    name: admin.name || admin.username,
    email: admin.email,
    role: admin.role,
  };
}

async function authenticateAdminToken(req, res, next) {
  const token = extractBearerToken(req.headers.authorization);

  if (!token) {
    return res.status(401).json({
      message: 'Ban chua dang nhap admin hoac token khong hop le.',
    });
  }

  if (!process.env.JWT_SECRET) {
    return res.status(500).json({
      message: 'Server chua duoc cau hinh JWT_SECRET.',
    });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);

    if (decoded.scope !== 'admin' || !decoded.adminId) {
      return res.status(401).json({
        message: 'Token admin khong hop le.',
      });
    }

    const [admins] = await db.query(
      'SELECT id, username, email, role FROM admins WHERE id = ? LIMIT 1',
      [decoded.adminId],
    );

    if (admins.length === 0) {
      return res.status(401).json({
        message: 'Tai khoan admin khong ton tai hoac da bi xoa.',
      });
    }

    req.admin = sanitizeAdmin(admins[0]);
    req.adminToken = token;

    return next();
  } catch (error) {
    return res.status(401).json({
      message: 'Token admin het han hoac khong hop le.',
    });
  }
}

module.exports = {
  authenticateAdminToken,
};
