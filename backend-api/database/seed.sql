-- =====================================================
-- SEED DATA - ADN Travel Booking System
-- =====================================================

-- ADMINS
INSERT INTO admins (username, email, password, role) VALUES
('ADN Super Admin', 'admin@adntravel.vn', 'admin123', 'admin'),
('Operations Admin', 'ops@adntravel.vn', 'ops12345', 'admin');

-- USERS
INSERT INTO users (name, email, phone, password, role) VALUES
('Dieu hanh vien',  'staff1@gmail.com',  '0900000001', '123456', 'STAFF'),
('Nhan vien',       'staff@gmail.com',   '0900000002', '123456', 'STAFF'),
('Nguyen Van A',    'user1@gmail.com',   '0900000003', '123456', 'USER'),
('Tran Van B',      'user2@gmail.com',   '0900000004', '123456', 'USER'),
('Le Thi C',        'user3@gmail.com',   '0900000005', '123456', 'USER'),
('Pham Thi D',      'user4@gmail.com',   '0900000006', '123456', 'USER'),
('Hoang Van E',     'user5@gmail.com',   '0900000007', '123456', 'USER'),
('Vo Thi F',        'user6@gmail.com',   '0900000008', '123456', 'USER');

-- TOURS (25 mẫu đa dạng)
INSERT INTO tours (
  title, description, price, location, duration, duration_text, max_people,
  status, transport, tagline, badge, season,
  departure_note, departure_schedule, meeting_point,
  created_by_admin_id, created_at
) VALUES

-- ĐÀ NẴNG
(
  'Tour Đà Nẵng - Hội An 3N2Đ',
  'Khám phá thành phố biển Đà Nẵng, tham quan phố cổ Hội An, check-in cầu Vàng trên đỉnh Bà Nà Hills.',
  2500000, 'Đà Nẵng', 3, '3 ngày 2 đêm', 20,
  'Active', 'Máy bay', 'Hành trình biển – cổ kính – huyền diệu', 'Bestseller', 'Quanh năm',
  'Tập trung tại sân bay Tân Sơn Nhất trước 5:30', 'Thứ 6 hàng tuần', 'Sân bay Tân Sơn Nhất',
  1, '2026-01-05 08:00:00'
),
(
  'Tour Đà Nẵng - Bà Nà Hills 2N1Đ',
  'Trải nghiệm thành phố Đà Nẵng về đêm, chinh phục Bà Nà Hills và vui chơi tại Sun World.',
  1800000, 'Đà Nẵng', 2, '2 ngày 1 đêm', 30,
  'Active', 'Ô tô', 'Thiên đường trên mây', 'Hot', 'Tháng 3 – 9',
  'Tập trung tại bến xe Miền Đông lúc 6:00', 'Thứ 7 hàng tuần', 'Bến xe Miền Đông',
  1, '2026-01-10 08:00:00'
),

-- PHÚ QUỐC
(
  'Tour Phú Quốc 4N3Đ - Nghỉ dưỡng',
  'Thiên đường đảo ngọc Phú Quốc: tắm biển, lặn ngắm san hô, thưởng thức hải sản tươi sống.',
  5500000, 'Phú Quốc', 4, '4 ngày 3 đêm', 15,
  'Active', 'Máy bay', 'Đảo ngọc giữa trời xanh', 'Luxury', 'Tháng 11 – 4',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 7:00', 'Thứ 2 và Thứ 5', 'Sân bay Tân Sơn Nhất',
  1, '2026-01-12 09:00:00'
),
(
  'Tour Phú Quốc 3N2Đ - Khám phá đảo',
  'Tour đảo hopping Phú Quốc: thăm đảo Hòn Thơm, lặn biển, câu cá, BBQ trên biển.',
  3800000, 'Phú Quốc', 3, '3 ngày 2 đêm', 20,
  'Active', 'Máy bay', 'Khám phá từng mảnh biển xanh', 'Popular', 'Tháng 11 – 4',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 8:00', 'Thứ 3 và Thứ 6', 'Sân bay Tân Sơn Nhất',
  1, '2026-01-15 08:00:00'
),

-- ĐÀ LẠT
(
  'Tour Đà Lạt 3N2Đ - Thành phố ngàn hoa',
  'Đà Lạt lãng mạn: vườn hoa, hồ Xuân Hương, thác Datanla, cà phê sáng trong sương mù.',
  2200000, 'Đà Lạt', 3, '3 ngày 2 đêm', 25,
  'Active', 'Xe khách giường nằm', 'Thành phố trong sương', 'Romantic', 'Quanh năm',
  'Tập trung tại bến xe Miền Đông lúc 19:00 (xe đêm)', 'Thứ 6 hàng tuần', 'Bến xe Miền Đông',
  2, '2026-01-18 10:00:00'
),
(
  'Tour Đà Lạt 2N1Đ - Săn mây cuối tuần',
  'Chuyến đi cuối tuần đến Đà Lạt: săn mây Langbiang, thưởng thức dâu tây, ăn bánh mì xíu mại.',
  1600000, 'Đà Lạt', 2, '2 ngày 1 đêm', 35,
  'Active', 'Xe khách giường nằm', 'Cuối tuần lên mây', 'Weekend', 'Quanh năm',
  'Tập trung tại bến xe Miền Đông lúc 20:00', 'Thứ 6 hàng tuần', 'Bến xe Miền Đông',
  2, '2026-01-20 08:00:00'
),

-- NHA TRANG
(
  'Tour Nha Trang 4N3Đ - Nghỉ dưỡng biển',
  'Nha Trang xanh trong: Vinpearl Land, lặn biển, tham quan Tháp Bà Ponagar, thả thuyền kayak.',
  4500000, 'Nha Trang', 4, '4 ngày 3 đêm', 20,
  'Active', 'Máy bay', 'Xanh ngắt đại dương', 'Bestseller', 'Tháng 2 – 9',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 6:30', 'Thứ 2 và Thứ 5', 'Sân bay Tân Sơn Nhất',
  1, '2026-01-22 09:00:00'
),
(
  'Tour Nha Trang 3N2Đ - Tour đảo 4 hòn',
  'Khám phá 4 hòn đảo Nha Trang: tắm biển, lặn ngắm san hô, buffet trên biển, karaoke thuyền.',
  3200000, 'Nha Trang', 3, '3 ngày 2 đêm', 25,
  'Active', 'Máy bay', 'Bốn đảo một hành trình', 'Popular', 'Tháng 2 – 9',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 7:00', 'Thứ 4 và Thứ 7', 'Sân bay Tân Sơn Nhất',
  1, '2026-01-25 08:00:00'
),

-- HẠ LONG
(
  'Tour Hạ Long 3N2Đ - Du thuyền 5 sao',
  'Vịnh Hạ Long huyền bí: nghỉ đêm trên du thuyền 5 sao, chèo kayak, khám phá hang động Thiên Cung.',
  6500000, 'Hạ Long', 3, '3 ngày 2 đêm', 12,
  'Active', 'Máy bay + Xe đưa đón', 'Đêm ngủ giữa kỳ quan', 'Luxury', 'Tháng 4 – 10',
  'Tập trung tại sân bay Nội Bài lúc 8:00', 'Thứ 2 và Thứ 5', 'Sân bay Nội Bài',
  1, '2026-02-01 08:00:00'
),
(
  'Tour Hạ Long 2N1Đ - Tàu tham quan',
  'Khám phá vịnh Hạ Long bằng tàu tham quan 1 ngày: thăm hang Sửng Sốt, đảo Ti Tốp, chèo kayak.',
  2800000, 'Hạ Long', 2, '2 ngày 1 đêm', 30,
  'Active', 'Xe giường nằm', 'Kỳ quan thiên nhiên thế giới', 'Hot', 'Tháng 4 – 10',
  'Tập trung tại bến xe Mỹ Đình lúc 18:00', 'Thứ 5 và Thứ 7', 'Bến xe Mỹ Đình',
  2, '2026-02-05 09:00:00'
),

-- HÀ NỘI
(
  'Tour Hà Nội 4N3Đ - Khám phá ngàn năm văn hiến',
  'Hà Nội lịch sử: Văn Miếu, Hồ Hoàn Kiếm, Lăng Bác, phố cổ 36 phố phường, thưởng thức phở Hà Nội.',
  3500000, 'Hà Nội', 4, '4 ngày 3 đêm', 20,
  'Active', 'Máy bay', 'Nghìn năm Thăng Long', 'Cultural', 'Quanh năm',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 5:30', 'Thứ 2 và Thứ 5', 'Sân bay Tân Sơn Nhất',
  1, '2026-02-08 08:00:00'
),

-- HỘI AN
(
  'Tour Hội An 3N2Đ - Phố cổ & biển An Bàng',
  'Hội An cổ kính: phố đèn lồng, làng gốm Thanh Hà, làng rau Trà Quế, thả đèn hoa đăng.',
  2300000, 'Hội An', 3, '3 ngày 2 đêm', 22,
  'Active', 'Máy bay', 'Ánh đèn lồng giữa phố cổ', 'Cultural', 'Quanh năm',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 6:00', 'Thứ 4 và Thứ 7', 'Sân bay Tân Sơn Nhất',
  2, '2026-02-10 08:00:00'
),

-- MŨI NÉ
(
  'Tour Mũi Né 2N1Đ - Đồi cát vàng',
  'Mũi Né hoang dã: trượt cát đồi Hồng, đồi Cát Bay, xem bình minh tại Bàu Trắng, ăn hải sản tươi.',
  1400000, 'Mũi Né - Phan Thiết', 2, '2 ngày 1 đêm', 30,
  'Active', 'Xe khách', 'Cát vàng – nắng gió miền Trung', 'Adventure', 'Tháng 11 – 5',
  'Tập trung tại bến xe Miền Đông lúc 7:00', 'Thứ 7 và Chủ nhật', 'Bến xe Miền Đông',
  2, '2026-02-12 09:00:00'
),
(
  'Tour Mũi Né 3N2Đ - Nghỉ dưỡng biển cát',
  'Tận hưởng Mũi Né với resort ven biển, trượt cát, kite surf, tham quan làng chài địa phương.',
  2600000, 'Mũi Né - Phan Thiết', 3, '3 ngày 2 đêm', 20,
  'Active', 'Xe khách', 'Resort cát và gió', 'Relaxing', 'Tháng 11 – 5',
  'Tập trung tại bến xe Miền Đông lúc 7:00', 'Thứ 6 hàng tuần', 'Bến xe Miền Đông',
  1, '2026-02-15 08:00:00'
),

-- CÔN ĐẢO
(
  'Tour Côn Đảo 4N3Đ - Thiên đường hoang sơ',
  'Côn Đảo nguyên sơ: lặn biển ngắm san hô, theo dõi rùa biển đẻ trứng, tham quan nhà tù Côn Đảo.',
  7500000, 'Côn Đảo', 4, '4 ngày 3 đêm', 12,
  'Active', 'Máy bay', 'Nơi biển xanh chưa ngủ', 'Eco', 'Tháng 3 – 9',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 8:00', 'Thứ 3 và Thứ 6', 'Sân bay Tân Sơn Nhất',
  1, '2026-02-18 08:00:00'
),

-- SÀI GÒN MIỀN TÂY
(
  'Tour Miền Tây 2N1Đ - Sông nước Cửu Long',
  'Khám phá miền Tây sông nước: chợ nổi Cái Răng, làng nghề, đờn ca tài tử, thưởng thức trái cây.',
  1200000, 'Cần Thơ - Tiền Giang', 2, '2 ngày 1 đêm', 35,
  'Active', 'Xe khách', 'Sông nước miền Tây', 'Cultural', 'Quanh năm',
  'Tập trung tại bến xe Miền Tây lúc 6:30', 'Thứ 7 và Chủ nhật', 'Bến xe Miền Tây',
  2, '2026-02-20 08:00:00'
),
(
  'Tour Miền Tây 3N2Đ - Cần Thơ & Châu Đốc',
  'Miền Tây đặc sắc: chợ nổi Cái Răng, Bà Chúa Xứ núi Sam, miếu Tây An, làng Chăm Châu Phong.',
  2100000, 'Cần Thơ - Châu Đốc', 3, '3 ngày 2 đêm', 28,
  'Active', 'Xe khách', 'Phù sa miền sông nước', 'Cultural', 'Quanh năm',
  'Tập trung tại bến xe Miền Tây lúc 6:00', 'Thứ 6 hàng tuần', 'Bến xe Miền Tây',
  2, '2026-02-22 09:00:00'
),

-- SẦM SƠN / THANH HÓA
(
  'Tour Sầm Sơn 3N2Đ - Biển Bắc Trung Bộ',
  'Biển Sầm Sơn: tắm biển đẹp, tham quan Hòn Trống Mái, thưởng thức hải sản đặc trưng miền Trung.',
  2000000, 'Sầm Sơn - Thanh Hóa', 3, '3 ngày 2 đêm', 30,
  'Active', 'Xe giường nằm', 'Sóng Bắc Trung Bộ', 'Beach', 'Tháng 4 – 9',
  'Tập trung tại bến xe Nước Ngầm lúc 19:00', 'Thứ 5 hàng tuần', 'Bến xe Nước Ngầm',
  2, '2026-02-25 08:00:00'
),

-- SAPA
(
  'Tour Sa Pa 4N3Đ - Thiên đường mây trắng',
  'Sa Pa hùng vĩ: chinh phục đỉnh Fansipan, thăm bản Cát Cát, ruộng bậc thang Mù Cang Chải, chợ tình.',
  4800000, 'Sa Pa - Lào Cai', 4, '4 ngày 3 đêm', 15,
  'Active', 'Tàu hỏa + Xe đưa đón', 'Nóc nhà Đông Dương', 'Adventure', 'Tháng 9 – 12, Tháng 3 – 5',
  'Tập trung tại ga Hà Nội lúc 21:00', 'Thứ 5 và Thứ 7', 'Ga Hà Nội',
  1, '2026-03-01 08:00:00'
),
(
  'Tour Sa Pa 2N1Đ - Leo Fansipan',
  'Chinh phục Fansipan 3.143m bằng cáp treo, ngắm mây từ đỉnh, thăm bản Cát Cát.',
  3200000, 'Sa Pa - Lào Cai', 2, '2 ngày 1 đêm', 20,
  'Active', 'Máy bay', 'Đỉnh cao Đông Dương', 'Adventure', 'Tháng 9 – 5',
  'Tập trung tại sân bay Nội Bài lúc 7:00', 'Thứ 6 và Chủ nhật', 'Sân bay Nội Bài',
  1, '2026-03-05 09:00:00'
),

-- HUẾ
(
  'Tour Huế 3N2Đ - Cố đô ngàn năm',
  'Huế cổ kính: thăm Đại Nội Huế, Lăng Tự Đức, Lăng Minh Mạng, thưởng thức ẩm thực cung đình.',
  2700000, 'Huế', 3, '3 ngày 2 đêm', 22,
  'Active', 'Máy bay', 'Di sản cung đình', 'Cultural', 'Tháng 2 – 4, Tháng 8 – 10',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 6:30', 'Thứ 3 và Thứ 6', 'Sân bay Tân Sơn Nhất',
  2, '2026-03-08 08:00:00'
),

-- NÚI BÀ ĐEN
(
  'Tour Núi Bà Đen 1N - Tây Ninh',
  'Cáp treo Núi Bà Đen Tây Ninh: đỉnh núi cao nhất Nam Bộ, tâm linh và thiên nhiên hùng vĩ.',
  800000, 'Tây Ninh', 1, '1 ngày', 40,
  'Active', 'Xe khách', 'Nóc nhà Nam Bộ', 'Local', 'Quanh năm',
  'Tập trung tại điểm xuất phát lúc 6:00', 'Hàng ngày', 'Quảng trường thành phố Hồ Chí Minh',
  2, '2026-03-10 07:00:00'
),

-- CÚC PHƯƠNG
(
  'Tour Cúc Phương 2N1Đ - Rừng nguyên sinh',
  'Vườn quốc gia Cúc Phương: trekking rừng nhiệt đới, thăm trung tâm cứu hộ linh trưởng, hang Người Xưa.',
  1900000, 'Cúc Phương - Ninh Bình', 2, '2 ngày 1 đêm', 20,
  'Active', 'Xe khách', 'Rừng già bí ẩn', 'Eco', 'Tháng 11 – 4',
  'Tập trung tại bến xe Mỹ Đình lúc 7:00', 'Thứ 7 hàng tuần', 'Bến xe Mỹ Đình',
  2, '2026-03-12 08:00:00'
),

-- TOUR STATUS DRAFT (để test filter)
(
  'Tour Quy Nhơn 3N2Đ - Eo Gió & Kỳ Co',
  'Quy Nhơn hoang sơ: Eo Gió, bãi tắm Kỳ Co, Bãi Xép, tháp Chăm Bánh Ít – vẻ đẹp còn nguyên sơ.',
  2900000, 'Quy Nhơn - Bình Định', 3, '3 ngày 2 đêm', 18,
  'Draft', 'Máy bay', 'Viên ngọc thô biển miền Trung', 'New', 'Tháng 3 – 9',
  'Tập trung tại sân bay Tân Sơn Nhất lúc 7:00', 'Thứ 2 và Thứ 5', 'Sân bay Tân Sơn Nhất',
  1, '2026-03-15 08:00:00'
),
(
  'Tour Ninh Bình 3N2Đ - Tràng An & Tam Cốc',
  'Ninh Bình hùng vĩ: Tràng An Bích Động, Tam Cốc, Cố đô Hoa Lư, chùa Bái Đính – di sản thiên nhiên thế giới.',
  2400000, 'Ninh Bình', 3, '3 ngày 2 đêm', 25,
  'Draft', 'Xe khách giường nằm', 'Hạ Long cạn – Việt Nam thu nhỏ', 'Coming soon', 'Tháng 4 – 10',
  'Tập trung tại bến xe Mỹ Đình lúc 18:00', 'Thứ 5 hàng tuần', 'Bến xe Mỹ Đình',
  2, '2026-03-18 09:00:00'
);

-- BOOKINGS (nhiều trạng thái để test)
INSERT INTO bookings (user_id, tour_id, travel_date, number_of_people, total_price, status, created_at) VALUES
(3, 1,  '2026-05-01', 2, 5000000,  'PENDING',   '2026-04-01 09:00:00'),
(4, 2,  '2026-05-10', 3, 5400000,  'CONFIRMED', '2026-04-02 10:00:00'),
(5, 3,  '2026-06-01', 2, 11000000, 'CONFIRMED', '2026-04-03 11:00:00'),
(6, 5,  '2026-05-15', 4, 8800000,  'COMPLETED', '2026-04-04 09:00:00'),
(7, 7,  '2026-07-01', 2, 9000000,  'PENDING',   '2026-04-05 10:00:00'),
(8, 9,  '2026-06-20', 2, 13000000, 'CONFIRMED', '2026-04-06 11:00:00'),
(3, 11, '2026-05-20', 3, 10500000, 'CANCELLED', '2026-04-07 09:00:00'),
(4, 13, '2026-06-05', 2, 4600000,  'COMPLETED', '2026-04-08 10:00:00'),
(5, 15, '2026-07-10', 2, 15000000, 'PENDING',   '2026-04-09 09:00:00'),
(6, 17, '2026-05-25', 4, 4800000,  'CONFIRMED', '2026-04-10 11:00:00');

-- BOOKING CUSTOMERS
INSERT INTO booking_customers (booking_id, full_name, gender, date_of_birth) VALUES
(1, 'Nguyen Van A',  'MALE',   '1995-01-01'),
(1, 'Le Thi B',      'FEMALE', '1997-05-10'),
(2, 'Tran Van B',    'MALE',   '1990-02-02'),
(2, 'Pham Thi C',    'FEMALE', '1992-03-03'),
(2, 'Hoang Van D',   'MALE',   '1988-04-04'),
(3, 'Le Thi C',      'FEMALE', '1993-06-15'),
(3, 'Nguyen Manh H', 'MALE',   '1991-08-20'),
(4, 'Pham Thi D',    'FEMALE', '1994-11-30'),
(4, 'Vo Minh K',     'MALE',   '1990-03-25'),
(4, 'Tran Thi L',    'FEMALE', '1996-07-12'),
(4, 'Le Van M',      'MALE',   '1988-09-18');

-- PAYMENTS
INSERT INTO payments (booking_id, amount, method, status, paid_at) VALUES
(1, 5000000,  'MOMO',          'PENDING', NULL),
(2, 5400000,  'VNPAY',         'SUCCESS', '2026-04-02 11:00:00'),
(3, 11000000, 'BANK_TRANSFER', 'SUCCESS', '2026-04-03 12:00:00'),
(4, 8800000,  'MOMO',          'SUCCESS', '2026-04-04 10:00:00'),
(6, 13000000, 'VNPAY',         'SUCCESS', '2026-04-06 12:00:00'),
(8, 4600000,  'CASH',          'SUCCESS', '2026-04-08 11:00:00'),
(10, 4800000, 'BANK_TRANSFER', 'SUCCESS', '2026-04-10 12:00:00');

-- REVIEWS
INSERT INTO reviews (booking_id, user_id, tour_id, rating, comment, status, created_at, updated_at) VALUES
(4, 6, 5, 4, 'Da Lat lang man, lich trinh nhe va huong dan vien nhiet tinh.', 'VISIBLE', '2026-05-19 10:00:00', '2026-05-19 10:00:00'),
(8, 4, 13, 5, 'Hoi An ban dem dep, den long dep va am thuc rat ngon.', 'VISIBLE', '2026-06-08 09:00:00', '2026-06-08 09:00:00');
