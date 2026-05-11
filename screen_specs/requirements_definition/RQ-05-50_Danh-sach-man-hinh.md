# RQ-05-50_Danh-sach-man-hinh

ユーザー: Nguyen Kien
作成日時: 8 tháng 5, 2026 9:50
最終更新日時: 11 tháng 5, 2026 (v1.3 — đối chiếu route/controller/twig repo; sửa mô tả sai; thêm cột Trạng thái vs source)

# **Danh sách màn hình (画面一覧)**

> **Dự án**: Website EC thực phẩm chức năng — PRJ-SABURI-001 **Phiên bản**: v1.3
> 

## Đối chiếu source

- **Frontend core:** `src/Eccube/Controller/` (ProductController, CartController, ShoppingController, Mypage/*) + template `src/Eccube/Resource/template/default/`.
- **Customize subscription:** `app/Customize/Controller/Admin/SubscriptionController.php`, `app/Customize/Form/Extension/ShoppingOrderTypeExtension.php`, `app/template/admin/Subscription/*.twig`, `app/template/admin/Customer/{index,edit}.twig` (override + card subscription), `app/Customize/EventSubscriber/Admin/CustomerSubscriptionViewSubscriber.php`.
- **Không có** trong repo: Chatbot (F-015), MyPage subscription route, Dashboard subscription admin, màn Cron-job log riêng.

---

## **Frontend — Màn hình Client**

| No | ID màn hình | Tên màn hình | Tổng quan | Người dùng | F-ID | Phân loại phạm vi | Thiết bị | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | SCR-001 | Trang chủ (Top) | Hero visual, concept, điều hướng danh mục | Guest / Member | — | Đối tượng lần này | PC / Tablet / SP | Template EC-CUBE; "triệu chứng" = cấu hình danh mục, không có entity đặc biệt. | Đã có (core) |
| 2 | SCR-002 | Danh sách sản phẩm | Bộ lọc danh mục, phân trang | Guest / Member | F-001 | Đối tượng lần này | PC / Tablet / SP | Core `product_list`. | Đã có (core) |
| 3 | SCR-003 | Chi tiết sản phẩm | Xem SP; UI Mua lẻ / Mua định kỳ. Chọn chu kỳ giao. | Guest / Member | F-002 | Đối tượng lần này | PC / Tablet / SP | Customize: `ShoppingOrderTypeExtension` thêm checkbox + dropdown chu kỳ **trên form checkout** (không trên trang chi tiết SP). Chu kỳ: `test_10m`, `weekly_1`, `monthly_1`, `monthly_3` — **không phải** 30/60/90 ngày cố định như v1.2. | Một phần |
| 4 | SCR-004 | Giỏ hàng (Cart) | Xem/cập nhật/xóa SP | Member | F-004 | Đối tượng lần này | PC / Tablet / SP | Core `cart`. EC-CUBE cho phép guest dùng giỏ — RQ ghi "Member" là chặt hơn core. | Đã có (core) |
| 5 | SCR-005 | Checkout (Thanh toán) | Địa chỉ → thanh toán. Định kỳ: hiển thị điều kiện hủy. | Member | F-005 | Đối tượng lần này | PC / Tablet / SP | Core `shopping` + Customize `ShoppingOrderTypeExtension` + `GmoDirectCreditCard`. Combini/PayPay: **không** trong Customize — tuỳ plugin cài thêm. | Một phần |
| 6 | SCR-006 | Hoàn thành đơn hàng | Xác nhận thành công + mã đơn | Member | — | Đối tượng lần này | PC / Tablet / SP | Core `shopping_complete`. `SubscriptionShoppingCompleteSubscriber` tạo subscription nếu có payload. | Đã có |
| 7 | SCR-007 | Đăng nhập / Đăng ký | Email+password. Xác thực email. | Guest | F-016, F-017 | Đối tượng lần này | PC / Tablet / SP | Core EC-CUBE. Google/LINE OAuth (F-018): **không** trong repo — cần plugin. | Một phần |
| 8 | SCR-008 | My Page — Tổng hợp | Profile, đơn hàng, địa chỉ, rút lui. **Subscription: không có route MyPage trong Customize**. | Member | F-027, F-028 | Đối tượng lần này | PC / Tablet / SP | Core My Page (`mypage`, `mypage_history`, `mypage_change`, `mypage_delivery`, `mypage_withdraw`). **F-008–F-012** (xem/skip/pause/cancel subscription từ MyPage): **chưa có** controller/twig. | Một phần |

## **Frontend — Trang tĩnh CMS**

| No | ID màn hình | Tên màn hình | Tổng quan | Người dùng | F-ID | Phân loại phạm vi | Thiết bị | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 9 | SCR-009 | Tokushouhou (特商法表記) | Thông tin giao dịch đặc định | Guest / Member | F-024 | Đối tượng lần này | PC / Tablet / SP | Core CMS — nội dung do NB cung cấp. | Đã có (core) |
| 10 | SCR-010 | Privacy Policy | Chính sách bảo mật | Guest / Member | F-024 | Đối tượng lần này | PC / Tablet / SP | Core CMS. | Đã có (core) |
| 11 | SCR-011 | About Us | Giới thiệu thương hiệu | Guest / Member | F-024 | Đối tượng lần này | PC / Tablet / SP | Core CMS. | Đã có (core) |
| 12 | SCR-012 | FAQ | Câu hỏi thường gặp | Guest / Member | F-024 | Đối tượng lần này | PC / Tablet / SP | Core CMS. | Đã có (core) |

## **Frontend — Component đặc biệt**

| No | ID màn hình | Tên màn hình | Tổng quan | Người dùng | F-ID | Phân loại phạm vi | Thiết bị | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 13 | SCR-013 | AI Chatbot Widget | Triệu chứng → đề xuất SP + disclaimer Yakkihou | Guest / Member | F-015 | Đối tượng lần này | PC / Tablet / SP | **Không có** code/service/twig trong repo. | Chưa |
| 14 | SCR-014 | Chuyển đổi ngôn ngữ | Toggle JA ↔ EN | Guest / Member | F-020 | Đối tượng lần này | PC / Tablet / SP | Customize có `messages.ja.yaml` / `messages.en.yaml` cho nhãn admin subscription; toggle component front **chưa thấy**. | Một phần |

## **Backend — Màn hình quản trị (Admin)**

| No | ID màn hình | Tên màn hình | Tổng quan | Người dùng | F-ID | Phân loại phạm vi | Thiết bị | Ghi chú | Trạng thái vs source |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 15 | SCR-A01 | Dashboard | v1.2: "Churn Rate, biểu đồ subscription". **Không có** dashboard subscription trong Customize; core EC-CUBE dashboard hiện chỉ hiển thị đơn hàng/doanh thu chuẩn. | Admin | F-023 | Đối tượng lần này | PC | Phase 2. | Chưa |
| 16 | SCR-A02 | Quản lý sản phẩm | CRUD sản phẩm. Core admin (`admin_product`). "Giá định kỳ riêng / Custom Field tag triệu chứng": **không** có mở rộng trong Customize. | Admin | F-003 | Đối tượng lần này | PC | EC-CUBE chuẩn; phần mở rộng RQ chưa triển khai. | Một phần |
| 17 | SCR-A03 | Quản lý đơn hàng | Danh sách đơn, trạng thái, tracking. Core admin (`admin_order`). "Filter đơn lẻ/định kỳ, hiển thị số kỳ": **không** trong Customize. | Admin | F-021 | Đối tượng lần này | PC | EC-CUBE chuẩn. | Một phần |
| 18 | SCR-A04a | Quản lý Subscription — Danh sách | Bảng subscription: ID, khách (link), trạng thái, plan × interval, next billing, retry/max, tổng tiền, link chi tiết. Form lọc GET (status, next_from/to, retry_count, customer_id). Banner liên kết khách khi lọc theo `customer_id`. | Admin | — | Đối tượng lần này | PC | `admin_subscription_index` → `@admin/Subscription/index.twig`. Menu: 受注管理 → Subscription. | Đã có |
| 19 | SCR-A04b | Quản lý Subscription — Chi tiết | Metadata subscription, GMO rút gọn, line items snapshot, lịch sử billing rows (status / scheduled / executed / link order), event log gần đây, nút **Retry charge** / **Force bill** / **Hủy** (CSRF, confirm). | Admin | — | Đối tượng lần này | PC | `admin_subscription_detail` → `@admin/Subscription/detail.twig`. Charge chỉ khi `SUBSCRIPTION_ENABLED=1` + trạng thái `active` hoặc `past_due`. | Đã có |
| 20 | SCR-A05a | Quản lý khách hàng — Danh sách | Core admin customer + **cột đếm subscription** (link sang danh sách subscription đã lọc `?customer_id=`). | Admin | F-022 | Đối tượng lần này | PC | Override `app/template/admin/Customer/index.twig` + `CustomerSubscriptionViewSubscriber`. | Đã có (phần subscription) |
| 21 | SCR-A05b | Quản lý khách hàng — Chỉnh sửa | Core admin edit + **card "Subscription"**: bảng rút gọn subscription của khách (status, plan, next billing, tổng, link chi tiết + danh sách). | Admin | F-022 | Đối tượng lần này | PC | Override `app/template/admin/Customer/edit.twig`. | Đã có (phần subscription) |
| 22 | SCR-A06 | Log Cron-job (riêng) | v1.2: "Màn hình mới — xem lịch sử batch". **Không có** màn admin riêng cho cron log. Event log subscription xem được trên **SCR-A04b** (detail). Log hệ thống ghi file (`var/log`). | Admin | F-013, F-014 | Đối tượng lần này | PC | Chưa triển khai riêng. | Chưa |

---

### **Hạng mục đã lược bỏ / điều chỉnh so với bản v1.2**

| Nội dung cũ (v1.2) | Lý do |
| --- | --- |
| SCR-003: chu kỳ "30/60/90 ngày" | Code dùng `weekly_1`, `monthly_1`, `monthly_3`, `test_10m`. |
| SCR-003: "UI trên chi tiết SP" | Customize đặt toggle/dropdown **trên form checkout** (ShoppingOrderTypeExtension), không trên trang chi tiết. |
| SCR-007: F-018 Google/LINE | Không có trong repo — cần plugin. |
| SCR-008: "tạo gói mới / sửa gói / skip / pause / hủy" (F-008–F-012) | **Không** có controller/route MyPage subscription trong Customize. |
| SCR-A01: Dashboard subscription | Không có trong Customize — Phase 2. |
| SCR-A04 "một màn hình" + "lịch sử Skip" | Code có **hai** trang (index + detail); skip không tồn tại (Phase 2). |
| SCR-A05 "nhãn CRM, thống kê lý do hủy" | Không có trong code; nhưng có card subscription + cột đếm. Tách thành A05a (index) + A05b (edit). |
| SCR-A06 "Màn hình mới log Cron-job" | Không có màn riêng; event log nằm trong chi tiết subscription (A04b). |
| Thiếu: SCR-A04b, SCR-A05a/b | Thêm mới — khớp template override và route thực tế. |
