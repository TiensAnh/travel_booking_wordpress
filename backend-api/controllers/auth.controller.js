const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const db = require('../config/db');

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_REGEX = /^[0-9]{9,11}$/;

function buildToken(user) {
  return jwt.sign(
    {
      id: user.id,
      role: user.role,
    },
    process.env.JWT_SECRET,
    { expiresIn: '7d' },
  );
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

function normalizeRegisterBody(body = {}) {
  return {
    fullName: body.fullName?.trim() || body.fullname?.trim() || '',
    phone: body.phone?.trim() || '',
    email: body.email?.trim().toLowerCase() || '',
    password: body.password || '',
    confirmPassword: body.confirmPassword || body.confirmPass || '',
  };
}

function isBcryptHash(value = '') {
  return value.startsWith('$2a$') || value.startsWith('$2b$') || value.startsWith('$2y$');
}

exports.register = async (req, res) => {
  const { fullName, phone, email, password, confirmPassword } = normalizeRegisterBody(req.body);
  const validationErrors = {};

  if (!fullName) {
    validationErrors.fullName = 'Vui long nhap ho va ten.';
  }

  if (!phone) {
    validationErrors.phone = 'Vui long nhap so dien thoai.';
  } else if (!PHONE_REGEX.test(phone)) {
    validationErrors.phone = 'So dien thoai phai gom 9 den 11 chu so.';
  }

  if (!email) {
    validationErrors.email = 'Vui long nhap email.';
  } else if (!EMAIL_REGEX.test(email)) {
    validationErrors.email = 'Email khong dung dinh dang.';
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
      message: 'Du lieu dang ky chua hop le.',
      errors: validationErrors,
    });
  }

  try {
    const [existingUsers] = await db.query(
      'SELECT email, phone FROM users WHERE email = ? OR phone = ? LIMIT 2',
      [email, phone],
    );

    existingUsers.forEach((user) => {
      if (user.email === email) {
        validationErrors.email = 'Email da duoc su dung.';
      }

      if (user.phone === phone) {
        validationErrors.phone = 'So dien thoai da duoc su dung.';
      }
    });

    if (Object.keys(validationErrors).length > 0) {
      return res.status(409).json({
        message: 'Thong tin dang ky bi trung.',
        errors: validationErrors,
      });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    const [insertResult] = await db.query(
      'INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)',
      [fullName, email, phone, hashedPassword, 'USER'],
    );

    const user = {
      id: insertResult.insertId,
      name: fullName,
      email,
      phone,
      role: 'USER',
    };

    return res.status(201).json({
      message: 'Dang ky thanh cong.',
      token: buildToken(user),
      user: sanitizeUser(user),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the tao tai khoan luc nay.',
      error: error.message,
    });
  }
};

exports.getMe = async (req, res) => {
  return res.status(200).json({
    message: 'Lay thong tin nguoi dung thanh cong.',
    user: req.user,
  });
};

exports.login = async (req, res) => {
  const email = req.body.email?.trim().toLowerCase() || '';
  const password = req.body.password || '';

  if (!email || !password) {
    return res.status(400).json({
      message: 'Email va mat khau khong duoc de trong.',
    });
  }

  if (!EMAIL_REGEX.test(email)) {
    return res.status(400).json({
      message: 'Email khong dung dinh dang.',
    });
  }

  try {
    const [users] = await db.query('SELECT * FROM users WHERE email = ? LIMIT 1', [email]);

    if (users.length === 0) {
      return res.status(401).json({
        message: 'Email hoac mat khau khong chinh xac.',
      });
    }

    const user = users[0];
    const passwordMatches = isBcryptHash(user.password)
      ? await bcrypt.compare(password, user.password)
      : password === user.password;

    if (!passwordMatches) {
      return res.status(401).json({
        message: 'Email hoac mat khau khong chinh xac.',
      });
    }

    if (!isBcryptHash(user.password)) {
      const upgradedPassword = await bcrypt.hash(password, 10);
      await db.query('UPDATE users SET password = ? WHERE id = ?', [upgradedPassword, user.id]);
    }

    return res.status(200).json({
      message: 'Dang nhap thanh cong.',
      token: buildToken(user),
      user: sanitizeUser(user),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the dang nhap luc nay.',
      error: error.message,
    });
  }
};
