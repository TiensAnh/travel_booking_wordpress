CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20) UNIQUE,
    password VARCHAR(255),
    role ENUM('USER', 'STAFF') NOT NULL DEFAULT 'USER',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2),
    location VARCHAR(255),
    duration INT,
    duration_text VARCHAR(100),
    max_people INT,
    status VARCHAR(20) DEFAULT 'Draft',
    image_url VARCHAR(500),
    gallery_images_json LONGTEXT,
    transport VARCHAR(100),
    departure_note VARCHAR(255),
    tagline VARCHAR(255),
    badge VARCHAR(100),
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    season VARCHAR(100),
    departure_schedule VARCHAR(255),
    departure_dates_json TEXT,
    meeting_point VARCHAR(255),
    curator_note TEXT,
    curator_name VARCHAR(255),
    included_items_json TEXT,
    excluded_items_json TEXT,
    promise_items_json TEXT,
    overview_cards_json TEXT,
    highlights_json TEXT,
    itinerary_json TEXT,
    created_by INT,
    created_by_admin_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(id)
);

CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    tour_id INT,
    travel_date DATE,
    number_of_people INT,
    total_price DECIMAL(10,2),
    status VARCHAR(30) DEFAULT 'PENDING_PAYMENT',
    booking_status VARCHAR(30) NOT NULL DEFAULT 'PENDING_PAYMENT',
    payment_status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL',
    confirmed_by VARCHAR(191) NULL,
    confirmed_at DATETIME NULL,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_receipt_sent_at DATETIME NULL,
    confirmation_sent_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tour_id) REFERENCES tours(id)
);

CREATE TABLE booking_customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT,
    full_name VARCHAR(255),
    gender VARCHAR(10),
    date_of_birth DATE,
    passport VARCHAR(50),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT,
    amount DECIMAL(10,2),
    method VARCHAR(50),
    status VARCHAR(20) DEFAULT 'PENDING',
    payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL',
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    receipt_sent_at DATETIME NULL,
    refunded_at DATETIME NULL,
    paid_at DATETIME NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL UNIQUE,
    user_id INT,
    tour_id INT,
    rating INT,
    comment TEXT,
    status VARCHAR(20) DEFAULT 'VISIBLE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tour_id) REFERENCES tours(id)
);

CREATE TABLE coupons (
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
);

CREATE TABLE payment_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL UNIQUE,
    booking_id INT NOT NULL,
    request_id VARCHAR(100) NOT NULL UNIQUE,
    provider VARCHAR(50) NOT NULL,
    payment_plan VARCHAR(20) NOT NULL DEFAULT 'FULL',
    transaction_code VARCHAR(100) NOT NULL UNIQUE,
    checkout_token VARCHAR(120) NOT NULL,
    coupon_code VARCHAR(50) NULL,
    base_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    return_url TEXT NULL,
    gateway_reference VARCHAR(120) NULL,
    gateway_payload_json TEXT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

CREATE TABLE booking_audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    actor_type VARCHAR(30) NOT NULL DEFAULT 'system',
    actor_id VARCHAR(100) NULL,
    actor_name VARCHAR(255) NULL,
    note TEXT NULL,
    payload_json TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
