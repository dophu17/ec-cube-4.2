# EC-CUBE My Page — Chi Tiết Subscription (Spec Updated)

> Phiên bản lean spec cho AI coding (giữ nguyên sections 3 và 7 theo yêu cầu).

---

## 1. Tổng Quan

**Mục đích:**
Màn hình hiển thị chi tiết một subscription cụ thể.

**User có thể:**

- Xem thông tin subscription
- Xem danh sách sản phẩm trong subscription
- Thay đổi chu kỳ giao hàng
- Thay đổi ngày giao hàng
- Thay đổi phương thức thanh toán
- Thêm sản phẩm vào subscription
- Xoá sản phẩm khỏi subscription
- Điều chỉnh số lượng sản phẩm
- Huỷ subscription

**Vai trò trong hệ thống:**
`My Page → Quản lý Subscription → Chi tiết Subscription → Chỉnh sửa / Huỷ / Cập nhật sản phẩm`

**User:**

- Khách hàng đã đăng nhập
- Guest: Không
- Admin: Ngoài phạm vi

---

## 2. Phạm Vi

### ✅ Trong phạm vi

- Tóm tắt subscription
- Thông tin giao hàng / thanh toán
- Danh sách sản phẩm
- Điều chỉnh số lượng
- Xoá sản phẩm
- CTA thêm sản phẩm
- Tóm tắt giá
- Thay đổi chu kỳ
- Thay đổi ngày giao hàng
- Thay đổi phương thức thanh toán
- Responsive desktop / mobile
- Kiểm tra quyền sở hữu
- Xác thực CSRF cho các action ghi
- Hiển thị cảnh báo thanh toán / địa chỉ

### ❌ Ngoài phạm vi

- Luồng checkout
- Thực thi thanh toán thực tế
- Thao tác admin
- Hệ thống gợi ý sản phẩm
- Luồng tạo subscription
- Quản lý coupon
- Chi tiết đăng ký phương thức thanh toán

### Trạng thái triển khai Backend

> **Lưu ý:** Một số tính năng chỉnh sửa chưa có backend service, cần tạo mới khi implement frontend.

| Tính năng | Trạng thái Backend | Service cần tạo |
| --- | --- | --- |
| Xem chi tiết | ✅ Có (`SubscriptionRepository`, `SubscriptionItemRepository`) | — |
| Huỷ subscription | ✅ Có (`SubscriptionCancellationService`) | — |
| Thay đổi chu kỳ (plan_type / interval_count) | ⚠️ Chưa có | `SubscriptionEditService::changeCycle()` |
| Thay đổi ngày giao hàng (next_fulfillment_at) | ⚠️ Chưa có | `SubscriptionEditService::changeDeliveryDate()` |
| Thay đổi thanh toán (gmo_card_seq) | ⚠️ Chưa có | `SubscriptionEditService::changePayment()` |
| Thêm sản phẩm vào subscription | ⚠️ Chưa có | `SubscriptionEditService::addItem()` |
| Xoá sản phẩm khỏi subscription | ⚠️ Chưa có | `SubscriptionEditService::removeItem()` |
| Điều chỉnh số lượng | ⚠️ Chưa có | `SubscriptionEditService::updateItemQuantity()` |
| Tạm dừng / Tiếp tục | ⚠️ Chưa có | `SubscriptionEditService::pause()` / `resume()` |

---

## 3. Cấu Trúc Màn Hình

```
Màn hình Chi tiết Subscription
├── Header
├── Breadcrumb
├── Side Menu
├── Tóm tắt Subscription
├── Nút hành động quản lý
│   ├── Thay đổi chu kỳ giao hàng
│   ├── Thay đổi ngày giao hàng
│   ├── Thay đổi phương thức thanh toán
│   └── Huỷ Subscription
├── Danh sách sản phẩm Subscription
│   ├── Mục sản phẩm
│   ├── Bộ điều khiển số lượng
│   └── Xoá sản phẩm
├── CTA Thêm sản phẩm
├── Tóm tắt giá
├── Khối thông báo
└── Footer
```

---

## 4. Tham Số

### Tham số URL

| Tên | Kiểu | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `id` | integer | có | — | ID Subscription (tham số đường dẫn URL) |

### Tham số nội bộ (từ session / entity)

| Tên | Kiểu | Nguồn | Mô tả |
| --- | --- | --- | --- |
| `customer_id` | integer | Session (`$this->getUser()->getId()`) | ID khách hàng đã đăng nhập |
| `Subscription` | entity | `SubscriptionRepository::find($id)` | Entity Subscription |
| `SubscriptionItems` | collection | `SubscriptionItemRepository::findBySubscriptionOrdered()` | Danh sách sản phẩm |
| `SubscriptionOrders` | collection | `SubscriptionOrderRepository::findRecentBySubscription()` | Lịch sử thanh toán |

### Tham số cho các Action ghi

| Tên | Kiểu | Bắt buộc | Mô tả |
| --- | --- | --- | --- |
| `plan_type` | string | có (thay đổi chu kỳ) | `test_minute` / `weekly` / `monthly` |
| `interval_count` | integer | có (thay đổi chu kỳ) | VD: 1 = mỗi chu kỳ, 3 = mỗi 3 chu kỳ |
| `next_fulfillment_at` | date | có (thay đổi ngày) | Ngày giao hàng tiếp theo |
| `gmo_card_seq` | string | có (thay đổi thanh toán) | Sequence thẻ GMO mới |
| `quantity` | integer | có (thay đổi số lượng) | Số lượng mới (tối thiểu 1) |
| `product_class_id` | integer | có (thêm sản phẩm) | Product class cần thêm |
| `_token` | string | có (tất cả POST) | CSRF token |

---

## 5. Nguồn Dữ Liệu

### 5.1 Bảng chuẩn EC-CUBE

| Bảng | Mô tả | Sử dụng |
| --- | --- | --- |
| `dtb_customer` | Master khách hàng | Kiểm tra quyền sở hữu |
| `dtb_customer_address` | Địa chỉ khách hàng | Nguồn tham chiếu địa chỉ |
| `dtb_order` | Master đơn hàng | Đơn hàng gốc / đơn hàng định kỳ tự tạo |
| `dtb_order_item` | Mục đơn hàng | Snapshot / quan hệ mục đơn hàng |
| `dtb_shipping` | Vận chuyển | Thông tin vận chuyển từ đơn hàng gốc |
| `dtb_payment` | Phương thức thanh toán | Hiển thị thanh toán |
| `dtb_delivery` | Phương thức giao hàng | Tham chiếu giao hàng / chu kỳ |
| `dtb_product` | Master sản phẩm | Tên / trạng thái / hình ảnh sản phẩm |
| `dtb_product_class` | SKU / class sản phẩm | Giá, tồn kho, mã sản phẩm |
| `dtb_product_stock` | Tồn kho sản phẩm | Kiểm tra số lượng (thêm / tăng) |
| `dtb_product_image` | Hình ảnh sản phẩm | Thumbnail sản phẩm |
| `mtb_pref` | Master tỉnh/thành | Hiển thị địa chỉ giao hàng |
| `mtb_order_status` | Trạng thái đơn hàng | Trạng thái đơn hàng định kỳ liên quan |

> **Lưu ý:**
> - Dự án sử dụng **Custom Subscription** (`app/Customize/`), **KHÔNG** dùng EccubePaymentLite42 Regular plugin.
> - Không sửa trực tiếp bảng core.

### 5.2 Bảng Plugin (Chỉ tham chiếu)

| Bảng / Plugin | Ghi chú |
| --- | --- |
| EccubePaymentLite42 Regular tables | **KHÔNG sử dụng.** Dự án dùng Custom Subscription |
| GMO payment (`GmoApiClient`) | Custom GMO API client (`app/Customize/Service/Gmo/GmoApiClient.php`) |
| RemisePayment42 tables | Chỉ tham chiếu |

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
| `interval_count` | smallint (unsigned) | có | Mặc định `1` |
| `next_billing_at` | datetimetz | có | Ngày thanh toán tiếp theo |
| `next_fulfillment_at` | datetimetz | có | Ngày giao hàng tiếp theo |
| `last_billed_at` | datetimetz | không | Ngày thanh toán thành công gần nhất |
| `last_fulfilled_at` | datetimetz | không | Ngày giao hàng gần nhất |
| `cancelled_at` | datetimetz | không | Thời điểm huỷ |
| `retry_count` | smallint (unsigned) | có | Mặc định `0` |
| `max_retry` | smallint (unsigned) | có | Mặc định `3` |
| `subtotal_amount` | int | có | Mặc định `0` |
| `discount_amount` | int | có | Mặc định `0` |
| `shipping_fee` | int | có | Mặc định `0` |
| `tax_amount` | int | có | Mặc định `0` |
| `total_amount` | int | có | Mặc định `0` |
| `payment_gateway` | varchar(32) | có | Mặc định `'gmo'` |
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
| `product_name_snapshot` | varchar(255) | có | Snapshot tên sản phẩm |
| `product_code_snapshot` | varchar(255) | không | Snapshot mã sản phẩm |
| `quantity` | int | có | Mặc định `1` |
| `unit_price_snapshot` | int | có | Mặc định `0` |
| `tax_rate_snapshot` | decimal(10,2) | không | Snapshot thuế suất |
| `is_combo` | smallint (unsigned) | có | Mặc định `0` |
| `combo_code` | varchar(64) | không | Mã combo |
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
| `order_id` | int (unsigned) | không | FK → `dtb_order.id` |
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

#### Routes Frontend (CẦN TRIỂN KHAI)

| Route | Method | Auth | Tên Route | Trạng thái Backend |
| --- | --- | --- | --- | --- |
| `/mypage/subscriptions/{id}` | GET | Có | `mypage_subscription_detail` | ✅ Entity có |
| `/mypage/subscriptions/{id}/cycle` | POST | Có | `mypage_subscription_cycle` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/delivery-date` | POST | Có | `mypage_subscription_delivery_date` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/payment` | POST | Có | `mypage_subscription_payment` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/products` | POST | Có | `mypage_subscription_add_product` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/products/{item_id}/remove` | POST | Có | `mypage_subscription_remove_product` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/products/{item_id}/quantity` | POST | Có | `mypage_subscription_update_quantity` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/pause` | POST | Có | `mypage_subscription_pause` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/resume` | POST | Có | `mypage_subscription_resume` | ⚠️ Cần service mới |
| `/mypage/subscriptions/{id}/cancel` | POST | Có | `mypage_subscription_cancel` | ✅ `SubscriptionCancellationService` |
| `/login?redirect_url=/mypage/subscriptions/{id}` | GET | Không | — | ✅ EC-CUBE core |

#### Routes Admin (ĐÃ TRIỂN KHAI — tham chiếu)

| Route | Tên Route | Mô tả |
| --- | --- | --- |
| `/%admin%/subscription/{id}` | `admin_subscription_detail` | Xem chi tiết Admin |
| `/%admin%/subscription/{id}/cancel` | `admin_subscription_cancel` | Admin huỷ |
| `/%admin%/subscription/{id}/retry-now` | `admin_subscription_retry` | Admin thử lại thanh toán |
| `/%admin%/subscription/{id}/force-bill` | `admin_subscription_force_bill` | Admin thanh toán bắt buộc |

> **Lưu ý:**
> - Tất cả routes POST frontend phải kiểm tra quyền sở hữu + CSRF.
> - **KHÔNG** dùng routes plugin EccubePaymentLite42.
> - Controller đặt tại `app/Customize/Controller/Mypage/MypageSubscriptionController.php`.
> - Các routes chỉnh sửa (chu kỳ, ngày, thanh toán, sản phẩm) cần tạo `SubscriptionEditService` mới.

---

## 6. Trạng Thái Màn Hình

### 6.1 Trạng thái ban đầu

**Điều kiện:**

- Đăng nhập thành công
- Controller trả về dữ liệu thành công
- Subscription tồn tại
- Quyền sở hữu hợp lệ

**UI:**

- Tóm tắt subscription
- Danh sách sản phẩm
- Tóm tắt giá
- Các nút hành động
- Badge cảnh báo nếu thanh toán / sản phẩm có vấn đề

### 6.2 Trạng thái đang tải

- Skeleton tóm tắt
- Skeleton hàng sản phẩm
- Vô hiệu hoá nút số lượng
- Vô hiệu hoá nút hành động
- Loading overlay cho modal submit

### 6.3 Trạng thái trống

**Điều kiện:** Subscription không có sản phẩm (SubscriptionItems rỗng)

**UI:**
> ご利用中の商品がありません。(Không có sản phẩm đang sử dụng.)

**CTA:**
> 商品を追加する (Thêm sản phẩm)

### 6.4 Trạng thái lỗi

| Trường hợp | Điều kiện | UI | Hành động |
| --- | --- | --- | --- |
| Lỗi server | Controller exception / lỗi DB | 定期購入情報を取得できませんでした。(Không thể lấy thông tin subscription.) | Thử lại |
| Chưa xác thực | Session hết hạn / chưa đăng nhập | Chuyển hướng đăng nhập | `/login?redirect_url=/mypage/subscriptions/{id}` |
| Không tìm thấy | ID không hợp lệ / đã xoá | 対象の定期購入が見つかりません。(Không tìm thấy subscription.) | Quay lại danh sách |
| Lỗi quyền sở hữu | `subscription.Customer.id != currentCustomer.id` | `NotFoundHttpException` (404) | Không tiết lộ chi tiết |
| Quá hạn thanh toán | `Subscription.status == past_due` | Badge 決済リトライ中 (Đang thử lại thanh toán) | Chỉ hiển thị |
| Sản phẩm đã xoá | `SubscriptionItem.Product == null` | Hiển thị `product_name_snapshot` | Cho phép hiển thị |
| Sản phẩm hết hàng | Stock = 0 | Vô hiệu hoá tăng số lượng | Cảnh báo |
| Subscription đã huỷ | `status == cancelled` | Vô hiệu hoá tất cả hành động chỉnh sửa | Chỉ xem |
| Đã qua hạn thay đổi | Trước thời hạn thanh toán tiếp theo | Vô hiệu hoá chỉnh sửa ngày / chu kỳ / sản phẩm | Hiển thị thông báo |

---

## 7. Bố Cục UI

### 7.1 Hình ảnh bố cục

- **Desktop:** sidebar + nội dung chi tiết
- **Mobile:** cột đơn, điều khiển số lượng tối ưu cảm ứng

### 7.2 Bố cục ban đầu

| STT | Thành phần | Mô tả | Hành động |
| --- | --- | --- | --- |
| 1 | Breadcrumb | Phân cấp điều hướng | Điều hướng |
| 2 | Side Menu | Menu My Page | Điều hướng |
| 3 | Tiêu đề trang | 定期購入詳細 (Chi tiết Subscription) | Tĩnh |
| 4 | Tóm tắt Subscription | Thông tin subscription | Hiển thị |
| 5 | Badge trạng thái | 継続中 / 一時停止中 / 解約済み (Đang hoạt động / Tạm dừng / Đã huỷ) | Trạng thái |
| 6 | Badge cảnh báo | Cảnh báo thanh toán / sản phẩm | Điều hướng / sửa |
| 7 | Nút hành động | Các hành động subscription | Mở modal / action |
| 8 | Danh sách sản phẩm | Sản phẩm trong subscription | Hiển thị |
| 9 | Bộ điều khiển số lượng | +/- số lượng | Cập nhật số lượng |
| 10 | Nút xoá | 削除 (Xoá) | Xoá sản phẩm |
| 11 | CTA Thêm sản phẩm | 商品を追加する (Thêm sản phẩm) | Điều hướng / modal |
| 12 | Tóm tắt giá | Tóm tắt giá cả | Hiển thị |
| 13 | Khối thông báo | Hướng dẫn subscription | Tĩnh |
| 14 | Nút FAQ | FAQ | Điều hướng |
| 15 | Footer | Footer | Điều hướng |

### 7.3 Cấu trúc bố cục

```
Màn hình
├── Header
├── Breadcrumb
├── Container chính
│   ├── Side Menu
│   └── Nội dung
│       ├── Phần tóm tắt
│       ├── Phần nút hành động
│       ├── Danh sách sản phẩm
│       ├── CTA Thêm sản phẩm
│       ├── Tóm tắt giá
│       └── Khối thông báo
└── Footer
```

### 7.4 Responsive / Safe Area

| Mục | Giá trị |
| --- | --- |
| Responsive | Có |
| Desktop | Hai cột |
| Mobile | Cột đơn |
| Safe area | Có |
| Scroll | Có |
| Điều khiển số lượng | Tối ưu cảm ứng |
| Modal mobile | Bottom sheet / full width |

---

## 8. Chi Tiết Thành Phần UI

### Thành phần: Tóm tắt Subscription

| Phần tử | Entity field / nguồn | Mô tả | Kiểu |
| --- | --- | --- | --- |
| `subscription_id` | `Subscription.id` | Số subscription | text |
| `status_badge` | `Subscription.status` → label map | 継続中 / 一時停止中 / 解約済み / 決済リトライ中 | badge |
| `plan_type` | `Subscription.plan_type` + `interval_count` | お届けサイクル (Chu kỳ giao hàng, VD: 毎月) | text |
| `next_fulfillment_date` | `Subscription.next_fulfillment_at` | 次回お届け予定日 (Ngày giao hàng tiếp theo) | date |
| `next_billing_date` | `Subscription.next_billing_at` | 次回お支払い予定日 (Ngày thanh toán tiếp theo) | date |
| `total_amount` | `Subscription.total_amount` | 注文金額 (税込) (Số tiền đơn hàng, bao gồm thuế) | currency |
| `shipping_address` | Đơn hàng gốc (`Subscription.base_order_id`) → `Shipping` | お届け先 (Địa chỉ giao hàng) | text |
| `payment_gateway` | `Subscription.payment_gateway` | 支払い方法 (Phương thức thanh toán, VD: GMO Credit Card) | text |
| `retry_badge` | `Subscription.retry_count` / `max_retry` | リトライ {n}/{max} 回目 (Lần thử lại {n}/{max}, khi `past_due`) | badge |
| `payment_warning` | Kiểm tra `gmo_card_seq` | Cảnh báo vấn đề thanh toán | badge |

### Thành phần: Nút Hành Động

| Phần tử | Mô tả | Trạng thái backend | Kiểu |
| --- | --- | --- | --- |
| `change_cycle` | お届けサイクルの変更 (Thay đổi chu kỳ giao hàng) | ⚠️ Cần service mới | button |
| `change_delivery_date` | お届け日の変更 (Thay đổi ngày giao hàng) | ⚠️ Cần service mới | button |
| `change_payment_method` | お支払い方法の変更 (Thay đổi phương thức thanh toán) | ⚠️ Cần service mới | button |
| `pause_subscription` | 一時停止する (Tạm dừng) | ⚠️ Cần service mới | button (tuỳ chọn) |
| `resume_subscription` | 再開する (Tiếp tục) | ⚠️ Cần service mới | button (tuỳ chọn) |
| `cancel_subscription` | 定期購入を解約する (Huỷ subscription) | ✅ `SubscriptionCancellationService` | destructive button |

### Thành phần: Danh Sách Sản Phẩm

| Phần tử | Entity field / nguồn | Mô tả | Kiểu |
| --- | --- | --- | --- |
| `product_image` | `SubscriptionItem.Product.MainListImage` (fallback: `no_image_product`) | Thumbnail sản phẩm | image |
| `product_name` | `SubscriptionItem.product_name_snapshot` | Tên sản phẩm (snapshot) | text |
| `product_code` | `SubscriptionItem.product_code_snapshot` | Mã sản phẩm (snapshot) | text |
| `unit_price` | `SubscriptionItem.unit_price_snapshot` | Đơn giá (snapshot) | currency |
| `quantity_control` | `SubscriptionItem.quantity` | +/- số lượng (⚠️ cần service mới) | stepper |
| `subtotal` | `unit_price_snapshot × quantity` | Thành tiền sản phẩm | currency |
| `remove_button` | — | 削除 (Xoá) (⚠️ cần service mới) | button |

### Thành phần: Tóm Tắt Giá

| Phần tử | Entity field | Mô tả | Kiểu |
| --- | --- | --- | --- |
| `subtotal` | `Subscription.subtotal_amount` | 小計 (Tạm tính) | currency |
| `discount` | `Subscription.discount_amount` | 定期割引 (Giảm giá định kỳ) | currency |
| `shipping_fee` | `Subscription.shipping_fee` | 送料 (Phí vận chuyển) | currency |
| `tax` | `Subscription.tax_amount` | 消費税 (Thuế tiêu thụ) | currency |
| `total` | `Subscription.total_amount` | 合計（税込）(Tổng cộng, bao gồm thuế) | currency |

---

## 9. Logic Nghiệp Vụ

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

### Điều khiển số lượng

```
Nhấn +
  ↓ Kiểm tra tồn kho / giới hạn bán / hạn chỉnh sửa subscription
  ↓ Tăng SubscriptionItem.quantity
  ↓ Tính lại số tiền Subscription (subtotal_amount, tax_amount, total_amount)

Nhấn -
  ↓ Giảm SubscriptionItem.quantity
  ↓ Số lượng tối thiểu = 1
  ↓ Tính lại số tiền Subscription
```

> ⚠️ Cần tạo `SubscriptionEditService::updateItemQuantity()` + logic tính lại.

### Xoá sản phẩm

```
Nhấn 削除 (Xoá)
  ↓ Mở hộp thoại xác nhận
  ↓ Kiểm tra: phải còn ít nhất một mục hoạt động
  ↓ Xoá SubscriptionItem
  ↓ Tính lại số tiền Subscription
  ↓ Ghi nhật ký vào SubscriptionEventLog
```

> Nếu xoá sản phẩm cuối cùng:
> - Không tự động huỷ subscription
> - Hiển thị cảnh báo: subscription không còn sản phẩm
> - CTA: thêm sản phẩm hoặc huỷ subscription

> ⚠️ Cần tạo `SubscriptionEditService::removeItem()`.

### Thêm sản phẩm

Mở luồng thêm sản phẩm / modal hoặc điều hướng đến trang danh sách sản phẩm subscription.

> ⚠️ Cần tạo `SubscriptionEditService::addItem()`.

### Thay đổi chu kỳ giao hàng

```
Chọn plan_type + interval_count mới
  ↓ Kiểm tra giá trị cho phép (từ config)
  ↓ Cập nhật Subscription.plan_type, Subscription.interval_count
  ↓ Tính lại next_billing_at, next_fulfillment_at qua SubscriptionScheduler
  ↓ Ghi nhật ký vào SubscriptionEventLog
```

> Ví dụ mặc định: 毎週 (Hàng tuần) / 毎月 (Hàng tháng) / 3ヶ月ごと (Mỗi 3 tháng)
> ⚠️ Cần tạo `SubscriptionEditService::changeCycle()`.

### Thay đổi ngày giao hàng

```
Chọn ngày mới
  ↓ Kiểm tra: ngày chọn > hạn thay đổi (change_deadline)
  ↓ Cập nhật Subscription.next_fulfillment_at
  ↓ Tính lại next_billing_at qua SubscriptionScheduler
  ↓ Ghi nhật ký vào SubscriptionEventLog
```

> ⚠️ Cần tạo `SubscriptionEditService::changeDeliveryDate()`.

### Thay đổi thanh toán

```
Điều hướng đến cập nhật thẻ hoặc chọn từ thẻ đã lưu
  ↓ Cập nhật Subscription.gmo_card_seq (và gmo_member_id nếu cần)
  ↓ Ghi nhật ký vào SubscriptionEventLog
```

> Nếu thanh toán hiện tại hết hạn → hiển thị cảnh báo.
> ⚠️ Cần tạo `SubscriptionEditService::changePayment()`.

### Huỷ Subscription

```
Nhấn 定期購入を解約する (Huỷ subscription)
  ↓ Mở modal xác nhận huỷ
  ↓ Kiểm tra qua SubscriptionCancellationService
     (quy tắc: chỉ huỷ nếu chưa có pre-billing thành công cho chu kỳ fulfillment hiện tại)
  ↓ Xác nhận
  ↓ Subscription.status → cancelled
  ↓ Subscription.cancelled_at → thời điểm hiện tại
  ↓ Dừng thanh toán / tạo đơn hàng trong tương lai
  ↓ Ghi nhật ký vào SubscriptionEventLog
```

> ✅ Backend: `SubscriptionCancellationService` đã triển khai.

### Hiển thị giá

> Số tiền hiển thị từ snapshot trên `Subscription` entity (không tính real-time):

| Hiển thị | Entity Field |
| --- | --- |
| 小計 (Tạm tính) | `Subscription.subtotal_amount` |
| 定期割引 (Giảm giá định kỳ) | `Subscription.discount_amount` |
| 送料 (Phí vận chuyển) | `Subscription.shipping_fee` |
| 消費税 (Thuế tiêu thụ) | `Subscription.tax_amount` |
| 合計（税込）(Tổng cộng, bao gồm thuế) | `Subscription.total_amount` |

> Khi chỉnh sửa (thêm / xoá / thay đổi số lượng), cần tính lại và cập nhật các trường amount.

### Giới hạn chỉnh sửa

```
nếu Subscription.status == cancelled HOẶC expired:
    vô hiệu hoá TẤT CẢ hành động chỉnh sửa (chỉ xem)

nếu Subscription.status == past_due:
    vô hiệu hoá thay đổi chu kỳ / ngày / sản phẩm (chỉ cho huỷ hoặc thay đổi thanh toán)

nếu đã qua hạn thay đổi cho lần thanh toán tiếp theo:
    vô hiệu hoá thay đổi chu kỳ / ngày / sản phẩm
    hiển thị thông báo: "次回お届けの変更期限を過ぎています" (Đã quá hạn thay đổi cho lần giao hàng tiếp theo)
```

---

## 10. Phân Quyền

| Mục | Giá trị |
| --- | --- |
| Yêu cầu đăng nhập | Có |
| Kiểm tra quyền sở hữu | Bắt buộc |
| Truy cập guest | Không |
| Truy cập admin | Không |
| Yêu cầu CSRF | Có cho tất cả hành động POST |

**Quy tắc bảo mật:**

```php
$subscription->getCustomer()->getId() !== $this->getUser()->getId()
  → throw NotFoundHttpException
```

---

## 11. Trường Hợp Biên

| Trường hợp | Xử lý |
| --- | --- |
| Nhấn nhanh liên tục nút số lượng | Debounce / khoá cập nhật |
| Sản phẩm đã bị xoá (`SubscriptionItem.Product == null`) | Hiển thị snapshot (`product_name_snapshot`, `unit_price_snapshot`) |
| Sản phẩm hết hàng | Vô hiệu hoá tăng số lượng |
| Vượt giới hạn bán sản phẩm | Hiển thị lỗi validation |
| Thẻ GMO hết hạn / không hợp lệ | Badge cảnh báo + liên kết cập nhật thẻ |
| Phương thức thanh toán bị xoá | Badge cảnh báo + yêu cầu cập nhật |
| Phí vận chuyển thay đổi sau chỉnh sửa | Tính lại `total_amount` |
| Server timeout | UI thử lại |
| Xoá sản phẩm cuối cùng | Hiển thị cảnh báo, KHÔNG tự động huỷ |
| Subscription đã huỷ | Vô hiệu hoá tất cả chỉnh sửa |
| Đã qua hạn thay đổi | Vô hiệu hoá hành động chỉnh sửa, hiển thị thông báo |
| Subscription thuộc khách hàng khác | 404 / từ chối truy cập |
| Nhiều tab cập nhật cùng subscription | Tải lại trạng thái mới nhất khi focus |
| Mobile độ rộng nhỏ | Bố cục responsive |

---

## 12. Test Cases

### Happy Path

| ID | Trường hợp | Kết quả mong đợi |
| --- | --- | --- |
| TC-001 | Mở chi tiết | Dữ liệu hiển thị chính xác |
| TC-002 | Tăng số lượng | Tổng cập nhật |
| TC-003 | Giảm số lượng | Tổng cập nhật |
| TC-004 | Xoá sản phẩm | Sản phẩm bị xoá, tổng tính lại |
| TC-005 | Thêm sản phẩm | Sản phẩm được thêm vào subscription |
| TC-006 | Thay đổi chu kỳ | Chu kỳ cập nhật, ngày tính lại |
| TC-007 | Thay đổi ngày giao hàng | Ngày cập nhật |
| TC-008 | Thay đổi phương thức thanh toán | Thanh toán cập nhật |
| TC-009 | Huỷ subscription | Trạng thái → cancelled, chỉnh sửa bị vô hiệu hoá |
| TC-010 | Quay lại danh sách | Trạng thái danh sách được giữ |

### Lỗi

| ID | Trường hợp | Kết quả mong đợi |
| --- | --- | --- |
| TC-101 | Lỗi server 500 | UI lỗi |
| TC-102 | Chưa xác thực | Chuyển hướng đăng nhập |
| TC-103 | Subscription của khách hàng khác | 404 / từ chối truy cập |
| TC-104 | Sản phẩm hết hàng | Lỗi validation khi tăng |
| TC-105 | Số lượng không hợp lệ (< 1) | Từ chối cập nhật |
| TC-106 | CSRF không hợp lệ | Lỗi / tải lại |
| TC-107 | Đã qua hạn thay đổi | Chỉnh sửa bị chặn với thông báo |

### Trường hợp biên

| ID | Trường hợp | Kết quả mong đợi |
| --- | --- | --- |
| TC-201 | Tên sản phẩm rất dài | Xuống dòng đúng |
| TC-202 | Nhiều sản phẩm | Cuộn đúng |
| TC-203 | Nhấn đúp nút số lượng | Cập nhật một lần (debounce) |
| TC-204 | Sản phẩm bị xoá khỏi catalog | Hiển thị snapshot |
| TC-205 | Thẻ thanh toán hết hạn | Hiển thị cảnh báo |
| TC-206 | Xoá sản phẩm cuối cùng | Hiển thị cảnh báo, không tự huỷ |
| TC-207 | Subscription đã huỷ | Tất cả chỉnh sửa bị vô hiệu hoá |

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
| `SubscriptionRepository` | `find()`, `getAdminListQueryBuilder()`, `findDueActive()` |
| `SubscriptionItemRepository` | `findBySubscriptionOrdered()` |
| `SubscriptionOrderRepository` | `findRecentBySubscription()` |
| `SubscriptionEventLogRepository` | `findRecentBySubscription()` |

#### Service hiện có

| Service | Dùng cho Detail | Mô tả |
| --- | --- | --- |
| `SubscriptionCancellationService` | ✅ Hành động huỷ | Huỷ theo quy tắc nghiệp vụ |
| `SubscriptionScheduler` | ✅ Tính lại ngày | Tính toán ngày thanh toán / giao hàng tiếp theo |

### 13.2 Backend mới (CẦN TRIỂN KHAI)

#### `SubscriptionEditService` (CẦN TẠO)

**File:** `app/Customize/Service/Subscription/SubscriptionEditService.php`

| Phương thức | Mô tả | Entity bị ảnh hưởng |
| --- | --- | --- |
| `changeCycle($subscription, $planType, $intervalCount)` | Thay đổi plan_type + interval_count, tính lại ngày | `Subscription`, `SubscriptionEventLog` |
| `changeDeliveryDate($subscription, $newDate)` | Cập nhật next_fulfillment_at, tính lại next_billing_at | `Subscription`, `SubscriptionEventLog` |
| `changePayment($subscription, $gmoCardSeq, $gmoMemberId)` | Cập nhật thông tin thanh toán | `Subscription`, `SubscriptionEventLog` |
| `addItem($subscription, $productClass, $quantity)` | Thêm SubscriptionItem mới, tính lại số tiền | `SubscriptionItem`, `Subscription`, `SubscriptionEventLog` |
| `removeItem($subscription, $subscriptionItem)` | Xoá mục, kiểm tra tối thiểu 1 mục, tính lại | `SubscriptionItem`, `Subscription`, `SubscriptionEventLog` |
| `updateItemQuantity($subscriptionItem, $newQuantity)` | Cập nhật số lượng, tính lại số tiền | `SubscriptionItem`, `Subscription`, `SubscriptionEventLog` |
| `pause($subscription)` | Đặt trạng thái → paused | `Subscription`, `SubscriptionEventLog` |
| `resume($subscription)` | Đặt trạng thái → active, tính lại ngày | `Subscription`, `SubscriptionEventLog` |
| `recalculateAmounts($subscription)` | Tính lại subtotal / tax / total từ items | `Subscription` |

> Phụ thuộc: `SubscriptionScheduler`, `SubscriptionItemRepository`, `EntityManagerInterface`

### 13.3 Frontend Controller (CẦN TRIỂN KHAI)

**File:** `app/Customize/Controller/Mypage/MypageSubscriptionController.php`

| Phương thức | Route | Mô tả |
| --- | --- | --- |
| `detail(int $id)` | `GET /mypage/subscriptions/{id}` | Xem chi tiết |
| `changeCycle(Request, int $id)` | `POST .../cycle` | Thay đổi chu kỳ |
| `changeDeliveryDate(Request, int $id)` | `POST .../delivery-date` | Thay đổi ngày |
| `changePayment(Request, int $id)` | `POST .../payment` | Thay đổi thanh toán |
| `addProduct(Request, int $id)` | `POST .../products` | Thêm sản phẩm |
| `removeProduct(Request, int $id, int $item_id)` | `POST .../products/{item_id}/remove` | Xoá sản phẩm |
| `updateQuantity(Request, int $id, int $item_id)` | `POST .../products/{item_id}/quantity` | Cập nhật số lượng |
| `cancel(Request, int $id)` | `POST .../cancel` | Huỷ subscription |

#### Twig (CẦN TẠO)

| Template | Mô tả |
| --- | --- |
| `app/template/default/Mypage/subscription_detail.twig` | Trang chi tiết |

### 13.4 Ghi chú triển khai

> **Kiến trúc:**
> - **KHÔNG** dùng plugin EccubePaymentLite42 Regular. Dùng Custom Subscription entity / service.
> - Không sửa trực tiếp bảng core.
> - Controller kế thừa `AbstractController`.
> - Phương thức detail nên dùng chung với Management controller (`MypageSubscriptionController`).

> **Quyền sở hữu & Bảo mật:**
> - `$subscription->getCustomer()->getId() !== $this->getUser()->getId()` → throw `NotFoundHttpException`.
> - CSRF token bắt buộc cho mọi hành động POST (`$this->isTokenValid()`).

> **Template:**
> - Kế thừa `default_frame.twig`, include `Mypage/navi.twig`.
> - Đặt `{% set mypageno = 'subscription' %}`.
> - Breadcrumb: My Page > 定期購入管理 > 定期購入詳細.

> **Hiển thị dữ liệu:**
> - Thông tin sản phẩm từ snapshot `SubscriptionItem` (`product_name_snapshot`, `unit_price_snapshot`) khi `Product` bị xoá.
> - Địa chỉ giao hàng lấy từ đơn hàng gốc (`Subscription.base_order_id → Order → Shipping`).
> - Hiển thị số tiền dùng trường snapshot trên entity `Subscription`.
> - `next_billing_at` → 次回お支払い予定日 (Ngày thanh toán tiếp theo), `next_fulfillment_at` → 次回お届け予定日 (Ngày giao hàng tiếp theo).

> **Luồng chỉnh sửa:**
> - Hành động chỉnh sửa dùng `SubscriptionEditService` (cần tạo mới).
> - Mọi hành động chỉnh sửa phải ghi nhật ký vào `SubscriptionEventLog`.
> - Sau chỉnh sửa, cần tính lại số tiền + ngày nếu áp dụng.
> - Subscription đã huỷ / hết hạn → vô hiệu hoá tất cả hành động chỉnh sửa.
> - Subscription quá hạn thanh toán (past_due) → chỉ cho huỷ hoặc thay đổi thanh toán.

---

*Tạo lúc: 2026-05-11 15:37:00*
*Cập nhật: 2026-05-11*
