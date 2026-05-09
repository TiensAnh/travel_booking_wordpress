const fs = require('fs');
const path = require('path');
const multer = require('multer');

const TOUR_UPLOAD_DIR = path.join(__dirname, '..', 'public', 'uploads', 'tours');
const ALLOWED_MIME_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

fs.mkdirSync(TOUR_UPLOAD_DIR, { recursive: true });

function sanitizeFileName(fileName = 'tour-image') {
  const extension = path.extname(fileName).toLowerCase();
  const baseName = path.basename(fileName, extension);
  const normalizedBaseName = baseName
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-zA-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase();

  return {
    baseName: normalizedBaseName || 'tour-image',
    extension: extension || '.jpg',
  };
}

const storage = multer.diskStorage({
  destination: (req, file, callback) => {
    callback(null, TOUR_UPLOAD_DIR);
  },
  filename: (req, file, callback) => {
    const { baseName, extension } = sanitizeFileName(file.originalname);
    callback(null, `${Date.now()}-${baseName}${extension}`);
  },
});

function fileFilter(req, file, callback) {
  if (ALLOWED_MIME_TYPES.has(file.mimetype)) {
    callback(null, true);
    return;
  }

  const error = new Error('Chi chap nhan file JPG, PNG hoac WebP.');
  error.statusCode = 400;
  callback(error);
}

const uploadTourImage = multer({
  storage,
  limits: {
    fileSize: 5 * 1024 * 1024,
  },
  fileFilter,
}).single('image');

module.exports = {
  uploadTourImage,
  TOUR_UPLOAD_DIR,
};
