const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const db = require('../config/db');
const {
  BOOKING_STATUSES,
  BOOKING_PAYMENT_STATUSES,
  PAYMENT_RECORD_STATUSES,
  PAYMENT_PLANS,
  roundCurrency,
  getDepositRate,
  normalizePaymentPlan,
  calculatePaymentPlanAmounts,
  buildSuccessfulPaymentState,
  buildFailedPaymentState,
} = require('../services/bookingWorkflow.service');

const PAYMENT_METHODS = {
  VNPAY: {
    label: 'VNPay',
    tone: '#0f7cff',
    note: 'Thanh toan nhanh qua cong VNPay.',
  },
  MOMO: {
    label: 'MoMo',
    tone: '#b0006d',
    note: 'Vi dien tu pho bien cho thanh toan tren mobile.',
  },
  ZALOPAY: {
    label: 'ZaloPay',
    tone: '#0068ff',
    note: 'Thanh toan qua he sinh thai ZaloPay.',
  },
  BANK_TRANSFER: {
    label: 'Chuyen khoan ngan hang',
    tone: '#1a7f64',
    note: 'Xac nhan chuyen khoan voi tai khoan doanh nghiep ADN Travel.',
  },
  CARD: {
    label: 'The quoc te',
    tone: '#6a42ff',
    note: 'Visa, Mastercard, JCB, AmEx.',
  },
};

const TAX_RATE = 0.08;
const SERVICE_FEE = 39000;
const CHILD_PRICE_RATIO = 0.7;
const CALLBACK_SECRET = process.env.CHECKOUT_SIGNATURE_SECRET || process.env.JWT_SECRET || 'checkout-secret';
const TERMINAL_TRANSACTION_STATUSES = new Set([
  PAYMENT_RECORD_STATUSES.FAILED,
  PAYMENT_RECORD_STATUSES.CANCELLED,
  PAYMENT_RECORD_STATUSES.EXPIRED,
]);
const DEFAULT_FRONTEND_URL = 'http://localhost/travel_booking/';
const DEFAULT_CHECKOUT_PATH = 'thanh-toan/';

let schemaEnsurePromise = null;

function normalizeText(value) {
  return typeof value === 'string' ? value.trim() : '';
}

function normalizeEmail(value) {
  return normalizeText(String(value || '')).toLowerCase();
}

function normalizeCouponCode(value) {
  return normalizeText(String(value || '')).toUpperCase();
}

function normalizePaymentMethod(value) {
  return normalizeText(String(value || '')).toUpperCase();
}

function parsePositiveInteger(value) {
  const parsedValue = Number(value);
  if (!Number.isInteger(parsedValue) || parsedValue < 0) {
    return null;
  }

  return parsedValue;
}

function createSecureToken(length = 24) {
  return crypto.randomBytes(length).toString('hex');
}

function normalizeTransactionStatus(status) {
  return normalizeText(String(status || '')).toUpperCase();
}

function isTerminalTransactionStatus(status) {
  return TERMINAL_TRANSACTION_STATUSES.has(normalizeTransactionStatus(status));
}

function isResumableTransactionStatus(status) {
  const normalizedStatus = normalizeTransactionStatus(status);
  return normalizedStatus === 'PENDING' || normalizedStatus === 'SUCCESS';
}

function ensureTrailingSlash(value) {
  return /\/$/.test(value) ? value : `${value}/`;
}

function getConfiguredFrontendUrl() {
  try {
    const configuredUrl = new URL(ensureTrailingSlash(process.env.FRONTEND_URL || DEFAULT_FRONTEND_URL));
    configuredUrl.hash = '';
    configuredUrl.search = '';

    if (!/^https?:$/.test(configuredUrl.protocol)) {
      throw new Error('Unsupported frontend protocol.');
    }

    return configuredUrl;
  } catch (error) {
    return new URL(DEFAULT_FRONTEND_URL);
  }
}

function getAllowedFrontendOrigins() {
  const configuredFrontend = getConfiguredFrontendUrl();
  const configuredOrigins = normalizeText(process.env.FRONTEND_ALLOWED_ORIGINS || '');
  const originCandidates = configuredOrigins
    ? configuredOrigins.split(',').map((origin) => normalizeText(origin)).filter(Boolean)
    : [configuredFrontend.origin];

  const safeOrigins = originCandidates.reduce((accumulator, origin) => {
    try {
      const parsedOrigin = new URL(origin);

      if (/^https?:$/.test(parsedOrigin.protocol)) {
        accumulator.push(parsedOrigin.origin);
      }
    } catch (error) {
      // Ignore invalid configured origins and fall back to the known-safe frontend URL below.
    }

    return accumulator;
  }, []);

  if (!safeOrigins.length) {
    safeOrigins.push(configuredFrontend.origin);
  }

  return Array.from(new Set(safeOrigins));
}

function getSafeCheckoutPath() {
  const configuredPath = normalizeText(process.env.CHECKOUT_RETURN_PATH || DEFAULT_CHECKOUT_PATH)
    .replace(/^\/+/, '');

  return configuredPath || DEFAULT_CHECKOUT_PATH;
}

function buildSafeFrontendCheckoutUrl(query = {}) {
  const frontendUrl = getConfiguredFrontendUrl();
  const allowedOrigins = getAllowedFrontendOrigins();
  const safeUrl = new URL(getSafeCheckoutPath(), ensureTrailingSlash(frontendUrl.toString()));

  if (!allowedOrigins.includes(safeUrl.origin)) {
    const fallbackUrl = new URL(getSafeCheckoutPath(), ensureTrailingSlash(DEFAULT_FRONTEND_URL));
    Object.entries(query).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        fallbackUrl.searchParams.set(key, String(value));
      }
    });

    return fallbackUrl.toString();
  }

  Object.entries(query).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      safeUrl.searchParams.set(key, String(value));
    }
  });

  return safeUrl.toString();
}

function buildRetryRequestId(requestId) {
  return `${requestId}-${Date.now()}`;
}

function signGatewayPayload(transactionCode, status, checkoutToken) {
  return crypto
    .createHmac('sha256', CALLBACK_SECRET)
    .update(`${transactionCode}:${status}:${checkoutToken}`)
    .digest('hex');
}

function buildTransactionCode(paymentId) {
  return `TX${Date.now()}${String(paymentId).padStart(6, '0')}`;
}

function buildBookingCode(bookingId, createdAt = new Date()) {
  const date = new Date(createdAt);
  const yyyy = String(date.getFullYear());
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');

  return `ADN-${yyyy}${mm}${dd}-${String(bookingId).padStart(6, '0')}`;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('vi-VN').format(roundCurrency(amount));
}

async function columnExists(tableName, columnName) {
  const [rows] = await db.query(`SHOW COLUMNS FROM \`${tableName}\` LIKE ?`, [columnName]);
  return rows.length > 0;
}

async function addColumnIfMissing(tableName, columnName, columnDefinition) {
  if (await columnExists(tableName, columnName)) {
    return;
  }

  await db.query(`ALTER TABLE \`${tableName}\` ADD COLUMN \`${columnName}\` ${columnDefinition}`);
}

async function ensureCheckoutSchema() {
  if (schemaEnsurePromise) {
    return schemaEnsurePromise;
  }

  schemaEnsurePromise = (async () => {
    await db.query(`
      CREATE TABLE IF NOT EXISTS booking_details (
        booking_id INT PRIMARY KEY,
        contact_name VARCHAR(255) NOT NULL,
        contact_phone VARCHAR(50) NOT NULL,
        contact_email VARCHAR(255) NOT NULL,
        contact_country VARCHAR(120) NOT NULL,
        adults_count INT NOT NULL DEFAULT 1,
        children_count INT NOT NULL DEFAULT 0,
        special_requests TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_booking_details_booking
          FOREIGN KEY (booking_id) REFERENCES bookings(id)
          ON DELETE CASCADE
      )
    `);

    await db.query(`
      CREATE TABLE IF NOT EXISTS coupons (
        id INT PRIMARY KEY AUTO_INCREMENT,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_type ENUM('PERCENT', 'FIXED') NOT NULL DEFAULT 'PERCENT',
        discount_value DECIMAL(10,2) NOT NULL,
        min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        max_discount_amount DECIMAL(10,2) NULL,
        status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
        description VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    `);

    await db.query(`
      CREATE TABLE IF NOT EXISTS payment_transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        payment_id INT NOT NULL UNIQUE,
        booking_id INT NOT NULL,
        request_id VARCHAR(100) NOT NULL UNIQUE,
        provider VARCHAR(50) NOT NULL,
        transaction_code VARCHAR(100) NOT NULL UNIQUE,
        checkout_token VARCHAR(120) NOT NULL,
        coupon_code VARCHAR(50) NULL,
        base_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
        return_url TEXT NULL,
        gateway_reference VARCHAR(120) NULL,
        gateway_payload_json TEXT NULL,
        completed_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_payment_transactions_payment
          FOREIGN KEY (payment_id) REFERENCES payments(id)
          ON DELETE CASCADE,
        CONSTRAINT fk_payment_transactions_booking
          FOREIGN KEY (booking_id) REFERENCES bookings(id)
          ON DELETE CASCADE
      )
    `);

    await db.query(`
      CREATE TABLE IF NOT EXISTS booking_audit_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        booking_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        actor_type VARCHAR(30) NOT NULL DEFAULT 'system',
        actor_id VARCHAR(100) NULL,
        actor_name VARCHAR(255) NULL,
        note TEXT NULL,
        payload_json TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_booking_audit_logs_booking
          FOREIGN KEY (booking_id) REFERENCES bookings(id)
          ON DELETE CASCADE
      )
    `);

    await addColumnIfMissing('bookings', 'booking_status', "VARCHAR(30) NOT NULL DEFAULT 'PENDING_PAYMENT' AFTER `status`");
    await addColumnIfMissing('bookings', 'payment_status', "VARCHAR(30) NOT NULL DEFAULT 'PENDING' AFTER `booking_status`");
    await addColumnIfMissing('bookings', 'payment_plan', "VARCHAR(20) NOT NULL DEFAULT 'FULL' AFTER `payment_status`");
    await addColumnIfMissing('bookings', 'confirmed_by', 'VARCHAR(191) NULL AFTER `payment_plan`');
    await addColumnIfMissing('bookings', 'confirmed_at', 'DATETIME NULL AFTER `confirmed_by`');
    await addColumnIfMissing('bookings', 'paid_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `confirmed_at`');
    await addColumnIfMissing('bookings', 'remaining_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `paid_amount`');
    await addColumnIfMissing('bookings', 'payment_receipt_sent_at', 'DATETIME NULL AFTER `remaining_amount`');
    await addColumnIfMissing('bookings', 'confirmation_sent_at', 'DATETIME NULL AFTER `payment_receipt_sent_at`');

    await addColumnIfMissing('payments', 'payment_plan', "VARCHAR(20) NOT NULL DEFAULT 'FULL' AFTER `status`");
    await addColumnIfMissing('payments', 'paid_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `payment_plan`');
    await addColumnIfMissing('payments', 'remaining_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `paid_amount`');
    await addColumnIfMissing('payments', 'refund_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `remaining_amount`');
    await addColumnIfMissing('payments', 'receipt_sent_at', 'DATETIME NULL AFTER `refund_amount`');
    await addColumnIfMissing('payments', 'refunded_at', 'DATETIME NULL AFTER `receipt_sent_at`');

    await addColumnIfMissing('payment_transactions', 'payment_plan', "VARCHAR(20) NOT NULL DEFAULT 'FULL' AFTER `provider`");
    await addColumnIfMissing('payment_transactions', 'paid_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `total_amount`');
    await addColumnIfMissing('payment_transactions', 'remaining_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `paid_amount`');

    await db.query(`
      UPDATE bookings
      SET
        booking_status = CASE
          WHEN booking_status IS NULL OR booking_status = '' THEN
            CASE
              WHEN status = 'PENDING' THEN 'PENDING_PAYMENT'
              ELSE status
            END
          ELSE booking_status
        END,
        payment_status = CASE
          WHEN payment_status IS NULL OR payment_status = '' THEN
            CASE
              WHEN status = 'PAYMENT_FAILED' THEN 'FAILED'
              WHEN status IN ('CONFIRMED', 'COMPLETED') THEN 'PAID'
              WHEN status = 'CANCELLED' THEN 'FAILED'
              ELSE 'PENDING'
            END
          ELSE payment_status
        END,
        payment_plan = CASE
          WHEN payment_plan IS NULL OR payment_plan = '' THEN 'FULL'
          ELSE payment_plan
        END,
        paid_amount = CASE
          WHEN paid_amount IS NULL THEN 0
          ELSE paid_amount
        END,
        remaining_amount = CASE
          WHEN remaining_amount IS NULL THEN COALESCE(total_price, 0)
          ELSE remaining_amount
        END
    `);

    await db.query(`
      UPDATE bookings b
      LEFT JOIN payments p
        ON p.id = (
          SELECT p2.id
          FROM payments p2
          WHERE p2.booking_id = b.id
          ORDER BY p2.id DESC
          LIMIT 1
        )
      SET
        b.payment_status = CASE
          WHEN p.status = 'SUCCESS' AND COALESCE(p.remaining_amount, 0) > 0 THEN 'PARTIALLY_PAID'
          WHEN p.status = 'SUCCESS' THEN 'PAID'
          WHEN p.status = 'REFUNDED' THEN 'REFUNDED'
          WHEN p.status IN ('FAILED', 'CANCELLED', 'EXPIRED') THEN 'FAILED'
          ELSE b.payment_status
        END,
        b.payment_plan = COALESCE(NULLIF(p.payment_plan, ''), b.payment_plan),
        b.paid_amount = CASE
          WHEN p.status = 'SUCCESS' THEN COALESCE(NULLIF(p.paid_amount, 0), p.amount, b.paid_amount)
          ELSE b.paid_amount
        END,
        b.remaining_amount = CASE
          WHEN p.status = 'SUCCESS' THEN COALESCE(p.remaining_amount, GREATEST(COALESCE(b.total_price, 0) - COALESCE(NULLIF(p.paid_amount, 0), p.amount, 0), 0))
          ELSE b.remaining_amount
        END
      WHERE p.id IS NOT NULL
    `);

    await db.query(
      `INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, status, description)
       VALUES
        ('ADN10', 'PERCENT', 10, 1000000, 500000, 'ACTIVE', 'Giam 10% toi da 500.000d cho booking tu 1.000.000d'),
        ('SUMMER300', 'FIXED', 300000, 2500000, NULL, 'ACTIVE', 'Giam truc tiep 300.000d cho don tu 2.500.000d'),
        ('FAMILY5', 'PERCENT', 5, 1500000, 250000, 'ACTIVE', 'Danh cho gia dinh, giam 5% toi da 250.000d')
       ON DUPLICATE KEY UPDATE
        discount_type = VALUES(discount_type),
        discount_value = VALUES(discount_value),
        min_order_amount = VALUES(min_order_amount),
        max_discount_amount = VALUES(max_discount_amount),
        status = VALUES(status),
        description = VALUES(description)`
    );
  })().catch((error) => {
    schemaEnsurePromise = null;
    throw error;
  });

  return schemaEnsurePromise;
}

async function getTourById(tourId) {
  const [rows] = await db.query(
    `SELECT id, title, description, price, location, duration, duration_text, max_people, status,
            image_url, transport, departure_note, season, meeting_point
     FROM tours
     WHERE id = ? LIMIT 1`,
    [Number(tourId)],
  );

  return rows[0] || null;
}

async function getCouponByCode(code) {
  if (!code) {
    return null;
  }

  const [rows] = await db.query(
    `SELECT id, code, discount_type, discount_value, min_order_amount, max_discount_amount, status, description
     FROM coupons
     WHERE code = ? AND status = 'ACTIVE'
     LIMIT 1`,
    [code],
  );

  return rows[0] || null;
}

function calculateDiscount(subtotal, coupon) {
  if (!coupon) {
    return 0;
  }

  if (subtotal < Number(coupon.min_order_amount || 0)) {
    return 0;
  }

  let discountAmount = 0;

  if (coupon.discount_type === 'FIXED') {
    discountAmount = Number(coupon.discount_value || 0);
  } else {
    discountAmount = subtotal * (Number(coupon.discount_value || 0) / 100);
  }

  if (coupon.max_discount_amount) {
    discountAmount = Math.min(discountAmount, Number(coupon.max_discount_amount));
  }

  return roundCurrency(discountAmount);
}

function buildQuote(tour, adultsCount, childrenCount, coupon) {
  const adultPrice = roundCurrency(tour.price);
  const childPrice = roundCurrency(adultPrice * CHILD_PRICE_RATIO);
  const subtotal = roundCurrency(adultPrice * adultsCount + childPrice * childrenCount);
  const taxAmount = roundCurrency(subtotal * TAX_RATE);
  const feeAmount = subtotal > 0 ? SERVICE_FEE : 0;
  const discountAmount = calculateDiscount(subtotal, coupon);
  const totalAmount = roundCurrency(subtotal + taxAmount + feeAmount - discountAmount);

  return {
    basePrice: adultPrice,
    childPrice,
    subtotal,
    taxAmount,
    feeAmount,
    discountAmount,
    totalAmount,
    travellers: adultsCount + childrenCount,
    adultsCount,
    childrenCount,
    coupon: coupon
      ? {
          code: coupon.code,
          discountType: coupon.discount_type,
          discountValue: Number(coupon.discount_value || 0),
          description: coupon.description || '',
          maxDiscountAmount: coupon.max_discount_amount ? Number(coupon.max_discount_amount) : null,
          minOrderAmount: Number(coupon.min_order_amount || 0),
        }
      : null,
  };
}

function buildCheckoutSummary(tour, quote, travelDate, paymentPlan = PAYMENT_PLANS.FULL) {
  const paymentBreakdown = calculatePaymentPlanAmounts(quote.totalAmount, paymentPlan);

  return {
    tour: {
      id: tour.id,
      title: tour.title || '',
      imageUrl: tour.image_url || null,
      location: tour.location || '',
      duration: tour.duration_text || `${tour.duration || 0} ngay`,
      meetingPoint: tour.meeting_point || '',
      transport: tour.transport || '',
      departureNote: tour.departure_note || '',
      season: tour.season || '',
    },
    travelDate,
    pricing: {
      adultPrice: quote.basePrice,
      childPrice: quote.childPrice,
      subtotal: quote.subtotal,
      taxAmount: quote.taxAmount,
      feeAmount: quote.feeAmount,
      discountAmount: quote.discountAmount,
      totalAmount: quote.totalAmount,
      payableNowAmount: paymentBreakdown.paidAmount,
      remainingAmount: paymentBreakdown.remainingAmount,
      paymentPlan: paymentBreakdown.paymentPlan,
      depositRate: paymentBreakdown.paymentPlan === PAYMENT_PLANS.DEPOSIT ? getDepositRate() : 1,
      display: {
        adultPrice: formatCurrency(quote.basePrice),
        childPrice: formatCurrency(quote.childPrice),
        subtotal: formatCurrency(quote.subtotal),
        taxAmount: formatCurrency(quote.taxAmount),
        feeAmount: formatCurrency(quote.feeAmount),
        discountAmount: formatCurrency(quote.discountAmount),
        totalAmount: formatCurrency(quote.totalAmount),
        payableNowAmount: formatCurrency(paymentBreakdown.paidAmount),
        remainingAmount: formatCurrency(paymentBreakdown.remainingAmount),
      },
    },
    passengers: {
      adults: quote.adultsCount,
      children: quote.childrenCount,
      total: quote.travellers,
    },
    coupon: quote.coupon,
  };
}

function buildGatewayUrl(req, transactionCode, checkoutToken) {
  return `${req.protocol}://${req.get('host')}/api/checkout/gateway/${encodeURIComponent(transactionCode)}?token=${encodeURIComponent(checkoutToken)}`;
}

function buildGatewayCallbackUrl(req, transactionCode, checkoutToken, status) {
  const callbackUrl = new URL(
    `${req.protocol}://${req.get('host')}/api/checkout/callback/${encodeURIComponent(transactionCode)}`,
  );

  callbackUrl.searchParams.set('token', checkoutToken);
  callbackUrl.searchParams.set('status', status);
  callbackUrl.searchParams.set('signature', signGatewayPayload(transactionCode, status, checkoutToken));

  return callbackUrl.toString();
}

async function writeCheckoutEmailLog(payload) {
  try {
    const logDir = path.join(__dirname, '..', 'logs');
    await fs.promises.mkdir(logDir, { recursive: true });
    const logLine = `${new Date().toISOString()} ${JSON.stringify(payload)}\n`;
    await fs.promises.appendFile(path.join(logDir, 'checkout-mails.log'), logLine, 'utf8');
  } catch (error) {
    // Ignore logging failures to avoid breaking the checkout callback.
  }
}

async function createBookingAuditLog(connection, bookingId, action, payload = {}) {
  if (!bookingId || !action) {
    return;
  }

  await connection.query(
    `INSERT INTO booking_audit_logs
     (booking_id, action, actor_type, actor_id, actor_name, note, payload_json)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    [
      Number(bookingId),
      action,
      payload.actorType || 'system',
      payload.actorId ? String(payload.actorId) : null,
      payload.actorName ? String(payload.actorName) : null,
      payload.note ? String(payload.note) : null,
      payload.payload ? JSON.stringify(payload.payload) : null,
    ],
  );
}

function parseGatewayPayload(payload) {
  if (!payload) {
    return {};
  }

  try {
    const parsedPayload = JSON.parse(payload);
    return parsedPayload && typeof parsedPayload === 'object' ? parsedPayload : {};
  } catch (error) {
    return {};
  }
}

async function buildTransactionSummaryByCode(transactionCode, checkoutToken = '', options = {}) {
  await ensureCheckoutSchema();
  const userId = Number(options.userId || 0);
  const queryParams = [transactionCode];
  const ownershipClause = userId > 0 ? ' AND b.user_id = ?' : '';

  if (userId > 0) {
    queryParams.push(userId);
  }

  const [rows] = await db.query(
    `SELECT
        pt.id AS transaction_id,
        pt.provider,
        pt.transaction_code,
        pt.checkout_token,
        pt.coupon_code,
        pt.base_amount,
        pt.discount_amount,
        pt.tax_amount,
        pt.fee_amount,
        pt.total_amount,
        pt.status AS transaction_status,
        pt.return_url,
        pt.gateway_reference,
        pt.gateway_payload_json,
        pt.completed_at,
        b.id AS booking_id,
        b.user_id,
        b.travel_date,
        b.number_of_people,
        b.total_price,
        b.status AS booking_legacy_status,
        COALESCE(NULLIF(b.booking_status, ''), b.status) AS booking_status,
        COALESCE(NULLIF(b.payment_status, ''), 'PENDING') AS booking_payment_status,
        COALESCE(NULLIF(b.payment_plan, ''), 'FULL') AS booking_payment_plan,
        COALESCE(b.paid_amount, 0) AS booking_paid_amount,
        COALESCE(b.remaining_amount, 0) AS booking_remaining_amount,
        b.confirmed_by,
        b.confirmed_at,
        b.payment_receipt_sent_at,
        b.confirmation_sent_at,
        b.created_at AS booking_created_at,
        bd.contact_name,
        bd.contact_phone,
        bd.contact_email,
        bd.contact_country,
        bd.adults_count,
        bd.children_count,
        bd.special_requests,
        p.id AS payment_id,
        p.method AS payment_method,
        p.status AS payment_status,
        p.amount AS payment_amount,
        COALESCE(NULLIF(p.payment_plan, ''), 'FULL') AS payment_plan,
        COALESCE(p.paid_amount, 0) AS payment_paid_amount,
        COALESCE(p.remaining_amount, 0) AS payment_remaining_amount,
        COALESCE(p.refund_amount, 0) AS payment_refund_amount,
        p.receipt_sent_at,
        p.refunded_at,
        p.paid_at,
        t.id AS tour_id,
        t.title AS tour_title,
        t.location,
        t.image_url,
        t.duration_text,
        t.meeting_point,
        t.transport,
        t.departure_note
     FROM payment_transactions pt
     JOIN bookings b ON b.id = pt.booking_id
     JOIN payments p ON p.id = pt.payment_id
     JOIN tours t ON t.id = b.tour_id
     LEFT JOIN booking_details bd ON bd.booking_id = b.id
     WHERE pt.transaction_code = ?${ownershipClause} LIMIT 1`,
    queryParams,
  );

  if (!rows.length) {
    return null;
  }

  const row = rows[0];
  const gatewayPayload = parseGatewayPayload(row.gateway_payload_json);

  if (checkoutToken && row.checkout_token !== checkoutToken) {
    return null;
  }

  return {
    booking: {
      id: row.booking_id,
      code: buildBookingCode(row.booking_id, row.booking_created_at),
      travelDate: row.travel_date,
      travellers: row.number_of_people,
      totalPrice: Number(row.total_price || 0),
      status: row.booking_status,
      legacyStatus: row.booking_legacy_status,
      paymentStatus: row.booking_payment_status,
      paymentPlan: row.booking_payment_plan,
      paidAmount: Number(row.booking_paid_amount || 0),
      remainingAmount: Number(row.booking_remaining_amount || 0),
      confirmedBy: row.confirmed_by || '',
      confirmedAt: row.confirmed_at,
      paymentReceiptSentAt: row.payment_receipt_sent_at,
      confirmationSentAt: row.confirmation_sent_at,
      createdAt: row.booking_created_at,
      details: {
        contactName: row.contact_name || '',
        contactPhone: row.contact_phone || '',
        contactEmail: row.contact_email || '',
        contactCountry: row.contact_country || '',
        adultsCount: Number(row.adults_count || 0),
        childrenCount: Number(row.children_count || 0),
        specialRequests: row.special_requests || '',
      },
    },
    payment: {
      id: row.payment_id,
      method: row.payment_method,
      status: row.payment_status,
      amount: Number(row.payment_amount || 0),
      paymentPlan: row.payment_plan,
      paidAmount: Number(row.payment_paid_amount || 0),
      remainingAmount: Number(row.payment_remaining_amount || 0),
      refundAmount: Number(row.payment_refund_amount || 0),
      receiptSentAt: row.receipt_sent_at,
      refundedAt: row.refunded_at,
      paidAt: row.paid_at,
    },
    transaction: {
      id: row.transaction_id,
      code: row.transaction_code,
      provider: row.provider,
      status: row.transaction_status,
      paymentPlan: row.payment_plan,
      checkoutToken: row.checkout_token,
      couponCode: row.coupon_code || '',
      gatewayReference: row.gateway_reference || '',
      completedAt: row.completed_at,
      returnUrl: row.return_url || '',
      frontendContext: gatewayPayload.frontendContext && typeof gatewayPayload.frontendContext === 'object'
        ? gatewayPayload.frontendContext
        : {},
    },
    tour: {
      id: row.tour_id,
      title: row.tour_title || '',
      location: row.location || '',
      imageUrl: row.image_url || null,
      duration: row.duration_text || '',
      meetingPoint: row.meeting_point || '',
      transport: row.transport || '',
      departureNote: row.departure_note || '',
    },
    pricing: {
      baseAmount: Number(row.base_amount || 0),
      discountAmount: Number(row.discount_amount || 0),
      taxAmount: Number(row.tax_amount || 0),
      feeAmount: Number(row.fee_amount || 0),
      totalAmount: Number(row.total_amount || 0),
      payableNowAmount: Number(row.payment_paid_amount || row.payment_amount || 0),
      remainingAmount: Number(row.payment_remaining_amount || 0),
    },
  };
}

function buildFrontendResultUrl(summary, result) {
  const frontendContext = summary.transaction.frontendContext || {};
  const travellers = Number(summary.booking?.travellers || 0);
  const childrenCount = Number(summary.booking?.details?.childrenCount || 0);

  return buildSafeFrontendCheckoutUrl({
    tour_id: Number(frontendContext.tourId || 0) > 0 ? Number(frontendContext.tourId) : '',
    travel_date: summary.booking?.travelDate || '',
    party_size: travellers > 0 ? travellers : '',
    children: childrenCount > 0 ? childrenCount : 0,
    checkout_tx: summary.transaction.code,
    checkout_result: result === 'success' ? 'success' : 'failed',
  });
}

function buildCheckoutSessionResponse(req, summary, message) {
  return {
    message,
    booking: summary.booking,
    payment: summary.payment,
    transaction: summary.transaction,
    pricing: summary.pricing,
    redirectUrl: buildGatewayUrl(req, summary.transaction.code, summary.transaction.checkoutToken),
  };
}

async function getLatestTransactionForBooking(bookingId, userId = 0) {
  const [rows] = await db.query(
    `SELECT transaction_code
     FROM payment_transactions
     WHERE booking_id = ?
     ORDER BY id DESC
     LIMIT 1`,
    [Number(bookingId)],
  );

  if (!rows.length) {
    return null;
  }

  return buildTransactionSummaryByCode(rows[0].transaction_code, '', { userId });
}

async function getRetryTransactionSummary(transactionCode, userId) {
  const summary = await buildTransactionSummaryByCode(transactionCode, '', { userId });

  if (!summary) {
    return null;
  }

  return summary;
}

exports.previewQuote = async (req, res) => {
  const tourId = Number(req.body.tour_id || req.body.tourId);
  const travelDate = normalizeText(req.body.travel_date || req.body.travelDate);
  const paymentPlan = normalizePaymentPlan(req.body.payment_plan || req.body.paymentPlan);
  const adultsCount = parsePositiveInteger(req.body.adults_count ?? req.body.adultsCount ?? 1);
  const childrenCount = parsePositiveInteger(req.body.children_count ?? req.body.childrenCount ?? 0);
  const couponCode = normalizeCouponCode(req.body.coupon_code || req.body.couponCode);

  if (!Number.isInteger(tourId) || tourId <= 0) {
    return res.status(400).json({ message: 'Tour khong hop le.' });
  }

  if (!travelDate) {
    return res.status(400).json({ message: 'Vui long chon ngay khoi hanh.' });
  }

  if (adultsCount === null || childrenCount === null) {
    return res.status(400).json({ message: 'So luong hanh khach khong hop le.' });
  }

  const totalTravellers = adultsCount + childrenCount;

  if (totalTravellers <= 0) {
    return res.status(400).json({ message: 'Booking phai co it nhat 1 hanh khach.' });
  }

  try {
    await ensureCheckoutSchema();

    const tour = await getTourById(tourId);

    if (!tour) {
      return res.status(404).json({ message: 'Khong tim thay tour.' });
    }

    if (tour.status !== 'Active') {
      return res.status(400).json({ message: 'Tour nay hien chua mo dat cho online.' });
    }

    if (tour.max_people && totalTravellers > Number(tour.max_people)) {
      return res.status(400).json({
        message: `So luong khach vuot qua gioi han toi da ${tour.max_people} nguoi.`,
      });
    }

    const coupon = couponCode ? await getCouponByCode(couponCode) : null;
    const quote = buildQuote(tour, adultsCount, childrenCount, coupon);
    const summary = buildCheckoutSummary(tour, quote, travelDate, paymentPlan);

    if (couponCode && !coupon) {
      return res.status(200).json({
        message: 'Ma giam gia khong hop le hoac da het han.',
        summary,
        couponValid: false,
      });
    }

    if (couponCode && !quote.discountAmount) {
      return res.status(200).json({
        message: 'Ma giam gia hop le nhung chua du dieu kien ap dung cho don nay.',
        summary,
        couponValid: false,
      });
    }

    return res.status(200).json({
      message: couponCode && quote.discountAmount
        ? `Da ap dung ma ${couponCode} thanh cong.`
        : 'Tinh tong thanh toan thanh cong.',
      summary,
      couponValid: !couponCode || Boolean(quote.discountAmount),
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the tinh tong thanh toan luc nay.',
      error: error.message,
    });
  }
};

exports.createCheckoutSession = async (req, res) => {
  const userId = Number(req.user?.id || 0);
  const tourId = Number(req.body.tour_id || req.body.tourId);
  const travelDate = normalizeText(req.body.travel_date || req.body.travelDate);
  const requestId = normalizeText(req.body.request_id || req.body.requestId);
  const retryTransactionCodeInput = normalizeText(req.body.retry_transaction_code || req.body.retryTransactionCode);
  const frontendTourId = Number(req.body.frontend_tour_id || req.body.frontendTourId || 0);
  const contactName = normalizeText(req.body.contact_name || req.body.contactName);
  const contactPhone = normalizeText(req.body.contact_phone || req.body.contactPhone);
  const contactEmail = normalizeEmail(req.body.contact_email || req.body.contactEmail);
  const contactCountry = normalizeText(req.body.contact_country || req.body.contactCountry || 'Vietnam');
  const specialRequests = normalizeText(req.body.special_requests || req.body.specialRequests);
  const paymentMethod = normalizePaymentMethod(req.body.payment_method || req.body.paymentMethod);
  const paymentPlan = normalizePaymentPlan(req.body.payment_plan || req.body.paymentPlan);
  const couponCode = normalizeCouponCode(req.body.coupon_code || req.body.couponCode);
  const adultsCount = parsePositiveInteger(req.body.adults_count ?? req.body.adultsCount ?? 1);
  const childrenCount = parsePositiveInteger(req.body.children_count ?? req.body.childrenCount ?? 0);

  if (!userId) {
    return res.status(401).json({ message: 'Ban can dang nhap truoc khi thanh toan.' });
  }

  if (!Number.isInteger(tourId) || tourId <= 0 || !travelDate || !requestId) {
    return res.status(400).json({ message: 'Thieu thong tin de tao phien thanh toan.' });
  }

  if (!contactName || !contactPhone || !contactEmail || !contactCountry) {
    return res.status(400).json({ message: 'Vui long nhap day du thong tin lien he.' });
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contactEmail)) {
    return res.status(400).json({ message: 'Email lien he khong dung dinh dang.' });
  }

  if (!PAYMENT_METHODS[paymentMethod]) {
    return res.status(400).json({ message: 'Phuong thuc thanh toan khong hop le.' });
  }

  if (adultsCount === null || childrenCount === null || adultsCount + childrenCount <= 0) {
    return res.status(400).json({ message: 'Booking can it nhat 1 hanh khach.' });
  }

  try {
    await ensureCheckoutSchema();
    let effectiveRequestId = requestId;
    let retrySummary = null;

    const [existingTransactions] = await db.query(
      `SELECT
          pt.transaction_code,
          pt.checkout_token,
          pt.status,
          b.id AS booking_id,
          b.created_at AS booking_created_at,
          p.id AS payment_id
       FROM payment_transactions pt
       JOIN bookings b ON b.id = pt.booking_id
       JOIN payments p ON p.id = pt.payment_id
       WHERE pt.request_id = ? LIMIT 1`,
      [requestId],
    );

    if (existingTransactions.length > 0) {
      const existingSummary = await buildTransactionSummaryByCode(
        existingTransactions[0].transaction_code,
        '',
        { userId },
      );

      if (!existingSummary) {
        return res.status(409).json({ message: 'Phien thanh toan da ton tai nhung khong the khoi phuc.' });
      }

      if (isResumableTransactionStatus(existingSummary.transaction.status)) {
        return res.status(200).json(
          buildCheckoutSessionResponse(
            req,
            existingSummary,
            'Phien thanh toan da duoc tao truoc do. Dang mo lai de ban tiep tuc.',
          ),
        );
      }

      retrySummary = existingSummary;
      effectiveRequestId = buildRetryRequestId(requestId);
    }

    if (retryTransactionCodeInput) {
      const explicitRetrySummary = await getRetryTransactionSummary(retryTransactionCodeInput, userId);

      if (!explicitRetrySummary) {
        return res.status(404).json({ message: 'Khong tim thay giao dich can retry.' });
      }

      if (isResumableTransactionStatus(explicitRetrySummary.transaction.status)) {
        return res.status(200).json(
          buildCheckoutSessionResponse(
            req,
            explicitRetrySummary,
            normalizeTransactionStatus(explicitRetrySummary.transaction.status) === 'SUCCESS'
              ? 'Giao dich nay da thanh toan thanh cong truoc do.'
              : 'Phat hien mot phien thanh toan dang cho xu ly. Dang mo lai de ban tiep tuc.',
          ),
        );
      }

      retrySummary = explicitRetrySummary;
    }

    if (retrySummary && !isTerminalTransactionStatus(retrySummary.transaction.status)) {
      return res.status(409).json({ message: 'Giao dich hien tai khong nam trong trang thai co the retry.' });
    }

    const tour = await getTourById(tourId);

    if (!tour) {
      return res.status(404).json({ message: 'Khong tim thay tour.' });
    }

    if (tour.status !== 'Active') {
      return res.status(400).json({ message: 'Tour nay hien chua nhan booking online.' });
    }

    const totalTravellers = adultsCount + childrenCount;

    if (tour.max_people && totalTravellers > Number(tour.max_people)) {
      return res.status(400).json({
        message: `So luong khach vuot qua gioi han toi da ${tour.max_people} nguoi.`,
      });
    }

    const coupon = couponCode ? await getCouponByCode(couponCode) : null;
    const quote = buildQuote(tour, adultsCount, childrenCount, coupon);
    const paymentAmounts = calculatePaymentPlanAmounts(quote.totalAmount, paymentPlan);
    const frontendContext = {
      tourId: frontendTourId > 0 ? frontendTourId : null,
    };

    if (retrySummary) {
      const latestTransaction = await getLatestTransactionForBooking(retrySummary.booking.id, userId);

      if (
        latestTransaction &&
        latestTransaction.transaction.code !== retrySummary.transaction.code &&
        isResumableTransactionStatus(latestTransaction.transaction.status)
      ) {
        return res.status(200).json(
          buildCheckoutSessionResponse(
            req,
            latestTransaction,
            latestTransaction.transaction.status === 'SUCCESS'
              ? 'Booking nay da duoc thanh toan thanh cong roi.'
              : 'Phat hien mot phien thanh toan moi hon cho booking nay. Dang mo lai de ban tiep tuc.',
          ),
        );
      }
    }

    const connection = await db.getConnection();

    try {
      await connection.beginTransaction();
      let bookingId = 0;

      if (retrySummary) {
        bookingId = Number(retrySummary.booking.id || 0);

        await connection.query(
          `UPDATE bookings
           SET travel_date = ?, number_of_people = ?, total_price = ?, status = ?, booking_status = ?, payment_status = ?, payment_plan = ?, paid_amount = 0, remaining_amount = ?, confirmed_by = NULL, confirmed_at = NULL, confirmation_sent_at = NULL
           WHERE id = ? AND user_id = ?`,
          [
            travelDate,
            totalTravellers,
            quote.totalAmount,
            BOOKING_STATUSES.PENDING_PAYMENT,
            BOOKING_STATUSES.PENDING_PAYMENT,
            BOOKING_PAYMENT_STATUSES.PENDING,
            paymentAmounts.paymentPlan,
            quote.totalAmount,
            bookingId,
            userId,
          ],
        );

        const [detailRows] = await connection.query(
          'SELECT booking_id FROM booking_details WHERE booking_id = ? LIMIT 1',
          [bookingId],
        );

        if (detailRows.length > 0) {
          await connection.query(
            `UPDATE booking_details
             SET contact_name = ?, contact_phone = ?, contact_email = ?, contact_country = ?, adults_count = ?, children_count = ?, special_requests = ?, updated_at = NOW()
             WHERE booking_id = ?`,
            [contactName, contactPhone, contactEmail, contactCountry, adultsCount, childrenCount, specialRequests || null, bookingId],
          );
        } else {
          await connection.query(
            `INSERT INTO booking_details
             (booking_id, contact_name, contact_phone, contact_email, contact_country, adults_count, children_count, special_requests)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
            [bookingId, contactName, contactPhone, contactEmail, contactCountry, adultsCount, childrenCount, specialRequests || null],
          );
        }

        // Rotate the previous checkout token so stale gateway links cannot be reused after a retry.
        await connection.query(
          `UPDATE payment_transactions
           SET checkout_token = ?, updated_at = NOW()
           WHERE id = ?`,
          [createSecureToken(16), retrySummary.transaction.id],
        );

        if (retrySummary.payment?.id) {
          await connection.query(
            `UPDATE payments
             SET status = 'FAILED'
             WHERE id = ? AND status <> 'SUCCESS'`,
            [retrySummary.payment.id],
          );
        }
      } else {
        const [bookingResult] = await connection.query(
          `INSERT INTO bookings
           (user_id, tour_id, travel_date, number_of_people, total_price, status, booking_status, payment_status, payment_plan, paid_amount, remaining_amount)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
          [
            userId,
            tour.id,
            travelDate,
            totalTravellers,
            quote.totalAmount,
            BOOKING_STATUSES.PENDING_PAYMENT,
            BOOKING_STATUSES.PENDING_PAYMENT,
            BOOKING_PAYMENT_STATUSES.PENDING,
            paymentAmounts.paymentPlan,
            0,
            quote.totalAmount,
          ],
        );

        bookingId = bookingResult.insertId;

        await connection.query(
          `INSERT INTO booking_details
           (booking_id, contact_name, contact_phone, contact_email, contact_country, adults_count, children_count, special_requests)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
          [bookingId, contactName, contactPhone, contactEmail, contactCountry, adultsCount, childrenCount, specialRequests || null],
        );
      }

      const [paymentResult] = await connection.query(
        `INSERT INTO payments
         (booking_id, amount, method, status, payment_plan, paid_amount, remaining_amount, paid_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          bookingId,
          paymentAmounts.paidAmount,
          paymentMethod,
          PAYMENT_RECORD_STATUSES.PENDING,
          paymentAmounts.paymentPlan,
          paymentAmounts.paidAmount,
          paymentAmounts.remainingAmount,
          null,
        ],
      );

      const paymentId = paymentResult.insertId;
      const checkoutToken = createSecureToken(16);
      const transactionCode = buildTransactionCode(paymentId);

      await connection.query(
        `INSERT INTO payment_transactions
         (payment_id, booking_id, request_id, provider, payment_plan, transaction_code, checkout_token, coupon_code, base_amount, discount_amount, tax_amount, fee_amount, total_amount, paid_amount, remaining_amount, status, return_url, gateway_payload_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          paymentId,
          bookingId,
          requestId,
          paymentMethod,
          paymentAmounts.paymentPlan,
          transactionCode,
          checkoutToken,
          couponCode || null,
          quote.subtotal,
          quote.discountAmount,
          quote.taxAmount,
          quote.feeAmount,
          quote.totalAmount,
          paymentAmounts.paidAmount,
          paymentAmounts.remainingAmount,
          PAYMENT_RECORD_STATUSES.PENDING,
          buildSafeFrontendCheckoutUrl(),
          JSON.stringify({
            contactName,
            contactPhone,
            contactEmail,
            contactCountry,
            specialRequests,
            adultsCount,
            childrenCount,
            paymentPlan: paymentAmounts.paymentPlan,
            paidAmount: paymentAmounts.paidAmount,
            remainingAmount: paymentAmounts.remainingAmount,
            frontendContext,
          }),
        ],
      );

      await createBookingAuditLog(connection, bookingId, 'BOOKING_CREATED', {
        actorType: 'system',
        note: retrySummary
          ? 'Checkout retry da tao mot phien thanh toan moi.'
          : 'Checkout tao booking pending payment.',
        payload: {
          travelDate,
          totalTravellers,
          paymentPlan: paymentAmounts.paymentPlan,
          payableNowAmount: paymentAmounts.paidAmount,
          remainingAmount: paymentAmounts.remainingAmount,
          paymentMethod,
        },
      });

      await connection.commit();

      const summary = await buildTransactionSummaryByCode(transactionCode, '', { userId });

      return res.status(201).json(
        buildCheckoutSessionResponse(
          req,
          summary,
          retrySummary
            ? 'Da tao phien thanh toan moi cho booking truoc do chua thanh cong.'
            : 'Da tao booking pending va chuyen sang cong thanh toan.',
        ),
      );
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the tao phien booking luc nay.',
      error: error.message,
    });
  }
};

exports.renderMockGateway = async (req, res) => {
  const transactionCode = normalizeText(req.params.transactionCode);
  const checkoutToken = normalizeText(req.query.token);

  try {
    const summary = await buildTransactionSummaryByCode(transactionCode, checkoutToken);

    if (!summary) {
      return res.status(404).send('<h1>Khong tim thay phien thanh toan</h1>');
    }

    const method = PAYMENT_METHODS[summary.transaction.provider] || PAYMENT_METHODS.VNPAY;
    const successCallbackUrl = buildGatewayCallbackUrl(
      req,
      transactionCode,
      summary.transaction.checkoutToken,
      'success',
    );
    const failedCallbackUrl = buildGatewayCallbackUrl(
      req,
      transactionCode,
      summary.transaction.checkoutToken,
      'failed',
    );

    return res.status(200).send(`<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Thanh toan ${method.label}</title>
  <style>
    :root {
      color-scheme: light;
      --accent: ${method.tone};
      --panel: rgba(255,255,255,0.9);
      --border: rgba(15, 23, 42, 0.08);
      --text: #10233c;
      --muted: #59708d;
      --bg-a: #eef7ff;
      --bg-b: #f8fbff;
      --success: #0f9d58;
      --danger: #d33b4d;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Segoe UI", Arial, sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top left, rgba(255,255,255,0.92), transparent 32%),
        linear-gradient(135deg, var(--bg-a), var(--bg-b));
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .gateway-shell {
      width: min(980px, 100%);
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      gap: 24px;
    }
    .card {
      border-radius: 28px;
      background: var(--panel);
      border: 1px solid var(--border);
      box-shadow: 0 25px 90px rgba(15, 23, 42, 0.10);
      backdrop-filter: blur(18px);
      padding: 28px;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 10px 16px;
      border-radius: 999px;
      background: rgba(255,255,255,0.75);
      color: var(--accent);
      font-weight: 700;
      letter-spacing: .03em;
    }
    .brand::before {
      content: "";
      width: 14px;
      height: 14px;
      border-radius: 999px;
      background: var(--accent);
      box-shadow: 0 0 0 8px color-mix(in srgb, var(--accent) 14%, white);
    }
    h1 {
      margin: 18px 0 10px;
      font-size: clamp(32px, 4vw, 48px);
      line-height: 1.02;
    }
    p {
      color: var(--muted);
      line-height: 1.7;
      margin: 0 0 14px;
    }
    .stats, .summary-list {
      margin-top: 22px;
      display: grid;
      gap: 14px;
    }
    .stat, .summary-item {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      padding: 16px 18px;
      border-radius: 18px;
      background: rgba(255,255,255,0.7);
      border: 1px solid rgba(15, 23, 42, 0.05);
    }
    .stat strong,
    .summary-item strong {
      font-size: 1rem;
    }
    .amount {
      display: block;
      margin-top: 18px;
      font-size: clamp(32px, 5vw, 48px);
      font-weight: 800;
      color: var(--text);
    }
    .actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-top: 26px;
    }
    button,
    .action-link {
      appearance: none;
      border: 0;
      cursor: pointer;
      border-radius: 18px;
      padding: 16px 18px;
      font-size: 1rem;
      font-weight: 700;
      transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    }
    .action-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      text-decoration: none;
      text-align: center;
    }
    button:hover { transform: translateY(-2px); }
    .action-link:hover { transform: translateY(-2px); }
    .btn-success {
      color: white;
      background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 70%, #07131f));
      box-shadow: 0 18px 32px color-mix(in srgb, var(--accent) 24%, transparent);
    }
    .btn-fail {
      color: #8f2131;
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(211, 59, 77, 0.18);
    }
    .note {
      margin-top: 18px;
      font-size: .94rem;
      color: var(--muted);
    }
    .summary-total {
      margin-top: 20px;
      padding-top: 18px;
      border-top: 1px dashed rgba(15,23,42,0.12);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 800;
      font-size: 1.08rem;
    }
    @media (max-width: 860px) {
      .gateway-shell { grid-template-columns: 1fr; }
      .actions { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="gateway-shell">
    <section class="card">
      <span class="brand">${method.label}</span>
      <h1>Mo phong cong thanh toan cao cap</h1>
      <p>${method.note}</p>
      <p>Luot redirect nay mo phong hanh vi cua cong thanh toan that. Sau khi xac nhan, backend se verify callback signature, cap nhat giao dich va chuyen booking sang trang thai cho nhan vien xac nhan.</p>
      <strong class="amount">${formatCurrency(summary.pricing.payableNowAmount || summary.pricing.totalAmount)}d</strong>
      <div class="stats">
        <div class="stat"><span>Booking code</span><strong>${summary.booking.code}</strong></div>
        <div class="stat"><span>Tour</span><strong>${summary.tour.title}</strong></div>
        <div class="stat"><span>Ngay khoi hanh</span><strong>${summary.booking.travelDate}</strong></div>
        <div class="stat"><span>Hanh khach</span><strong>${summary.booking.details.adultsCount} nguoi lon, ${summary.booking.details.childrenCount} tre em</strong></div>
        <div class="stat"><span>Hinh thuc</span><strong>${summary.pricing.paymentPlan === PAYMENT_PLANS.DEPOSIT ? 'Dat coc truoc' : 'Thanh toan toan bo'}</strong></div>
      </div>

      <div class="actions">
        <a class="action-link btn-success" href="${successCallbackUrl}">Thanh toan thanh cong</a>
        <a class="action-link btn-fail" href="${failedCallbackUrl}">Mo phong that bai</a>
      </div>
      <p class="note">Trong luc tich hop production, khu vuc nay se duoc thay bang redirect sang VNPay, MoMo, ZaloPay, chuyen khoan hoac cong the quoc te co credentials that.</p>
    </section>
    <aside class="card">
      <h2 style="margin-top:0;">Tong quan giao dich</h2>
      <div class="summary-list">
        <div class="summary-item"><span>Tien nguoi lon</span><strong>${formatCurrency(summary.pricing.baseAmount)}d</strong></div>
        <div class="summary-item"><span>Giam gia</span><strong>-${formatCurrency(summary.pricing.discountAmount)}d</strong></div>
        <div class="summary-item"><span>Thue & phi</span><strong>${formatCurrency(summary.pricing.taxAmount + summary.pricing.feeAmount)}d</strong></div>
        <div class="summary-item"><span>Thanh toan hom nay</span><strong>${formatCurrency(summary.pricing.payableNowAmount || summary.pricing.totalAmount)}d</strong></div>
        <div class="summary-item"><span>Con lai</span><strong>${formatCurrency(summary.pricing.remainingAmount || 0)}d</strong></div>
      </div>
      <div class="summary-total"><span>Tong gia tri booking</span><strong>${formatCurrency(summary.pricing.totalAmount)}d</strong></div>
      <div class="summary-list">
        <div class="summary-item"><span>Khach lien he</span><strong>${summary.booking.details.contactName}</strong></div>
        <div class="summary-item"><span>Email</span><strong>${summary.booking.details.contactEmail}</strong></div>
        <div class="summary-item"><span>So dien thoai</span><strong>${summary.booking.details.contactPhone}</strong></div>
        <div class="summary-item"><span>Quoc gia</span><strong>${summary.booking.details.contactCountry}</strong></div>
      </div>
    </aside>
  </div>
</body>
</html>`);
  } catch (error) {
    return res.status(500).send(`<h1>Khong the mo cong thanh toan</h1><pre>${error.message}</pre>`);
  }
};

exports.handleGatewayCallback = async (req, res) => {
  const transactionCode = normalizeText(req.params.transactionCode);
  const checkoutToken = normalizeText(req.body.token || req.query.token);
  const status = normalizeText(req.body.status || req.query.status).toLowerCase();
  const signature = normalizeText(req.body.signature || req.query.signature);
  const gatewayStatus = status === 'success'
    ? 'SUCCESS'
    : status === 'cancelled'
      ? 'CANCELLED'
      : status === 'expired'
        ? 'EXPIRED'
        : 'FAILED';

  if (!transactionCode || !checkoutToken || !status || !signature) {
    return res.status(400).json({ message: 'Callback thieu thong tin can thiet.' });
  }

  const expectedSignature = signGatewayPayload(transactionCode, status, checkoutToken);

  if (signature !== expectedSignature) {
    return res.status(400).json({ message: 'Chu ky callback khong hop le.' });
  }

  try {
    const summary = await buildTransactionSummaryByCode(transactionCode, checkoutToken);

    if (!summary) {
      return res.status(404).json({ message: 'Khong tim thay giao dich.' });
    }

    if (normalizeTransactionStatus(summary.transaction.status) !== 'PENDING') {
      return res.redirect(
        buildFrontendResultUrl(
          summary,
          normalizeTransactionStatus(summary.transaction.status) === 'SUCCESS' ? 'success' : 'failed',
        ),
      );
    }

    const connection = await db.getConnection();

    try {
      await connection.beginTransaction();

      if (gatewayStatus === 'SUCCESS') {
        const paymentState = buildSuccessfulPaymentState(
          summary.pricing.totalAmount,
          summary.payment.paymentPlan || summary.booking.paymentPlan || PAYMENT_PLANS.FULL,
        );

        await connection.query(
          `UPDATE payment_transactions
           SET status = 'SUCCESS', gateway_reference = ?, paid_amount = ?, remaining_amount = ?, completed_at = NOW(), updated_at = NOW()
           WHERE transaction_code = ?`,
          [
            `${summary.transaction.provider}-${Date.now()}`,
            paymentState.paidAmount,
            paymentState.remainingAmount,
            transactionCode,
          ],
        );

        await connection.query(
          `UPDATE payments
           SET status = 'SUCCESS', payment_plan = ?, paid_amount = ?, remaining_amount = ?, paid_at = NOW()
           WHERE id = ?`,
          [
            paymentState.paymentPlan,
            paymentState.paidAmount,
            paymentState.remainingAmount,
            summary.payment.id,
          ],
        );

        await connection.query(
          `UPDATE bookings
           SET
             status = ?,
             booking_status = ?,
             payment_status = ?,
             payment_plan = ?,
             paid_amount = ?,
             remaining_amount = ?,
             confirmed_by = NULL,
             confirmed_at = NULL
           WHERE id = ? AND COALESCE(NULLIF(booking_status, ''), status) IN ('PENDING', 'PENDING_PAYMENT', 'PAYMENT_FAILED', 'PENDING_CONFIRMATION', 'PAID')`,
          [
            paymentState.legacyStatus,
            paymentState.bookingStatus,
            paymentState.paymentStatus,
            paymentState.paymentPlan,
            paymentState.paidAmount,
            paymentState.remainingAmount,
            summary.booking.id,
          ],
        );

        await createBookingAuditLog(connection, summary.booking.id, 'PAYMENT_SUCCESS', {
          actorType: 'gateway',
          actorName: summary.transaction.provider,
          note: paymentState.remainingAmount > 0
            ? 'Da nhan tien dat coc, booking cho nhan vien xac nhan.'
            : 'Da nhan thanh toan day du, booking cho nhan vien xac nhan.',
          payload: {
            transactionCode,
            paymentPlan: paymentState.paymentPlan,
            paidAmount: paymentState.paidAmount,
            remainingAmount: paymentState.remainingAmount,
          },
        });
      } else {
        const failedState = buildFailedPaymentState();

        await connection.query(
          `UPDATE payment_transactions
           SET status = ?, completed_at = NOW(), updated_at = NOW()
           WHERE transaction_code = ?`,
          [gatewayStatus, transactionCode],
        );

        await connection.query(
          `UPDATE payments
           SET status = 'FAILED'
           WHERE id = ?`,
          [summary.payment.id],
        );

        await connection.query(
          `UPDATE bookings
           SET
             status = ?,
             booking_status = ?,
             payment_status = ?,
             paid_amount = 0,
             remaining_amount = total_price,
             confirmed_by = NULL,
             confirmed_at = NULL
           WHERE id = ? AND COALESCE(NULLIF(booking_status, ''), status) IN ('PENDING', 'PENDING_PAYMENT', 'PENDING_CONFIRMATION', 'PAID')`,
          [
            failedState.legacyStatus,
            failedState.bookingStatus,
            failedState.paymentStatus,
            summary.booking.id,
          ],
        );

        await createBookingAuditLog(connection, summary.booking.id, 'PAYMENT_FAILED', {
          actorType: 'gateway',
          actorName: summary.transaction.provider,
          note: 'Gateway callback tra ve trang thai thanh toan that bai.',
          payload: {
            transactionCode,
            gatewayStatus,
          },
        });
      }

      await connection.commit();
    } catch (error) {
      await connection.rollback();
      throw error;
    } finally {
      connection.release();
    }

    const freshSummary = await buildTransactionSummaryByCode(transactionCode, checkoutToken);

    if (gatewayStatus === 'SUCCESS') {
      await writeCheckoutEmailLog({
        type: 'payment-receipt',
        transactionCode,
        bookingCode: freshSummary.booking.code,
        email: freshSummary.booking.details.contactEmail,
        paymentMethod: freshSummary.payment.method,
        paymentPlan: freshSummary.payment.paymentPlan,
        paidAmount: freshSummary.payment.paidAmount,
        remainingAmount: freshSummary.payment.remainingAmount,
        totalAmount: freshSummary.pricing.totalAmount,
      });
    }

    return res.redirect(buildFrontendResultUrl(freshSummary, gatewayStatus === 'SUCCESS' ? 'success' : 'failed'));
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the xu ly callback thanh toan luc nay.',
      error: error.message,
    });
  }
};

exports.getCheckoutTransaction = async (req, res) => {
  const transactionCode = normalizeText(req.params.transactionCode);
  const userId = Number(req.user?.id || 0);
  const isAdminViewer = normalizeText(req.user?.role).toUpperCase() === 'ADMIN';

  if (!transactionCode) {
    return res.status(400).json({ message: 'Thieu thong tin giao dich.' });
  }

  try {
    const summary = await buildTransactionSummaryByCode(transactionCode, '', {
      userId: isAdminViewer ? 0 : userId,
    });

    if (!summary) {
      return res.status(404).json({ message: 'Khong tim thay giao dich.' });
    }

    return res.status(200).json({
      message: 'Lay tong quan checkout thanh cong.',
      summary,
    });
  } catch (error) {
    return res.status(500).json({
      message: 'Khong the lay tong quan checkout luc nay.',
      error: error.message,
    });
  }
};
