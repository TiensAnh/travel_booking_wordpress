const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const db = require('../config/db');

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function buildAdminToken(admin) {
  return jwt.sign(
    {
      adminId: admin.id,
      scope: 'admin',
      role: admin.role,
    },
    process.env.JWT_SECRET,
    { expiresIn: '7d' },
  );
}

function sanitizeAdmin(admin) {
  return {
    id: admin.id,
    name: admin.name || admin.username,
    email: admin.email,
    role: admin.role,
  };
}

function normalizeRegisterBody(body = {}) {
  return {
    name: body.name?.trim() || body.username?.trim() || body.fullName?.trim() || '',
    email: body.email?.trim().toLowerCase() || '',
    password: body.password || '',
    confirmPassword: body.confirmPassword || body.confirmPass || '',
  };
}

function isBcryptHash(value = '') {
  return value.startsWith('$2a$') || value.startsWith('$2b$') || value.startsWith('$2y$');
}

exports.registerAdmin = async (req, res) => {
  const { name, email, password, confirmPassword } = normalizeRegisterBody(req.body);
  const validationErrors = {};

  if (!name) {
    validationErrors.name = 'Vui long nhap ten admin.';
  }

  if (!email) {
    validationErrors.email = 'Vui long nhap email admin.';
  } else if (!EMAIL_REGEX.test(email)) {
    validationErrors.email = 'Email admin khong dung dinh dang.';
  }

  if (!password) {
    validationErrors.password = 'Vui long nhap mat khau.';
  } else if (password.length < 6) {
    validationErrors.password = 'Mat khau phai co it nhat 6 ky tu.';
  }

  if (!confirmPassword) {
    validationErrors.confirmPassword = 'Vui long nhap lai mat khau.';
  } else if (password !== confirmPassword) {
    validationErrors.confirmPassword = 'Mat khau xac nhan chua khop.';
  }

  if (Object.keys(validationErrors).length > 0) {
    return res.status(400).json({
      message: 'Du lieu dang ky admin chua hop le.',
      errors: validationErrors,
    });
  }

  try {
    const [existingAdmins] = await db.query('SELECT email FROM admins WHERE email = ? LIMIT 1', [email]);

    existingAdmins.forEach((admin) => {
      if (admin.email === email) {
        validationErrors.email = 'Email admin da duoc su dung.';
      }
    });

    if (Object.keys(validationErrors).length > 0) {
      return res.status(409).json({
        message: 'Thong tin admin bi trung.',
        errors: validationErrors,
      });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    const [insertResult] = await db.query(
      'INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)',
      [name, email, hashedPassword, 'admin'],
    );

    const admin = {
      id: insertResult.insertId,
      username: name,
      email,
      role: 'admin',
    };

    return res.status(201).json({
      message: 'Dang ky admin thanh cong.',
      token: buildAdminToken(admin),
      admin: sanitizeAdmin(admin),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the tao tai khoan admin luc nay.',
      error: error.message,
    });
  }
};

exports.loginAdmin = async (req, res) => {
  const email = req.body.email?.trim().toLowerCase() || '';
  const password = req.body.password || '';

  if (!email || !password) {
    return res.status(400).json({
      message: 'Email va mat khau admin khong duoc de trong.',
    });
  }

  if (!EMAIL_REGEX.test(email)) {
    return res.status(400).json({
      message: 'Email admin khong dung dinh dang.',
    });
  }

  try {
    const [admins] = await db.query('SELECT * FROM admins WHERE email = ? LIMIT 1', [email]);

    if (admins.length === 0) {
      return res.status(401).json({
        message: 'Tai khoan admin hoac mat khau khong chinh xac.',
      });
    }

    const admin = admins[0];
    const passwordMatches = isBcryptHash(admin.password)
      ? await bcrypt.compare(password, admin.password)
      : password === admin.password;

    if (!passwordMatches) {
      return res.status(401).json({
        message: 'Tai khoan admin hoac mat khau khong chinh xac.',
      });
    }

    if (!isBcryptHash(admin.password)) {
      const upgradedPassword = await bcrypt.hash(password, 10);
      await db.query('UPDATE admins SET password = ? WHERE id = ?', [upgradedPassword, admin.id]);
    }

    return res.status(200).json({
      message: 'Dang nhap admin thanh cong.',
      token: buildAdminToken(admin),
      admin: sanitizeAdmin(admin),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the dang nhap admin luc nay.',
      error: error.message,
    });
  }
};

exports.getAdminMe = async (req, res) => {
  return res.status(200).json({
    message: 'Lay thong tin admin thanh cong.',
    admin: req.admin,
  });
};
