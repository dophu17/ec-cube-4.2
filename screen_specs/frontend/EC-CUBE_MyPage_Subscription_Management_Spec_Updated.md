# EC-CUBE My Page — Quản Lý Subscription (Spec Updated)

> Phiên bản lean spec cho AI coding (giữ nguyên sections 3 và 7 theo yêu cầu).

---

## 1. Tổng Quan

**Mục đích:**
Màn hình cho phép khách hàng quản lý toàn bộ các gói 定期購入 (subscription / mua hàng định kỳ).

**User có thể:**

- Xem danh sách subscription đang sử dụng
- Xem subscription đã huỷ
- Kiểm tra chu kỳ giao hàng
- Kiểm tra ngày giao hàng tiếp theo
- Kiểm tra ngày thanh toán tiếp theo
- Kiểm tra giá trị đơn hàng
- Kiểm tra địa chỉ nhận hàng
- Kiểm tra phương thức thanh toán
- Xem chi tiết subscription
- Đăng ký thêm subscription mới

**Vai trò trong hệ thống:**
`Đăng nhập → My Page → Quản lý Subscription → Chi tiết Subscription`

**User:**

- Khách hàng đã đăng nhập
- Guest: Không
- Admin: Ngoài phạm vi

---

## 2. Phạm Vi

### ✅ Trong phạm vi

- Danh sách subscription
- Tab đang hoạt động / đã huỷ
- Card subscription
- Nút xem chi tiết
- CTA tạo subscription mới
- Responsive desktop / mobile
- Khối FAQ
- Khối thông báo subscription
- Kiểm tra quyền sở hữu
- Hiển thị cảnh báo thanh toán / địa chỉ

### ❌ Ngoài phạm vi

- Luồng checkout
- Thử lại thanh toán (payment retry)
- Quản lý Admin
- Hệ thống gợi ý sản phẩm
- Luồng áp dụng coupon
- Modal chỉnh sửa subscription nội bộ
- Chi tiết chỉnh sửa phương thức thanh toán

---

## 3. Cấu Trúc Màn Hình

```
Màn hình Quản lý Subscription
├── Header
├── Side Menu My Page
├── Tab điều hướng Subscription
│   ├── Tab Subscription đang hoạt động
│   └── Tab Subscription đã huỷ
├── Danh sách Subscription
│   └── Card Subscription
├── CTA Subscription mới
├── Khối thông báo Subscription
├── Nút FAQ
└── Footer
```

---

## 4. Tham Số

| Tên | Kiểu | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `tab` | string | không | `active` | `active` / `cancelled` / `paused` / `past_due` |
| `page` | integer | không | `1` | Phân trang (`pageno` theo convention EC-CUBE) |
| `limit` | integer | không | `eccube_search_pmax` | Số mục mỗi trang (config EC-CUBE) |
| `customer_id` | integer | có (tự động) | - | ID khách hàng đã đăng nhập (từ session, không phải tham số URL) |
| `subscription_id` | integer | không | - | ID Subscription cho route chi tiết |
| `sort` | string | không | `next_billing_asc` | Điều kiện sắp xếp (map tới `dtb_subscription.next_billing_at`) |

### Mapping Tab → Trạng thái

| Tab | Các trạng thái Subscription bao gồm |
| --- | --- |
| `active` | `active`, `pending_activation`, `past_due` |
| `paused` | `paused` |
| `cancelled` | `cancelled`, `expired` |

---

## 5. Nguồn Dữ Liệu

### 5.1 Bảng chuẩn EC-CUBE

| Bảng | Mô tả | Sử dụng |
| --- | --- | --- |
| `dtb_customer` | Master khách hàng | Kiểm tra quyền sở hữu |
| `dtb_customer_address` | Địa chỉ khách hàng | Tham chiếu địa chỉ giao hàng |
| `dtb_order` | Master đơn hàng | Đơn hàng gốc / đơn hàng định kỳ tự tạo |
| `dtb_order_item` | Mục đơn hàng | Snapshot sản phẩm subscription |
| `dtb_shipping` | Vận chuyển | Địa chỉ giao hàng / thông tin giao hàng |
| `dtb_payment` | Phương thức thanh toán | Nhãn thanh toán |
| `dtb_delivery` | Phương thức giao hàng | Hiển thị phương thức giao hàng / chu kỳ nếu áp dụng |
| `dtb_product` | Master sản phẩm | Tên / hình ảnh / trạng thái sản phẩm |
| `dtb_product_class` | SKU / class sản phẩm | Giá / tồn kho / mã sản phẩm |
| `dtb_product_image` | Hình ảnh sản phẩm | Hình ảnh card subscription |
| `mtb_order_status` | Trạng thái đơn hàng | Hiển thị trạng thái đơn hàng liên quan |
| `mtb_pref` | Master tỉnh/thành | Hiển thị địa chỉ |

> **Lưu ý:**
> - Dự án sử dụng **Custom Subscription** (`app/Customize/`), **KHÔNG** dùng EccubePaymentLite42 Regular plugin.
> - EccubePaymentLite42 plugin có tính năng "定期購入 (Regular)" riêng nhưng không sử dụng cho subscription của dự án này.
> - Không sửa trực tiếp bảng core.

### 5.2 Bảng Plugin (Chỉ tham chiếu)

| Bảng / Plugin | Ghi chú |
| --- | --- |
| EccubePaymentLite42 Regular tables | **KHÔNG sử dụng.** Plugin có `RegularOrder` / `RegularShipping` riêng nhưng dự án dùng Custom Subscription |
| GMO payment (`GmoApiClient`) | Custom GMO API client tích hợp trực tiếp trong Subscription service (`app/Customize/Service/Gmo/GmoApiClient.php`) |
| RemisePayment42 tables | Chỉ tham chiếu: autocharge subscription quản lý riêng |

### 5.3 Bảng Tuỳ Chỉnh (Đã triển khai)

> **Source code:**
> - Entity: `app/Customize/Entity/`
> - Repository: `app/Customize/Repository/`
> - Migration: `Version20260507103000.php`, `Version20260507120000.php`, `Version20260508070500.php`

#### `dtb_subscription`

Bảng subscription chính — Entity: `Customize\Entity\Subscription`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int (unsigned) | có | PK, auto increment |
| `customer_id` | int | có | FK → `dtb_customer.id` |
| `base_order_id` | int (unsigned) | có | Đơn hàng đầu tiên tạo subscription này |
| `status` | varchar(32) | có | `pending_activation` / `active` / `paused` / `past_due` / `cancelled` / `expired` |
| `plan_type` | varchar(32) | có | `test_minute` / `weekly` / `monthly` |
| `interval_count` | smallint (unsigned) | có | Mặc định `1`. VD: 1 = mỗi tháng, 3 = mỗi 3 tháng |
| `next_billing_at` | datetimetz | có | Ngày thanh toán tiếp theo |
| `next_fulfillment_at` | datetimetz | có | Ngày giao hàng tiếp theo |
| `last_billed_at` | datetimetz | không | Ngày thanh toán thành công gần nhất |
| `last_fulfilled_at` | datetimetz | không | Ngày giao hàng gần nhất |
| `cancelled_at` | datetimetz | không | Thời điểm huỷ |
| `retry_count` | smallint (unsigned) | có | Mặc định `0`. Số lần thử lại hiện tại |
| `max_retry` | smallint (unsigned) | có | Mặc định `3`. Số lần thử tối đa trước khi chuyển `past_due` |
| `subtotal_amount` | int | có | Mặc định `0`. Tạm tính (税抜 - chưa thuế) |
| `discount_amount` | int | có | Mặc định `0`. Số tiền giảm giá |
| `shipping_fee` | int | có | Mặc định `0`. Phí vận chuyển |
| `tax_amount` | int | có | Mặc định `0`. Số tiền thuế |
| `total_amount` | int | có | Mặc định `0`. Tổng cộng (税込 - bao gồm thuế + phí vận chuyển) |
| `payment_gateway` | varchar(32) | có | Mặc định `'gmo'`. Nhà cung cấp thanh toán |
| `gmo_member_id` | varchar(255) | không | GMO member ID |
| `gmo_card_seq` | varchar(64) | không | Sequence thẻ đã lưu trên GMO |
| `gmo_last_order_id` | varchar(255) | không | Tham chiếu GMO order ID gần nhất |
| `create_date` | datetimetz | có | Convention EC-CUBE |
| `update_date` | datetimetz | có | Convention EC-CUBE |
| `discriminator_type` | varchar(255) | có | Mặc định `'subscription'` |

**Indexes:**

- `idx_subscription_status_next` → (`status`, `next_billing_at`)
- `idx_subscription_customer_status` → (`customer_id`, `status`)

#### `dtb_subscription_item`

Snapshot mục sản phẩm — Entity: `Customize\Entity\SubscriptionItem`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int (unsigned) | có | PK, auto increment |
| `subscription_id` | int | có | FK → `dtb_subscription.id` (CASCADE) |
| `product_id` | int | không | FK → `dtb_product.id` (SET NULL khi xoá) |
| `product_class_id` | int | không | FK → `dtb_product_class.id` (SET NULL khi xoá) |
| `product_name_snapshot` | varchar(255) | có | Snapshot tên sản phẩm tại thời điểm đăng ký |
| `product_code_snapshot` | varchar(255) | không | Snapshot mã sản phẩm |
| `quantity` | int | có | Mặc định `1` |
| `unit_price_snapshot` | int | có | Mặc định `0`. Giá tại thời điểm đăng ký |
| `tax_rate_snapshot` | decimal(10,2) | không | Snapshot thuế suất |
| `is_combo` | smallint (unsigned) | có | Mặc định `0`. Cờ sản phẩm combo |
| `combo_code` | varchar(64) | không | Mã combo nếu áp dụng |
| `create_date` | datetime | có | Convention EC-CUBE |
| `update_date` | datetime | có | Convention EC-CUBE |
| `discriminator_type` | varchar(255) | có | Mặc định `'subscriptionitem'` |

**Index:** `idx_subscription_item_sub` → (`subscription_id`)

#### `dtb_subscription_order`

Bản ghi chu kỳ thanh toán — Entity: `Customize\Entity\SubscriptionOrder`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int (unsigned) | có | PK, auto increment |
| `subscription_id` | int | có | FK → `dtb_subscription.id` (CASCADE) |
| `order_id` | int (unsigned) | không | FK → `dtb_order.id` (đơn hàng gia hạn tự tạo) |
| `billing_cycle_key` | varchar(128) | có | Key duy nhất cho mỗi chu kỳ thanh toán |
| `billing_scheduled_at` | datetimetz | có | Thời gian thanh toán dự kiến |
| `billing_executed_at` | datetimetz | không | Thời gian thực thi thực tế |
| `billing_status` | varchar(32) | có | `pending` / `success` / `failed` |
| `gmo_order_id` | varchar(255) | không | GMO order ID |
| `gmo_access_id` | varchar(255) | không | GMO access ID |
| `gmo_access_pass` | varchar(255) | không | GMO access pass |
| `gmo_tran_id` | varchar(255) | không | GMO transaction ID |
| `gmo_approve` | varchar(255) | không | Mã duyệt GMO |
| `gmo_status` | varchar(64) | không | Trạng thái giao dịch GMO |
| `error_code` | varchar(255) | không | Mã lỗi nếu thất bại |
| `error_message` | text | không | Thông báo lỗi nếu thất bại |
| `create_date` | datetimetz | có | Convention EC-CUBE |
| `update_date` | datetimetz | có | Convention EC-CUBE |
| `discriminator_type` | varchar(255) | có | Mặc định `'subscriptionorder'` |

**Index:** `idx_subscription_order_lookup` → (`subscription_id`, `billing_status`)
**Unique:** `uniq_subscription_cycle` → (`billing_cycle_key`)

#### `dtb_subscription_event_log`

Nhật ký kiểm toán / lịch sử — Entity: `Customize\Entity\SubscriptionEventLog`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int (unsigned) | có | PK, auto increment |
| `subscription_id` | int | có | FK → `dtb_subscription.id` (CASCADE) |
| `event_type` | varchar(64) | có | `activated` / `renewal_success` / `renewal_failure` / `paused` / `cancelled` v.v. |
| `payload` | text | không | JSON payload chứa chi tiết sự kiện |
| `create_date` | datetimetz | có | Convention EC-CUBE |
| `discriminator_type` | varchar(255) | có | Mặc định `'subscriptioneventlog'` |

**Index:** `idx_subscription_event_sub` → (`subscription_id`)

### 5.4 API / Routes

#### Routes Frontend (CẦN TRIỂN KHAI — chưa có controller)

| Route | Method | Auth | Tên Route | Mô tả |
| --- | --- | --- | --- | --- |
| `/mypage/subscriptions` | GET | Có | `mypage_subscriptions` | Danh sách subscription |
| `/mypage/subscriptions/{id}` | GET | Có | `mypage_subscription_detail` | Chi tiết subscription |
| `/mypage/subscriptions/{id}/cancel` | POST | Có | `mypage_subscription_cancel` | Huỷ subscription |
| `/mypage/subscriptions/{id}/pause` | POST | Có | `mypage_subscription_pause` | Tạm dừng subscription (tuỳ chọn) |
| `/mypage/subscriptions/{id}/resume` | POST | Có | `mypage_subscription_resume` | Tiếp tục subscription (tuỳ chọn) |
| `/login?redirect_url=/mypage/subscriptions` | GET | Không | — | Chuyển hướng khi session hết hạn |

#### Routes Admin (ĐÃ TRIỂN KHAI)

| Route | Method | Auth | Tên Route | Mô tả |
| --- | --- | --- | --- | --- |
| `/%admin%/subscription` | GET | Admin | `admin_subscription_index` | Danh sách subscription Admin |
| `/%admin%/subscription/page/{page_no}` | GET | Admin | `admin_subscription_index_page` | Phân trang danh sách Admin |
| `/%admin%/subscription/{id}` | GET | Admin | `admin_subscription_detail` | Chi tiết subscription Admin |
| `/%admin%/subscription/{id}/retry-now` | POST | Admin | `admin_subscription_retry` | Admin thử lại thanh toán |
| `/%admin%/subscription/{id}/force-bill` | POST | Admin | `admin_subscription_force_bill` | Admin thanh toán bắt buộc |
| `/%admin%/subscription/{id}/cancel` | POST | Admin | `admin_subscription_cancel` | Admin huỷ subscription |

> **Lưu ý:**
> - Dùng route số nhiều `/mypage/subscriptions` cho frontend.
> - Tất cả routes frontend phải kiểm tra quyền sở hữu: `subscription.getCustomer().getId() == currentCustomer.getId()`.
> - **KHÔNG** dùng routes plugin EccubePaymentLite42 (`/mypage/eccube_payment_lite/regular/`).
> - Controller đặt tại `app/Customize/Controller/Mypage/MypageSubscriptionController.php`.

---

## 6. Trạng Thái Màn Hình

### 6.1 Trạng thái ban đầu

**Điều kiện:**

- Đăng nhập thành công
- Controller trả về dữ liệu thành công
- Có subscription

**UI:**

- Tab đang hoạt động được chọn
- Danh sách card subscription
- Nút xem chi tiết
- Nút subscription mới
- Khối thông báo

### 6.2 Trạng thái đang tải

- Skeleton loading card
- Vô hiệu hoá chuyển tab
- Vô hiệu hoá nút chi tiết
- Trạng thái loading cho phân trang

### 6.3 Trạng thái trống

**Điều kiện:** Không có subscription ở tab hiện tại

**UI:**
> 現在ご利用中の定期コースはありません。(Hiện không có gói định kỳ nào đang sử dụng.)

**CTA:**
> 新しい定期コースを申し込む (Đăng ký gói định kỳ mới)

### 6.4 Trạng thái lỗi

| Trường hợp | Điều kiện | UI | Hành động |
| --- | --- | --- | --- |
| Lỗi server | Controller exception / lỗi DB | 定期購入情報を取得できませんでした。(Không thể lấy thông tin subscription.) | Thử lại / quay lại mypage |
| Chưa xác thực | Session hết hạn / chưa đăng nhập | Chuyển hướng đăng nhập | `/login?redirect_url=/mypage/subscriptions` |
| Lỗi quyền sở hữu | `subscription.Customer.id != currentCustomer.id` | `NotFoundHttpException` (404) | Không tiết lộ chi tiết |
| Quá hạn thanh toán | `Subscription.status == past_due` | Badge 決済リトライ中 (Đang thử lại thanh toán) + hiển thị `retry_count` | Chỉ hiển thị (admin xử lý thử lại) |
| Sản phẩm đã xoá | `SubscriptionItem.Product == null` | Hiển thị `product_name_snapshot`, `unit_price_snapshot` | Cho phép xem chi tiết |
| Vấn đề thẻ GMO | `gmo_card_seq` thiếu hoặc không hợp lệ | Badge cảnh báo | Điều hướng đến màn hình cập nhật thẻ |

---

## 7. Bố Cục UI

### 7.1 Hình ảnh bố cục

- **Desktop:** Sidebar My Page + danh sách subscription
- **Mobile:** Bố cục dọc, menu thu gọn

### 7.2 Bố cục ban đầu

| STT | Thành phần | Mô tả | Hành động |
| --- | --- | --- | --- |
| 1 | Header | Header toàn site | Điều hướng |
| 2 | Side Menu | Điều hướng My Page | Điều hướng |
| 3 | Tiêu đề trang | 定期購入管理 (Quản lý Subscription) | Tĩnh |
| 4 | Tab điều hướng | 利用中 / 解約済み (Đang sử dụng / Đã huỷ) | Chuyển danh sách |
| 5 | Card Subscription | Tóm tắt subscription | Hiển thị thông tin |
| 6 | Hình ảnh sản phẩm | Thumbnail sản phẩm | Tĩnh |
| 7 | Badge trạng thái | 継続中 (Đang hoạt động) | Hiển thị trạng thái |
| 8 | Cảnh báo thanh toán | Thẻ hết hạn / thanh toán không hợp lệ | Điều hướng thanh toán |
| 9 | Cảnh báo địa chỉ | Địa chỉ thiếu / không hợp lệ | Điều hướng giao hàng |
| 10 | Nút chi tiết | 詳細を見る (Xem chi tiết) | Mở chi tiết |
| 11 | CTA Subscription mới | 新しい定期コースを申し込む (Đăng ký gói mới) | Điều hướng |
| 12 | Khối thông báo | Hướng dẫn subscription | Tĩnh |
| 13 | Nút FAQ | よくある質問を見る (Xem câu hỏi thường gặp) | Điều hướng |
| 14 | Footer | Footer site | Điều hướng |

### 7.3 Cấu trúc bố cục

```
Màn hình
├── Header
├── Container chính
│   ├── Điều hướng bên
│   └── Vùng nội dung
│       ├── Tiêu đề
│       ├── Tabs
│       ├── Danh sách Subscription
│       │   └── Card Subscription
│       ├── Phân trang
│       ├── Nút CTA
│       └── Khối thông báo
└── Footer
```

### 7.4 Responsive / Safe Area

| Mục | Giá trị |
| --- | --- |
| Responsive | Có |
| Desktop | Bố cục sidebar |
| Mobile | Bố cục dọc |
| Scroll | Có |
| Safe area | Có |
| Mobile menu thu gọn | Có |

---

## 8. Chi Tiết Thành Phần UI

### Thành phần: Side Menu

| Phần tử | Mô tả | Kiểu |
| --- | --- | --- |
| 会員情報 (Thông tin thành viên) | Thông tin thành viên | menu |
| お届け先情報 (Thông tin giao hàng) | Địa chỉ giao hàng | menu |
| 注文履歴 (Lịch sử đơn hàng) | Lịch sử đơn hàng | menu |
| **定期購入管理 (Quản lý Subscription)** | **Trang hiện tại** | **active menu** |
| クーポン一覧 (Danh sách coupon) | Danh sách coupon | menu |
| ポイント履歴 (Lịch sử điểm) | Lịch sử điểm | menu |
| 決済方法 (Phương thức thanh toán) | Phương thức thanh toán | menu |
| ログアウト (Đăng xuất) | Đăng xuất | menu |

### Thành phần: Tab Subscription

| Phần tử | Trạng thái bao gồm | Mô tả | Kiểu |
| --- | --- | --- | --- |
| 利用中の定期コース (Gói đang sử dụng) | `active`, `pending_activation`, `past_due` | Subscription đang hoạt động (bao gồm đang thử lại thanh toán) | tab |
| 一時停止中の定期コース (Gói tạm dừng) | `paused` | Subscription tạm dừng | tab (tuỳ chọn) |
| 解約済みの定期コース (Gói đã huỷ) | `cancelled`, `expired` | Subscription đã huỷ / hết hạn | tab |

### Thành phần: Card Subscription

| Phần tử | Entity field / nguồn | Mô tả | Kiểu |
| --- | --- | --- | --- |
| `product_image` | `SubscriptionItem.Product.MainListImage` (fallback: `no_image_product`) | Hình ảnh sản phẩm | image |
| `product_name` | `SubscriptionItem.product_name_snapshot` | Tên sản phẩm (snapshot) | text |
| `product_code` | `SubscriptionItem.product_code_snapshot` | Mã sản phẩm (snapshot) | text |
| `status_badge` | `Subscription.status` → label map | 継続中 / 一時停止中 / 解約済み / 決済リトライ中 | badge |
| `plan_type` | `Subscription.plan_type` + `interval_count` | お届けサイクル (Chu kỳ giao hàng, VD: 毎月) | text |
| `next_fulfillment_date` | `Subscription.next_fulfillment_at` | 次回お届け予定日 (Ngày giao hàng tiếp theo) | date |
| `next_billing_date` | `Subscription.next_billing_at` | 次回お支払い予定日 (Ngày thanh toán tiếp theo) | date |
| `total_amount` | `Subscription.total_amount` | 注文金額 (税込) (Số tiền đơn hàng, bao gồm thuế) | currency |
| `quantity` | `SubscriptionItem.quantity` | Số lượng | text |
| `payment_gateway` | `Subscription.payment_gateway` | 支払い方法 (Phương thức thanh toán, VD: GMO Credit Card) | text |
| `retry_badge` | `Subscription.retry_count` / `max_retry` | リトライ {n}/{max} 回目 (Lần thử lại {n}/{max}, khi `past_due`) | badge |
| `payment_warning` | Kiểm tra `gmo_card_seq` | Cảnh báo thanh toán | badge |
| `detail_button` | — | 詳細を見る (Xem chi tiết) | button |

### Thành phần: Khối Thông Báo

| Phần tử | Mô tả | Kiểu |
| --- | --- | --- |
| title | 定期購入について (Về Subscription) | title |
| guide_list | Nội dung hướng dẫn | text |
| faq_button | Nút FAQ | button |

---

## 9. Logic Nghiệp Vụ

### Chuyển Tab

| Tab | Trạng thái | Mô tả |
| --- | --- | --- |
| 利用中 (Đang sử dụng) | `active`, `pending_activation`, `past_due` | Subscription đang hoạt động (bao gồm đang thử lại thanh toán) |
| 一時停止中 (Tạm dừng) | `paused` | Subscription tạm dừng (tab tuỳ chọn) |
| 解約済み (Đã huỷ) | `cancelled`, `expired` | Subscription đã huỷ / hết hạn |

### Hiển thị trạng thái

Map tới các hằng `Subscription::STATUS_*`:

| Hằng trạng thái | Giá trị | Nhãn | Màu badge |
| --- | --- | --- | --- |
| `STATUS_PENDING_ACTIVATION` | `pending_activation` | 有効化待ち (Chờ kích hoạt) | 🔘 xám |
| `STATUS_ACTIVE` | `active` | 継続中 (Đang hoạt động) | 🟢 xanh lá |
| `STATUS_PAUSED` | `paused` | 一時停止中 (Tạm dừng) | 🟡 vàng |
| `STATUS_PAST_DUE` | `past_due` | 決済リトライ中 (Đang thử lại thanh toán) | 🔴 đỏ |
| `STATUS_CANCELLED` | `cancelled` | 解約済み (Đã huỷ) | 🔘 xám |
| `STATUS_EXPIRED` | `expired` | 期限切れ (Hết hạn) | 🔘 xám |

### Hiển thị loại gói

Map tới các hằng `Subscription::PLAN_*`:

| Hằng Plan | Giá trị | Nhãn |
| --- | --- | --- |
| `PLAN_TEST_MINUTE` | `test_minute` | テスト（分単位）(Test - theo phút) |
| `PLAN_WEEKLY` | `weekly` | 毎週 (Hàng tuần) |
| `PLAN_MONTHLY` | `monthly` | 毎月 (Hàng tháng) |

### Nút Chi Tiết

```
Nhấn 詳細を見る (Xem chi tiết)
  ↓
Mở màn hình chi tiết subscription → /mypage/subscriptions/{id}
```

### CTA Subscription Mới

Điều hướng đến trang danh sách sản phẩm subscription.

### Logic Quyền Sở Hữu & Điều Kiện

```
nếu user chưa đăng nhập:
    chuyển hướng /login?redirect_url=/mypage/subscriptions

nếu subscription.getCustomer().getId() != currentCustomer.getId():
    throw NotFoundHttpException (404)

nếu sản phẩm đã xoá (SubscriptionItem.getProduct() == null):
    hiển thị snapshot: product_name_snapshot, product_code_snapshot, unit_price_snapshot

nếu thẻ GMO hết hạn / không hợp lệ:
    hiển thị badge cảnh báo → liên kết đến màn hình cập nhật thẻ

nếu next_billing_at < hôm nay VÀ status == active:
    hiển thị badge chú ý thanh toán (thanh toán có thể quá hạn)

nếu retry_count > 0 VÀ status == past_due:
    hiển thị cảnh báo thử lại: "決済リトライ中（{retry_count}/{max_retry}回目）"

nếu số subscription đang hoạt động == 0:
    hiển thị trạng thái trống ở tab đang hoạt động
```

---

## 10. Phân Quyền

| Mục | Giá trị |
| --- | --- |
| Yêu cầu đăng nhập | Có |
| Truy cập guest | Không |
| Vai trò khách hàng | Có |
| Vai trò admin | Không |
| Kiểm tra quyền sở hữu | Bắt buộc |
| Yêu cầu CSRF | Có cho các hành động cập nhật |

---

## 11. Trường Hợp Biên

| Trường hợp | Xử lý |
| --- | --- |
| Không có subscription đang hoạt động | Hiển thị trạng thái trống |
| Không có subscription đã huỷ | Hiển thị trạng thái trống ở tab đã huỷ |
| Sản phẩm đã xoá | Hiển thị thông tin snapshot |
| Địa chỉ đã xoá | Hiển thị cảnh báo |
| Phương thức thanh toán hết hạn | Badge cảnh báo |
| Phương thức thanh toán đã xoá | Badge cảnh báo + liên kết phương thức thanh toán |
| Server timeout | UI thử lại |
| Nhiều subscription | Cuộn danh sách + phân trang |
| Địa chỉ rất dài | Xuống dòng nhiều dòng |
| Tên sản phẩm dài | Xuống dòng / cắt ngắn |
| Thiếu hình ảnh | Hình ảnh placeholder |
| Mobile độ rộng nhỏ | Responsive thu gọn |
| Thiếu tính toán ngày batch | Hiển thị fallback `'-'` |
| Subscription thuộc khách hàng khác | 404 / từ chối truy cập |

---

## 12. Test Cases

### Happy Path

| ID | Trường hợp | Kết quả mong đợi |
| --- | --- | --- |
| TC-001 | Mở trang | Hiển thị danh sách subscription |
| TC-002 | Chuyển tab | Danh sách đúng được hiển thị |
| TC-003 | Nhấn chi tiết | Mở màn hình chi tiết |
| TC-004 | Nhấn CTA | Điều hướng `/products/subscription` |
| TC-005 | Responsive mobile | Bố cục đúng |
| TC-006 | Nhấn cảnh báo thanh toán | Điều hướng phương thức thanh toán |

### Lỗi

| ID | Trường hợp | Kết quả mong đợi |
| --- | --- | --- |
| TC-101 | Lỗi server 500 | UI lỗi |
| TC-102 | Chưa xác thực | Chuyển hướng đăng nhập |
| TC-103 | Không có subscription | Trạng thái trống |
| TC-104 | Subscription của khách hàng khác | 404 / từ chối truy cập |

### Trường hợp biên

| ID | Trường hợp | Kết quả mong đợi |
| --- | --- | --- |
| TC-201 | Tên sản phẩm dài | Xuống dòng đúng |
| TC-202 | Nhiều subscription | Cuộn / phân trang đúng |
| TC-203 | Thiếu hình ảnh | Hiển thị hình ảnh placeholder |
| TC-204 | Thẻ thanh toán hết hạn | Hiển thị cảnh báo |
| TC-205 | Sản phẩm đã xoá | Hiển thị thông tin snapshot |
| TC-206 | Địa chỉ không hợp lệ | Hiển thị cảnh báo |

---

## 13. Ghi Chú Triển Khai EC-CUBE

### 13.1 Backend hiện có (ĐÃ TRIỂN KHAI)

#### Entity (`app/Customize/Entity/`)

| Entity | Bảng | Mô tả |
| --- | --- | --- |
| `Subscription` | `dtb_subscription` | Bản ghi subscription chính |
| `SubscriptionItem` | `dtb_subscription_item` | Snapshot mục sản phẩm |
| `SubscriptionOrder` | `dtb_subscription_order` | Bản ghi chu kỳ thanh toán |
| `SubscriptionEventLog` | `dtb_subscription_event_log` | Nhật ký kiểm toán |

#### Repository (`app/Customize/Repository/`)

| Repository | Phương thức chính |
| --- | --- |
| `SubscriptionRepository` | `getAdminListQueryBuilder()`, `findDueActive()` |
| `SubscriptionItemRepository` | `findBySubscriptionOrdered()` |
| `SubscriptionOrderRepository` | `findRecentBySubscription()` |
| `SubscriptionEventLogRepository` | `findRecentBySubscription()` |

#### Services (`app/Customize/Service/Subscription/`)

| Service | Mô tả |
| --- | --- |
| `SubscriptionActivator` | Tạo subscription từ checkout |
| `SubscriptionBillingRunner` | Engine thanh toán chính |
| `SubscriptionScheduler` | Tính toán ngày thanh toán / giao hàng tiếp theo |
| `SubscriptionRenewalOrderFactory` | Clone đơn hàng gốc cho gia hạn |
| `SubscriptionCycleParser` | Parse `plan_type` + `interval_count` |
| `SubscriptionAdminService` | Facade Admin (`billNow`, `cancel`) |
| `SubscriptionCancellationService` | Huỷ theo quy tắc nghiệp vụ |
| `SubscriptionMailNotifier` | Thông báo email |

#### Admin Controller (ĐÃ TRIỂN KHAI)

- **File:** `app/Customize/Controller/Admin/SubscriptionController.php`
- **Routes:** `admin_subscription_index`, `admin_subscription_detail`, `admin_subscription_retry`, `admin_subscription_force_bill`, `admin_subscription_cancel`

#### Commands

| Command | Mô tả |
| --- | --- |
| `subscription:run` | Batch thanh toán cron |
| `subscription:cancel` | Huỷ thủ công theo ID |

### 13.2 Frontend MyPage (CẦN TRIỂN KHAI)

#### Controller (CẦN TẠO)

**File:** `app/Customize/Controller/Mypage/MypageSubscriptionController.php`

| Phương thức | Mô tả |
| --- | --- |
| `index(Request, PaginatorInterface)` | Danh sách subscription với bộ lọc tab |
| `detail(int $id)` | Xem chi tiết subscription |

#### Route Annotations

```php
@Route("/mypage/subscriptions", name="mypage_subscriptions", methods={"GET"})
@Route("/mypage/subscriptions/page/{page_no}", name="mypage_subscriptions_page", requirements={"page_no"="\d+"}, methods={"GET"})
@Route("/mypage/subscriptions/{id}", name="mypage_subscription_detail", requirements={"id"="\d+"}, methods={"GET"})
```

#### Twig (CẦN TẠO)

| Template | Mô tả |
| --- | --- |
| `app/template/default/Mypage/subscriptions.twig` | Danh sách subscription |
| `app/template/default/Mypage/subscription_detail.twig` | Chi tiết subscription |

#### Dependencies (inject vào controller)

- `SubscriptionRepository`
- `SubscriptionItemRepository`
- `SubscriptionOrderRepository`
- `PaginatorInterface`

### 13.3 Ghi chú triển khai

> **Kiến trúc:**
> - **KHÔNG** dùng plugin EccubePaymentLite42 Regular. Dùng Custom Subscription entity / service.
> - Không sửa trực tiếp bảng core.
> - Controller kế thừa `AbstractController`, inject `SubscriptionRepository`.

> **Quyền sở hữu & Bảo mật:**
> - `$subscription->getCustomer()->getId() !== $this->getUser()->getId()` → throw `NotFoundHttpException`.
> - CSRF token bắt buộc cho mọi hành động POST.

> **Phân trang:**
> - Dùng `KnpPaginator` giống pattern `MypageController::index()`.

> **Template:**
> - Kế thừa `default_frame.twig`, include `Mypage/navi.twig`.
> - Đặt `{% set mypageno = 'subscription' %}` cho trạng thái active của navi.
> - Navi.twig cần thêm link 定期購入管理 (thêm qua jQuery append giống pattern plugin, hoặc override `navi.twig`).

> **Hiển thị dữ liệu:**
> - Card subscription hiển thị dữ liệu từ snapshot `SubscriptionItem` (`product_name_snapshot`, `unit_price_snapshot`) khi Product bị xoá.
> - Cảnh báo thanh toán: kiểm tra `gmo_card_seq` tồn tại + trạng thái subscription.
> - Số tiền: dùng `total_amount` từ entity Subscription (đã snapshot).
> - Ngày: `next_billing_at` → 次回お支払い予定日 (Ngày thanh toán tiếp theo), `next_fulfillment_at` → 次回お届け予定日 (Ngày giao hàng tiếp theo).

---

*Tạo lúc: 2026-05-11 15:27:00*
*Cập nhật: 2026-05-11*
