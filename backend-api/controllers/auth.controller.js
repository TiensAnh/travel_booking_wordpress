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
    validationErrors.fullName = 'Vui lòng nhập họ và tên.';
  }

  if (!phone) {
    validationErrors.phone = 'Vui lòng nhập số điện thoại.';
  } else if (!PHONE_REGEX.test(phone)) {
    validationErrors.phone = 'Số điện thoại phải gồm 9 đến 11 chữ số.';
  }

  if (!email) {
    validationErrors.email = 'Vui lòng nhập email.';
  } else if (!EMAIL_REGEX.test(email)) {
    validationErrors.email = 'Email không đúng định dạng.';
  }

  if (!password) {
    validationErrors.password = 'Vui lòng nhập mật khẩu.';
  } else if (password.length < 6) {
    validationErrors.password = 'Mật khẩu phải có ít nhất 6 ký tự.';
  }

  if (!confirmPassword) {
    validationErrors.confirmPassword = 'Vui lòng nhập lại mật khẩu.';
  } else if (password !== confirmPassword) {
    validationErrors.confirmPassword = 'Mật khẩu xác nhận chưa khớp.';
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
        validationErrors.phone = 'Số điện thoại da duoc su dung.';
      }
    });

    if (Object.keys(validationErrors).length > 0) {
      return res.status(409).json({
        message: 'Thông tin đăng ký bị trùng.',
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
      message: 'Đăng ký thành công.',
      token: buildToken(user),
      user: sanitizeUser(user),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể tạo tài khoản lúc này.',
      error: error.message,
    });
  }
};

exports.getMe = async (req, res) => {
  return res.status(200).json({
    message: 'Lấy thông tin người dùng thành công.',
    user: req.user,
  });
};

exports.login = async (req, res) => {
  const email = req.body.email?.trim().toLowerCase() || '';
  const password = req.body.password || '';

  if (!email || !password) {
    return res.status(400).json({
      message: 'Email và mật khẩu không được để trống.',
    });
  }

  if (!EMAIL_REGEX.test(email)) {
    return res.status(400).json({
      message: 'Email không đúng định dạng.',
    });
  }

  try {
    const [users] = await db.query('SELECT * FROM users WHERE email = ? LIMIT 1', [email]);

    if (users.length === 0) {
      return res.status(401).json({
        message: 'Email hoặc mật khẩu không chính xác.',
      });
    }

    const user = users[0];
    const passwordMatches = isBcryptHash(user.password)
      ? await bcrypt.compare(password, user.password)
      : password === user.password;

    if (!passwordMatches) {
      return res.status(401).json({
        message: 'Email hoặc mật khẩu không chính xác.',
      });
    }

    if (!isBcryptHash(user.password)) {
      const upgradedPassword = await bcrypt.hash(password, 10);
      await db.query('UPDATE users SET password = ? WHERE id = ?', [upgradedPassword, user.id]);
    }

    return res.status(200).json({
      message: 'Đăng nhập thành công.',
      token: buildToken(user),
      user: sanitizeUser(user),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Không thể đăng nhập lúc này.',
      error: error.message,
    });
  }
};
