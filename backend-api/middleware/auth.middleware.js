const jwt = require('jsonwebtoken');

const db = require('../config/db');

function extractBearerToken(authorizationHeader = '') {
  if (!authorizationHeader.startsWith('Bearer ')) {
    return null;
  }

  return authorizationHeader.slice(7).trim() || null;
}

function sanitizeUser(user) {
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone,
    role: user.role,
  };
}

async function authenticateToken(req, res, next) {
  const token = extractBearerToken(req.headers.authorization);

  if (!token) {
    return res.status(401).json({
      message: 'Ban chua dang nhap hoac token khong hop le.',
    });
  }

  if (!process.env.JWT_SECRET) {
    return res.status(500).json({
      message: 'Server chua duoc cau hinh JWT_SECRET.',
    });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const [users] = await db.query(
      'SELECT id, name, email, phone, role FROM users WHERE id = ? LIMIT 1',
      [decoded.id],
    );

    if (users.length === 0) {
      return res.status(401).json({
        message: 'Tai khoan khong ton tai hoac da bi xoa.',
      });
    }

    req.user = sanitizeUser(users[0]);
    req.token = token;

    return next();
  } catch (error) {
    return res.status(401).json({
      message: 'Token het han hoac khong hop le.',
    });
  }
}

function authorizeRoles(...allowedRoles) {
  const normalizedRoles = allowedRoles.map((role) => String(role).toUpperCase());

  return (req, res, next) => {
    const currentRole = String(req.user?.role || '').toUpperCase();

    if (!currentRole) {
      return res.status(401).json({
        message: 'Khong xac dinh duoc thong tin nguoi dung.',
      });
    }

    if (!normalizedRoles.includes(currentRole)) {
      return res.status(403).json({
        message: 'Ban khong co quyen truy cap tai nguyen nay.',
      });
    }

    return next();
  };
}

const requireAdmin = authorizeRoles('ADMIN');

module.exports = {
  authenticateToken,
  authorizeRoles,
  requireAdmin,
};
