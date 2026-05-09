# Travel Booking WordPress + Backend API

Project này là website du lịch chạy theo mô hình hybrid:

- Frontend và CMS dùng WordPress
- Child theme chính là `travel-agency-modern`
- Backend nghiệp vụ được nhúng trong repo tại `backend-api`
- Booking, payment, review, auth sẽ đi qua backend API

## Cấu trúc chính

```text
travel_booking/
├─ backend-api/                         # Node.js / Express backend
│  ├─ config/
│  ├─ controllers/
│  ├─ database/
│  │  ├─ schema.sql
│  │  └─ seed.sql
│  ├─ routes/
│  ├─ .env.example
│  └─ server.js
├─ database/
│  └─ db_travel_booking.sql            # Database WordPress demo
└─ wp-content/
   └─ themes/
      ├─ travel-agency/                # Parent theme
      └─ travel-agency-modern/         # Child theme đang dùng
```

## Yêu cầu môi trường

- PHP 8.1+
- MySQL hoặc MariaDB
- XAMPP / Laragon / stack WordPress tương đương
- Node.js 18+
- Git

## 1. Clone code

```powershell
cd C:\xampp\htdocs
git clone https://github.com/TiensAnh/travel_booking_wordpress.git travel_booking
cd .\travel_booking
```

## 2. Tạo database WordPress

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS travel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Import database demo của WordPress:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root travel_booking < .\database\db_travel_booking.sql
```

## 3. Tạo `wp-config.php`

```powershell
Copy-Item .\wp-config-sample.php .\wp-config.php
```

Sửa lại thông tin DB trong `wp-config.php`:

```php
define( 'DB_NAME', 'travel_booking' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
```

## 4. Tạo database cho backend API

Backend dùng database riêng để quản lý:

- users
- tours
- bookings
- payments
- reviews

Tạo database:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS travel_booking_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Import schema và seed:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root travel_booking_api < .\backend-api\database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root travel_booking_api < .\backend-api\database\seed.sql
```

## 5. Cấu hình backend API

Tạo file môi trường:

```powershell
Copy-Item .\backend-api\.env.example .\backend-api\.env
```

File `.env.example` đã được chỉnh sẵn theo local hiện tại:

```env
PORT=5000
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=travel_booking_api
FRONTEND_URL=http://localhost/travel_booking
JWT_SECRET=secret123
```

Nếu máy bạn dùng MySQL có mật khẩu thì đổi `DB_PASS` cho đúng.

## 6. Cài package và chạy backend

```powershell
cd .\backend-api
npm install
npm run dev
```

Hoặc:

```powershell
npm start
```

Khi backend chạy đúng, bạn sẽ có API tại:

```text
http://127.0.0.1:5000/api
```

Health check:

```text
http://127.0.0.1:5000/api/health
```

## 7. Chạy WordPress

Bật `Apache` và `MySQL` trong XAMPP, sau đó mở:

```text
http://localhost/travel_booking
```

## 8. Việc cần làm trong WordPress admin

Sau khi đăng nhập `wp-admin`, kiểm tra các bước sau:

1. `Appearance > Themes`
   Kích hoạt `Travel Agency Modern`

2. `Tools > Sync Tours API`
   Bấm `Sync tours now`

3. `Settings > Permalinks`
   Bấm `Save Changes` một lần để refresh rewrite rules

4. Tạo hoặc kiểm tra page `Tài khoản`
   Chọn template: `Tài khoản backend`
   Gợi ý slug: `tai-khoan`

5. Kiểm tra page `Thanh toán`
   Chọn template: `Thanh toán tour`

## 9. Những gì đã được nối sang backend API

Theme WordPress hiện tại đã dùng backend cho các luồng sau:

- Đăng nhập backend
- Đăng ký backend
- Đồng bộ tour từ backend vào custom post type `tour`
- Tạo booking khi checkout
- Tạo payment request khi checkout
- Lấy review thật theo tour
- Gửi review từ trang tour
- Xem booking của người dùng ở trang `Tài khoản`
- Xem chi tiết booking
- Xem lịch sử thanh toán của booking
- Huỷ booking `PENDING`
- Xem review đã gửi của chính người dùng

## 10. Tài khoản demo backend

Từ `backend-api/database/seed.sql`, bạn có thể dùng nhanh:

- `user1@gmail.com` / `123456`
- `user2@gmail.com` / `123456`
- `user3@gmail.com` / `123456`

Lưu ý:

- Đây là dữ liệu demo
- Một số booking seed có thể chưa ở trạng thái `COMPLETED`, nên form review chỉ hiện khi backend trả về booking đủ điều kiện đánh giá

## 11. Nếu người khác clone repo này

Để người khác thấy được gần đúng giao diện và backend flow, họ cần:

1. Clone repo
2. Import `database/db_travel_booking.sql`
3. Import `backend-api/database/schema.sql`
4. Import `backend-api/database/seed.sql`
5. Chạy backend Node
6. Chạy WordPress
7. Sync tour từ `Tools > Sync Tours API`

Nếu bỏ qua backend thì:

- giao diện WordPress vẫn lên
- nhưng auth, booking, payment, review theo API sẽ không hoạt động đúng

## 12. Git lưu ý

Repo này nên commit:

- `README.md`
- `backend-api/`
- `database/db_travel_booking.sql`
- `wp-content/themes/travel-agency-modern/`
- `wp-content/themes/travel-agency/` nếu muốn clone về chạy ngay child theme

Không nên commit:

- `wp-config.php`
- `node_modules/`
- cache, log
- file nhạy cảm chứa secret thật

## 13. API admin của dự án cũ

Repo vẫn giữ nguyên backend admin-side trong `backend-api`, bao gồm:

- `admin-auth`
- admin bookings
- admin payments
- admin reviews
- `stats`
- `users`

Phần này chưa được render thành giao diện riêng trong WordPress vì WordPress đã có admin area của riêng nó. Tuy nhiên service Node vẫn còn đầy đủ để bạn mở rộng dashboard riêng sau này.
