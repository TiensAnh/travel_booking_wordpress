# ADN Travel Booking

## Thành viên thực hiện

- Nguyễn Văn Anh Đức - 23810310118
- Nguyễn Đồng Tiến Anh - 23810310080
- Đinh Trọng Nghĩa - 23810310119

## Giới thiệu website/hệ thống

ADN Travel Booking là website hỗ trợ giới thiệu và đặt tour du lịch. Hệ thống cho phép người dùng xem danh sách tour, xem chi tiết tour, chọn ngày khởi hành, gửi yêu cầu đặt tour, theo dõi tài khoản cá nhân và gửi đánh giá sau chuyến đi.

Dự án được xây theo mô hình hybrid:

- Frontend và CMS dùng WordPress
- Child theme đang sử dụng là `travel-agency-modern`
- Backend nghiệp vụ nằm trong thư mục `backend-api`
- Các luồng booking, payment, review, auth được xử lý qua backend API

## Demo truy cập

Khi máy chủ demo đang chạy, truy cập bằng tên miền:

- Trang chủ: https://nguyenvananhduc.id.vn/travel_booking/
- WordPress admin: https://nguyenvananhduc.id.vn/travel_booking/wp-admin

Nếu tự cấu hình và chạy trên máy local, truy cập bằng localhost:

- Trang chủ: http://localhost/travel_booking/
- WordPress admin: http://localhost/travel_booking/wp-admin

## Tài khoản demo

### WordPress admin

- Username: `tiensanh`
- Password: `123`

### Backend user demo

- `user1@gmail.com` / `123456`
- `user2@gmail.com` / `123456`
- `user3@gmail.com` / `123456`

## Phân công nhiệm vụ cụ thể

- Nguyễn Văn Anh Đức - Backend Developer
  Phụ trách backend API, xử lý dữ liệu tour, booking, review, payment và kết nối cơ sở dữ liệu MySQL.

- Nguyễn Đồng Tiến Anh - UI/UX Designer
  Phụ trách định hướng giao diện, bố cục hiển thị, card tour, trải nghiệm người dùng và phần trình bày website.

- Đinh Trọng Nghĩa - Frontend Developer
  Phụ trách tùy biến giao diện WordPress, xây dựng các template người dùng và tối ưu luồng thao tác từ xem tour đến thanh toán.

## Công nghệ sử dụng

- WordPress
- PHP
- HTML, CSS, JavaScript
- MySQL
- Node.js / Express
- Cloudflare Tunnel cho phần public demo

## Cấu trúc chính

```text
travel_booking/
|-- backend-api/                     # Node.js / Express backend
|   |-- config/
|   |-- controllers/
|   |-- database/
|   |   |-- schema.sql
|   |   `-- seed.sql
|   |-- routes/
|   |-- .env.example
|   `-- server.js
|-- database/
|   `-- db_travel_booking.sql        # Database WordPress demo
`-- wp-content/
    `-- themes/
        |-- travel-agency/           # Parent theme
        `-- travel-agency-modern/    # Child theme đang dùng
```

## Hướng dẫn cài đặt

### 1. Clone source code

```powershell
cd C:\xampp\htdocs
git clone https://github.com/TiensAnh/travel_booking_wordpress.git travel_booking
cd .\travel_booking
```

### 2. Tạo database WordPress

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS travel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root travel_booking < .\database\db_travel_booking.sql
```

### 3. Tạo `wp-config.php`

```powershell
Copy-Item .\wp-config-sample.php .\wp-config.php
```

Cấu hình database trong `wp-config.php`:

```php
define( 'DB_NAME', 'travel_booking' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
```

### 4. Tạo database cho backend API

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS travel_booking_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root travel_booking_api < .\backend-api\database\schema.sql
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root travel_booking_api < .\backend-api\database\seed.sql
```

### 5. Cấu hình backend API

```powershell
Copy-Item .\backend-api\.env.example .\backend-api\.env
```

Mẫu `.env` local:

```env
PORT=5000
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=travel_booking_api
FRONTEND_URL=http://localhost/travel_booking
JWT_SECRET=replace-with-a-strong-random-secret
```

Nếu MySQL máy bạn có mật khẩu thì đổi `DB_PASS` cho đúng.

## Hướng dẫn chạy project

Lưu ý: các lệnh dưới đây nên chạy bằng PowerShell. Nếu dùng CMD, một số lệnh như `Copy-Item` sẽ không hoạt động.

### 1. Chạy backend API

```powershell
cd .\backend-api
npm install
npm run dev
```

Hoặc:

```powershell
npm start
```

Khi backend chạy đúng:

- API base: `http://127.0.0.1:5000/api`
- Health check: `http://127.0.0.1:5000/api/health`

### 2. Chạy WordPress local

Bật `Apache` và `MySQL` trong XAMPP, sau đó mở:

- `http://localhost/travel_booking/`
- `http://localhost/travel_booking/wp-admin`

### 3. Thiết lập trong WordPress admin

Sau khi đăng nhập `wp-admin`, kiểm tra các bước sau:

1. `Appearance > Themes`
   Kích hoạt `Travel Agency Modern`.

2. `Tools > Sync Tours API`
   Bấm `Sync tours now`.

3. `Settings > Permalinks`
   Bấm `Save Changes` một lần để refresh rewrite rules.

4. Kiểm tra page `Tài khoản`
   Template nên là template tài khoản của theme.
   Slug nên là `tai-khoan`.

5. Kiểm tra page `Thanh toán`
   Template nên là `Thanh toán tour`.

## Những chức năng đã kết nối với backend API

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
- Hủy booking `PENDING`
- Xem review đã gửi của chính người dùng

## Hình ảnh minh họa hệ thống

### Giao diện trang chủ / tour

![Trang chủ](./wp-content/uploads/2026/05/Trang%20chu%20cua%20user.jpg)

### Giao diện chi tiết tour

![Chi tiết tour](./wp-content/uploads/2026/05/trang%20chi%20tiet%20tour.jpg)

### Giao diện dashboard quản trị

![Dashboard quản trị](./wp-content/uploads/2026/05/trang%20dashboard.jpg)

## Link video demo

- Video demo: https://drive.google.com/drive/folders/1PqPbqDB3gmVdhnmw5p1bdv-TYq7igSSn?usp=sharing

## Link online đã deploy

- Frontend: https://nguyenvananhduc.id.vn/travel_booking/
- WordPress admin: https://nguyenvananhduc.id.vn/travel_booking/wp-admin

## Public demo bằng tên miền

Bản demo hiện được public qua Cloudflare Tunnel. Để bản demo public hoạt động, máy local phải đang bật:

- Apache
- MySQL
- backend `npm run dev`
- `cloudflared tunnel run travel-booking`

Nếu các dịch vụ local tắt, tên miền vẫn tồn tại nhưng website/demo sẽ không phản hồi đúng.

## Nếu người khác clone repo này

Để người khác chạy lại gần đúng toàn bộ flow của dự án, cần:

1. Clone repo
2. Import `database/db_travel_booking.sql`
3. Import `backend-api/database/schema.sql`
4. Import `backend-api/database/seed.sql`
5. Chạy backend Node.js
6. Chạy WordPress
7. Sync tour từ `Tools > Sync Tours API`

Nếu bỏ qua backend thì giao diện WordPress vẫn có thể mở, nhưng các phần auth, booking, payment, review và tài khoản sẽ không hoạt động đúng.

## Lưu ý khi commit

Nên commit:

- `README.md`
- `backend-api/`
- `database/db_travel_booking.sql`
- `wp-content/themes/travel-agency-modern/`
- `wp-content/themes/travel-agency/` nếu muốn clone về chạy ngay child theme

Không nên commit:

- `wp-config.php`
- `node_modules/`
- cache, log
- file chứa secret thật