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
    transport VARCHAR(100),
    departure_note VARCHAR(255),
    tagline VARCHAR(255),
    badge VARCHAR(100),
    season VARCHAR(100),
    departure_schedule VARCHAR(255),
    meeting_point VARCHAR(255),
    curator_note TEXT,
    curator_name VARCHAR(255),
    included_items_json TEXT,
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
    status VARCHAR(20) DEFAULT 'PENDING',
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
