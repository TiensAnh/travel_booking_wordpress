const BOOKING_STATUSES = Object.freeze({
  PENDING_PAYMENT: 'PENDING_PAYMENT',
  PAYMENT_FAILED: 'PAYMENT_FAILED',
  PAID: 'PAID',
  PENDING_CONFIRMATION: 'PENDING_CONFIRMATION',
  CONFIRMED: 'CONFIRMED',
  COMPLETED: 'COMPLETED',
  CANCELLED: 'CANCELLED',
  REFUNDED: 'REFUNDED',
});

const BOOKING_PAYMENT_STATUSES = Object.freeze({
  PENDING: 'PENDING',
  PAID: 'PAID',
  PARTIALLY_PAID: 'PARTIALLY_PAID',
  FAILED: 'FAILED',
  REFUNDED: 'REFUNDED',
});

const PAYMENT_RECORD_STATUSES = Object.freeze({
  PENDING: 'PENDING',
  SUCCESS: 'SUCCESS',
  FAILED: 'FAILED',
  CANCELLED: 'CANCELLED',
  EXPIRED: 'EXPIRED',
  REFUNDED: 'REFUNDED',
});

const PAYMENT_PLANS = Object.freeze({
  FULL: 'FULL',
  DEPOSIT: 'DEPOSIT',
});

function roundCurrency(value) {
  return Math.max(0, Math.round(Number(value || 0)));
}

function normalizeText(value) {
  return typeof value === 'string' ? value.trim() : '';
}

function getDepositRate() {
  const configuredRate = Number(process.env.CHECKOUT_DEPOSIT_RATE || 0.3);

  if (!Number.isFinite(configuredRate) || configuredRate <= 0 || configuredRate >= 1) {
    return 0.3;
  }

  return configuredRate;
}

function normalizePaymentPlan(value) {
  const plan = normalizeText(String(value || '')).toUpperCase();
  return plan === PAYMENT_PLANS.DEPOSIT ? PAYMENT_PLANS.DEPOSIT : PAYMENT_PLANS.FULL;
}

function calculatePaymentPlanAmounts(totalAmount, paymentPlan) {
  const normalizedPlan = normalizePaymentPlan(paymentPlan);
  const normalizedTotal = roundCurrency(totalAmount);

  if (normalizedPlan === PAYMENT_PLANS.DEPOSIT) {
    const depositRate = getDepositRate();
    const paidAmount = Math.max(1, roundCurrency(normalizedTotal * depositRate));
    const remainingAmount = Math.max(0, normalizedTotal - paidAmount);

    return {
      paymentPlan: normalizedPlan,
      paidAmount,
      remainingAmount,
      paymentStatus: remainingAmount > 0
        ? BOOKING_PAYMENT_STATUSES.PARTIALLY_PAID
        : BOOKING_PAYMENT_STATUSES.PAID,
    };
  }

  return {
    paymentPlan: PAYMENT_PLANS.FULL,
    paidAmount: normalizedTotal,
    remainingAmount: 0,
    paymentStatus: BOOKING_PAYMENT_STATUSES.PAID,
  };
}

function buildSuccessfulPaymentState(totalAmount, paymentPlan) {
  const paymentAmounts = calculatePaymentPlanAmounts(totalAmount, paymentPlan);

  return {
    bookingStatus: BOOKING_STATUSES.PENDING_CONFIRMATION,
    legacyStatus: BOOKING_STATUSES.PENDING_CONFIRMATION,
    paymentStatus: paymentAmounts.paymentStatus,
    paymentPlan: paymentAmounts.paymentPlan,
    paidAmount: paymentAmounts.paidAmount,
    remainingAmount: paymentAmounts.remainingAmount,
    paymentRecordStatus: PAYMENT_RECORD_STATUSES.SUCCESS,
    transactionStatus: PAYMENT_RECORD_STATUSES.SUCCESS,
  };
}

function buildFailedPaymentState() {
  return {
    bookingStatus: BOOKING_STATUSES.PAYMENT_FAILED,
    legacyStatus: BOOKING_STATUSES.PAYMENT_FAILED,
    paymentStatus: BOOKING_PAYMENT_STATUSES.FAILED,
    paymentRecordStatus: PAYMENT_RECORD_STATUSES.FAILED,
  };
}

function isBookingAwaitingManualConfirmation(status) {
  const normalizedStatus = normalizeText(String(status || '')).toUpperCase();
  return normalizedStatus === BOOKING_STATUSES.PENDING_CONFIRMATION || normalizedStatus === BOOKING_STATUSES.PAID;
}

function isBookingConfirmable(status) {
  return isBookingAwaitingManualConfirmation(status);
}

function isBookingCancelable(status) {
  const normalizedStatus = normalizeText(String(status || '')).toUpperCase();

  return [
    BOOKING_STATUSES.PENDING_PAYMENT,
    BOOKING_STATUSES.PAYMENT_FAILED,
    BOOKING_STATUSES.PAID,
    BOOKING_STATUSES.PENDING_CONFIRMATION,
    BOOKING_STATUSES.CONFIRMED,
  ].includes(normalizedStatus);
}

function isBookingRefundable(paymentStatus, bookingStatus) {
  const normalizedPaymentStatus = normalizeText(String(paymentStatus || '')).toUpperCase();
  const normalizedBookingStatus = normalizeText(String(bookingStatus || '')).toUpperCase();

  if (
    normalizedPaymentStatus === BOOKING_PAYMENT_STATUSES.REFUNDED ||
    normalizedBookingStatus === BOOKING_STATUSES.REFUNDED
  ) {
    return false;
  }

  return [
    BOOKING_PAYMENT_STATUSES.PAID,
    BOOKING_PAYMENT_STATUSES.PARTIALLY_PAID,
  ].includes(normalizedPaymentStatus);
}

module.exports = {
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
  isBookingAwaitingManualConfirmation,
  isBookingConfirmable,
  isBookingCancelable,
  isBookingRefundable,
};
