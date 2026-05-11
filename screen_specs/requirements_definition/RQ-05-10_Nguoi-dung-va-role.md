# RQ-05-10_Nguoi-dung-va-role

ユーザー: Nguyen Kien  
作成日時: 8 tháng 5, 2026 9:43  
最終更新日時: 9 tháng 5, 2026 (v1.4 — danh mục chỉ UserType; RL gộp / chuyển mục lược bỏ)

# Danh sách người dùng · Role (利用者・ロール一覧)

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001  
> **Phiên bản**: v1.4  
> 

## Ánh xạ kỹ thuật EC-CUBE 4.2 (bắt buộc đọc)

Trên **source core EC-CUBE**, Symfony Security chỉ dùng **hai role cố định**:

| Symfony role | Entity / ngữ cảnh | Ghi chú |
| --- | --- | --- |
| **`ROLE_USER`** | `Eccube\Entity\Customer` (hội viên đăng nhập storefront) | `Customer::getRoles()` luôn trả về `['ROLE_USER']`. Không có thêm role Symfony nào cho khách. |
| **`ROLE_ADMIN`** | `Eccube\Entity\Member` (tài khoản đăng nhập khu `/admin`) | `Member::getRoles()` luôn trả về `['ROLE_ADMIN']`. Mọi quản trị viên đều cùng role Symfony này. |

**Phân quyền admin chi tiết** (không tạo thêm Symfony role): master **`mtb_authority`** (vd. システム管理者 / 店舗オーナー) + bảng **`dtb_authority_role`** (pattern URL **từ chối** truy cập) + `AuthorityVoter`. Đây là **cấp quyền vận hành**, không phải “role” thứ ba kiểu `ROLE_*`.

**Batch / cron (UT-04):** chạy `bin/console` trong ngữ cảnh CLI — **không** đăng nhập `Customer` hay `Member`; không gán `ROLE_*` cho “System” trong HTTP.

**Kết luận:** Bảng **Danh sách chủ thể** bên dưới chỉ còn **UserType** (bốn dòng). Không liệt kê thêm hàng “Role nghiệp vụ” riêng — các mã **RL-01**, **RL-02** trước đây được **gộp** vào UT-02 / UT-03 và đồng thời ghi nhận ở mục **Hạng mục đã lược bỏ** để truy vết. Tránh nhầm với danh sách dài **AuthorityRole** (nhiều rule URL) trên màn 権限管理.

---

### Danh sách chủ thể (UserType — đối chiếu triển khai EC-CUBE)

| No | ID | Loại (種別) | Tên (名称) | Định nghĩa / Trách nhiệm (定義/責務) | Quan hệ (関連) | Căn cứ (根拠) | Ghi chú (備考) |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | UT-01 | UserType | Khách vãng lai (Guest) | Người dùng chưa đăng ký tài khoản, có thể duyệt sản phẩm và sử dụng Chatbot. Cố định tầng người dùng trước khi chuyển đổi thành Member. | Không gán Symfony role (ẩn danh) | RQ-01 | Chuyển sang Member khi đăng ký. Chatbot: tính năng/plugin ngoài core nếu triển khai. |
| 2 | UT-02 | UserType | Hội viên (Member) | Người dùng đã đăng ký tài khoản: **mua hàng**, **quản lý đăng ký định kỳ** (subscription), **tải biên lai** — trách nhiệm phía người tiêu dùng (trước đây mô tả gọn là “Buyer”). Cố định chủ thể chính của luồng mua hàng. | **Kỹ thuật:** `Eccube\Entity\Customer` + Symfony **`ROLE_USER`** | RQ-01 | Đăng ký qua Form; SNS Login nếu có plugin. Subscription Customize gắn `Customer`. Không có `ROLE_BUYER` trong core. |
| 3 | UT-03 | UserType | Quản trị viên (Admin) | Nhân viên quản lý: sản phẩm, đơn hàng, khách hàng, cấu hình shop, giám sát — **theo phạm vi** `Authority` + deny URL (trước đây gọi gọn “SystemAdmin”). | **Kỹ thuật:** `Eccube\Entity\Member` + Symfony **`ROLE_ADMIN`** + `mtb_authority` / `dtb_authority_role` | RQ-01 | Mọi admin cùng `ROLE_ADMIN`; không có `ROLE_SYSTEM_ADMIN`. Tài khoản đầu / システム管理者: quyền rộng nhất theo cấu hình. |
| 4 | UT-04 | UserType | Hệ thống (System) | Chủ thể tự động thực thi xử lý batch theo lịch trình. Cố định đơn vị trách nhiệm cho các xử lý không có người thao tác. | Không gán `ROLE_USER` / `ROLE_ADMIN` trong HTTP | RQ-01 | Cron / `bin/console`; không phải user DB kiểu Member/Customer cho request đó. |

### Hạng mục đã lược bỏ (省略項目)

Các dòng **Role** tách riêng dưới đây **không còn** nằm trong bảng chính (đã gộp nội dung vào UT-02 / UT-03 và mục ánh xạ kỹ thuật phía trên).

| ID (đã lược khỏi bảng chính) | Tên cũ | Lý do |
| --- | --- | --- |
| RL-01 | Người mua hàng (Buyer) | Trùng với trách nhiệm **UT-02**; trên EC-CUBE không có entity role riêng — chỉ **`ROLE_USER`** (`Customer`). |
| RL-02 | Quản trị viên hệ thống (SystemAdmin) | Trùng với trách nhiệm **UT-03**; trên EC-CUBE không có **`ROLE_SYSTEM_ADMIN`** — chỉ **`ROLE_ADMIN`** + **Authority** / deny URL. |
