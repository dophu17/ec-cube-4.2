# EC-CUBE Luồng Tạo Subscription (Cập nhật)

> Tài liệu mô tả luồng tạo subscription. Subscription được tạo qua **Shopping Checkout**, không phải MyPage wizard.

---

## 1. Tổng quan

**Mục đích:**
Mô tả luồng tạo mới một 定期購入 (subscription / mua hàng định kỳ) cho khách hàng.

**Luồng tạo subscription:**

```
Trang sản phẩm → Giỏ hàng → Thanh toán (Checkout)
  → ☑ subscription_enabled + chọn subscription_cycle
  → Thanh toán GMO Credit Card
  → Hoàn tất đơn hàng
  → [Tự động] SubscriptionActivator tạo Subscription + SubscriptionItems từ Order
```

> **QUAN TRỌNG:** Subscription được tạo **tự động sau khi checkout thành công**, không phải từ một màn hình riêng trong MyPage. Không có MyPage wizard để tạo subscription.

**Khách hàng có thể:**

- Mua sản phẩm bình thường qua checkout
- Tick checkbox 定期購入 tại trang xác nhận đơn hàng
- Chọn chu kỳ giao hàng (subscription cycle)
- Nhập thông tin thẻ GMO
- Sau khi đơn hàng thành công → subscription tự động được tạo

**Vai trò trong hệ thống:**

```
Sản phẩm → Giỏ hàng → Xác nhận đơn hàng [tuỳ chọn subscription] → Thanh toán → Hoàn tất → Subscription được tạo
                                                                                                    ↓
                                                                                MyPage → Quản lý Subscription → Chi tiết
```

**Người dùng:**

- Khách hàng đã đăng nhập
- Khách vãng lai: Không (subscription yêu cầu tài khoản)
- Quản trị viên: Ngoài phạm vi (admin xem subscription qua admin panel)

---

## 2. Phạm vi

### ✅ Trong phạm vi

- Checkbox subscription trên trang xác nhận đơn hàng
- Dropdown chọn chu kỳ subscription
- Các trường nhập thẻ GMO (direct card / token)
- Tạo đơn hàng kèm cờ subscription
- Tự động tạo Subscription + SubscriptionItems sau khi thanh toán thành công
- Tính toán lịch thanh toán lần đầu
- Ghi log sự kiện kích hoạt subscription
- Gửi email thông báo (nếu cấu hình)

### ❌ Ngoài phạm vi

- Wizard tạo subscription riêng trong MyPage (KHÔNG implement)
- Subscription cho khách vãng lai
- Thiết lập recurring từ admin
- Hệ thống đề xuất sản phẩm tự động
- Logic coupon/point cho subscription
- Bảng cấu hình sản phẩm subscription (mọi sản phẩm đều có thể subscription)
- PayPay / chuyển khoản (chỉ hỗ trợ GMO credit card)

---

## 3. Cấu trúc màn hình

> Tạo subscription là **phần mở rộng của Shopping Checkout**, không phải màn hình riêng.

```
Trang xác nhận đơn hàng (Shopping Confirm - EC-CUBE có sẵn)
├─ Tổng quan đơn hàng (có sẵn)
├─ Thông tin giao hàng (có sẵn)
├─ Thông tin thanh toán (có sẵn)
├─ ★ Khối tuỳ chọn Subscription (thêm bởi ShoppingOrderTypeExtension)
│   ├─ ☐ Checkbox subscription_enabled
│   ├─ Dropdown subscription_cycle
│   └─ Các trường thẻ GMO (nếu bật subscription)
├─ Nút xác nhận (có sẵn)
└─ ...
```

---

## 4. Tham số

### Các trường Subscription (thêm vào Shopping Order Form)

| Tên | Kiểu | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `subscription_enabled` | boolean | không | `false` | Checkbox: 定期購入にする |
| `subscription_cycle` | string | không | `monthly_1` | Dropdown chọn chu kỳ |
| `gmo_card_no` | string | có điều kiện | — | Số thẻ GMO (khi subscription + direct card) |
| `gmo_card_expire` | string | có điều kiện | — | Hạn thẻ GMO (MMYY) |
| `gmo_card_security_code` | string | có điều kiện | — | Mã bảo mật GMO |

### Giá trị chu kỳ Subscription

Được parse bởi `SubscriptionCycleParser` → `plan_type` + `interval_count`:

| Giá trị cycle | Plan Type | Interval Count | Nhãn hiển thị |
| --- | --- | --- | --- |
| `test_10m` | `test_minute` | 10 | テスト（10分） |
| `weekly_1` | `weekly` | 1 | 毎週 |
| `monthly_1` | `monthly` | 1 | 毎月 |
| `monthly_3` | `monthly` | 3 | 3ヶ月ごと |

---

## 5. Nguồn dữ liệu

### 5.1 Bảng chuẩn EC-CUBE

| Bảng | Mô tả | Sử dụng |
| --- | --- | --- |
| `dtb_customer` | Master khách hàng | Tài khoản khách hàng (yêu cầu đăng nhập) |
| `dtb_order` | Master đơn hàng | Đơn hàng gốc cho subscription |
| `dtb_order_item` | Sản phẩm đơn hàng | Nguồn dữ liệu snapshot cho SubscriptionItem |
| `dtb_shipping` | Giao hàng | Địa chỉ giao hàng của đơn gốc |
| `dtb_payment` | Phương thức thanh toán | Tham chiếu phương thức thanh toán |
| `dtb_product` | Master sản phẩm | Thông tin sản phẩm |
| `dtb_product_class` | SKU sản phẩm | Giá, tồn kho, mã sản phẩm |
| `dtb_product_stock` | Tồn kho | Kiểm tra tồn kho khi checkout |

### 5.2 Bảng Subscription tuỳ chỉnh (Đã implement)

> Được tạo tự động bởi `SubscriptionActivator` sau khi đơn hàng thành công.

| Bảng | Entity | Tạo bởi |
| --- | --- | --- |
| `dtb_subscription` | `Customize\Entity\Subscription` | `SubscriptionActivator` |
| `dtb_subscription_item` | `Customize\Entity\SubscriptionItem` | `SubscriptionActivator` |
| `dtb_subscription_event_log` | `Customize\Entity\SubscriptionEventLog` | `SubscriptionActivator` |

#### `dtb_subscription` — Các trường được điền khi tạo

| Cột | Nguồn | Ghi chú |
| --- | --- | --- |
| `customer_id` | `Order.Customer.id` | Từ khách hàng đang đăng nhập |
| `base_order_id` | `Order.id` | Đơn hàng checkout |
| `status` | `pending_activation` | Trạng thái ban đầu |
| `plan_type` | `SubscriptionCycleParser` → session payload | VD: `monthly` |
| `interval_count` | `SubscriptionCycleParser` → session payload | VD: `1` |
| `next_billing_at` | `SubscriptionScheduler::computeNext...()` | Tính toán từ plan |
| `next_fulfillment_at` | `SubscriptionScheduler::computeNext...()` | Tính toán từ plan |
| `subtotal_amount` | Tổng các order items | Snapshot từ đơn hàng |
| `discount_amount` | Giảm giá đơn hàng | Snapshot từ đơn hàng |
| `shipping_fee` | Phí vận chuyển | Snapshot từ đơn hàng |
| `tax_amount` | Thuế đơn hàng | Snapshot từ đơn hàng |
| `total_amount` | Tổng đơn hàng | Snapshot từ đơn hàng |
| `payment_gateway` | `'gmo'` | Mặc định |
| `gmo_member_id` | Đăng ký GMO | Từ thanh toán checkout |
| `gmo_card_seq` | Đăng ký GMO | Từ thanh toán checkout |

#### `dtb_subscription_item` — Các trường được điền khi tạo

| Cột | Nguồn | Ghi chú |
| --- | --- | --- |
| `subscription_id` | Subscription.id mới tạo | FK |
| `product_id` | `OrderItem.Product.id` | Từ sản phẩm đơn hàng |
| `product_class_id` | `OrderItem.ProductClass.id` | Từ sản phẩm đơn hàng |
| `product_name_snapshot` | `OrderItem.product_name` | Snapshot |
| `product_code_snapshot` | `OrderItem.product_code` | Snapshot |
| `quantity` | `OrderItem.quantity` | Snapshot |
| `unit_price_snapshot` | `OrderItem.price` | Snapshot |
| `tax_rate_snapshot` | `OrderItem.tax_rate` | Snapshot |

### 5.3 Routes

> Tạo subscription sử dụng các route Shopping có sẵn + tự động kích hoạt khi hoàn tất.

| Route | Phương thức | Mô tả | Vai trò Subscription |
| --- | --- | --- | --- |
| `/shopping` | GET | Trang thanh toán | Route EC-CUBE có sẵn |
| `/shopping/confirm` | GET | Trang xác nhận (hiển thị tuỳ chọn subscription) | Form extension thêm các trường subscription |
| `/shopping/checkout` | POST | Thực hiện checkout | Payload subscription lưu vào session |
| `/shopping/complete` | GET | Trang hoàn tất đơn hàng | `SubscriptionShoppingCompleteSubscriber` kích hoạt tạo subscription |
| `/mypage/subscriptions` | GET | Danh sách subscription (sau khi tạo) | Khách hàng xem subscription mới tại đây |

---

## 6. Trạng thái màn hình

### 6.1 Tuỳ chọn Subscription — Trạng thái mặc định

**Điều kiện:** Khách hàng ở trang xác nhận đơn hàng

**Giao diện:**

- ☐ 定期購入にする (chưa tick)
- Dropdown chu kỳ subscription (ẩn)
- Các trường thẻ GMO (ẩn hoặc quản lý bởi phần thanh toán)

### 6.2 Trạng thái bật Subscription

**Điều kiện:** Khách hàng tick checkbox subscription_enabled

**Giao diện:**

- ☑ 定期購入にする (đã tick)
- Dropdown chu kỳ subscription (hiển thị): 毎週 / 毎月 / 3ヶ月ごと
- Các trường thẻ GMO: hiển thị nếu cần nhập thẻ trực tiếp

### 6.3 Hoàn tất đơn hàng — Subscription đã tạo

**Điều kiện:** Checkout thành công, `SubscriptionActivator` chạy thành công

**Giao diện:**

- Trang hoàn tất đơn hàng (có sẵn)
- Tuỳ chọn: thông báo "定期購入が作成されました"
- Liên kết đến quản lý subscription: `/mypage/subscriptions`

### 6.4 Trạng thái lỗi

| Trường hợp | Điều kiện | Giao diện | Hành động |
| --- | --- | --- | --- |
| Thanh toán GMO thất bại | Thẻ bị từ chối / không hợp lệ | Lỗi checkout (EC-CUBE có sẵn) | Thử lại thanh toán |
| Kích hoạt subscription thất bại | Exception từ `SubscriptionActivator` | Đơn hàng tạo nhưng subscription KHÔNG tạo được | Admin cần kiểm tra / tạo thủ công |
| Hết hàng | Tồn kho = 0 khi checkout | Lỗi validate giỏ hàng (có sẵn) | Xoá hoặc điều chỉnh số lượng |
| Hết phiên đăng nhập | Payload subscription bị mất | Checkout không có subscription | Khách hàng cần tick lại |
| Giá trị cycle không hợp lệ | `SubscriptionCycleParser` parse lỗi | Mặc định monthly_1 hoặc từ chối | Validate |

---

## 7. Bố cục giao diện

> Tuỳ chọn subscription là **khối nhúng** trong trang xác nhận đơn hàng có sẵn.

### 7.1 Bố cục khối tuỳ chọn Subscription

| STT | Thành phần | Mô tả | Hành động |
| --- | --- | --- | --- |
| 1 | Checkbox Subscription | 定期購入にする | Bật/tắt chế độ subscription |
| 2 | Dropdown chu kỳ | お届けサイクル | Chọn chu kỳ |
| 3 | Mô tả chu kỳ | 選択したサイクルの説明 | Văn bản tĩnh |
| 4 | Ngày giao hàng đầu tiên | 初回お届け予定日 | Hiển thị tính toán |
| 5 | Ngày thanh toán đầu tiên | 初回お支払い予定日 | Hiển thị tính toán |
| 6 | Lưu ý Subscription | 定期購入に関する注意事項 | Văn bản tĩnh |

### 7.2 Vị trí trong trang Shopping

```
Trang xác nhận đơn hàng (Shopping Confirm)
├─ ... (tổng quan đơn hàng, giao hàng, v.v. có sẵn)
├─ ★ Khối tuỳ chọn Subscription ← thêm bởi ShoppingOrderTypeExtension
│   ├─ ☐ 定期購入にする
│   ├─ お届けサイクル: [毎月 ▼]
│   └─ Văn bản lưu ý
├─ ... (phần thanh toán, nút xác nhận có sẵn, v.v.)
```

### 7.3 Responsive

| Mục | Giá trị |
| --- | --- |
| Responsive | Có (theo bố cục trang Shopping) |
| Desktop | Inline trong form xác nhận |
| Mobile | Full width, theo responsive Shopping có sẵn |

---

## 8. Chi tiết thành phần UI

### Thành phần: Checkbox Subscription

| Phần tử | Tên trường | Mô tả | Loại |
| --- | --- | --- | --- |
| `subscription_enabled` | `subscription_enabled` | 定期購入にする | checkbox |

### Thành phần: Dropdown chu kỳ

| Phần tử | Tên trường | Mô tả | Loại |
| --- | --- | --- | --- |
| `subscription_cycle` | `subscription_cycle` | お届けサイクル | select/dropdown |

**Các tuỳ chọn:**

| Giá trị | Nhãn | plan_type | interval_count |
| --- | --- | --- | --- |
| `weekly_1` | 毎週 | `weekly` | `1` |
| `monthly_1` | 毎月 | `monthly` | `1` |
| `monthly_3` | 3ヶ月ごと | `monthly` | `3` |
| `test_10m` | テスト（10分） | `test_minute` | `10` |

> `test_10m` chỉ hiển thị khi `SUBSCRIPTION_ALLOW_TEST_INTERVAL=true`.

### Thành phần: Các trường thẻ GMO (có điều kiện)

| Phần tử | Tên trường | Mô tả | Loại |
| --- | --- | --- | --- |
| `gmo_card_no` | `gmo_card_no` | カード番号 | text input |
| `gmo_card_expire` | `gmo_card_expire` | 有効期限 (MMYY) | text input |
| `gmo_card_security_code` | `gmo_card_security_code` | セキュリティコード | text input |

> Các trường thẻ hiển thị tuỳ thuộc vào cấu hình phương thức thanh toán. Có thể dùng GMO token thay thế nhập thẻ trực tiếp.

---

## 9. Logic nghiệp vụ

### Luồng Checkout với Subscription

```
1. Khách hàng tại trang xác nhận đơn hàng
   ↓
2. Tick ☑ subscription_enabled
   ↓
3. Chọn subscription_cycle (VD: monthly_1)
   ↓
4. Nhập/xác nhận thanh toán (thẻ GMO)
   ↓
5. Click 注文する (xác nhận đơn hàng)
   ↓
6. ShoppingOrderTypeExtension.onSubmit():
   - Parse subscription_cycle → plan_type + interval_count (qua SubscriptionCycleParser)
   - Lưu payload subscription vào session
   ↓
7. EC-CUBE thực hiện thanh toán + tạo Order
   ↓
8. SubscriptionShoppingCompleteSubscriber lắng nghe FRONT_SHOPPING_COMPLETE_INITIALIZE:
   - Đọc payload subscription từ session
   - Gọi SubscriptionActivator::activate(Order, payload)
   ↓
9. SubscriptionActivator:
   - Tạo entity Subscription (status = pending_activation)
   - Tạo các entity SubscriptionItem từ OrderItems
   - Tính next_billing_at, next_fulfillment_at qua SubscriptionScheduler
   - Lưu thông tin GMO (gmo_member_id, gmo_card_seq)
   - Ghi log sự kiện kích hoạt vào SubscriptionEventLog
   - Flush vào database
   ↓
10. Hiển thị trang hoàn tất đơn hàng
    - Khách hàng có thể vào /mypage/subscriptions để xem subscription mới
```

### Trạng thái Subscription sau khi tạo

| Thời điểm | Trạng thái |
| --- | --- |
| Ngay sau khi kích hoạt | `pending_activation` |
| Sau khi chu kỳ thanh toán đầu tiên chạy (`subscription:run`) | `active` |
| Nếu thanh toán đầu tiên thất bại | `past_due` |

### Quy tắc Validate

```
Nếu subscription_enabled == true:
    subscription_cycle phải hợp lệ (parse được bởi SubscriptionCycleParser)
    Phương thức thanh toán phải là GMO credit card (subscription yêu cầu card-on-file)
    Khách hàng phải đăng nhập (không phải khách vãng lai)

Nếu SUBSCRIPTION_ALLOW_TEST_INTERVAL != true:
    Chu kỳ test_10m không khả dụng

Nếu SUBSCRIPTION_ENABLED != true:
    Tuỳ chọn subscription ẩn khỏi checkout
```

### Snapshot số tiền

> Tại thời điểm tạo, `SubscriptionActivator` snapshot toàn bộ số tiền từ Order:

| Trường Subscription | Nguồn |
| --- | --- |
| `subtotal_amount` | Tổng `OrderItem.price × quantity` |
| `discount_amount` | Giảm giá đơn hàng |
| `shipping_fee` | Phí vận chuyển đơn hàng |
| `tax_amount` | Thuế đơn hàng |
| `total_amount` | Tổng đơn hàng |

### Tính toán ngày

> Xử lý bởi `SubscriptionScheduler`:

| Trường ngày | Cách tính |
| --- | --- |
| `next_fulfillment_at` | Dựa trên `plan_type` + `interval_count` từ ngày đặt hàng |
| `next_billing_at` | `next_fulfillment_at - 1 ngày` (mô hình pre-billing) |

---

## 10. Phân quyền

| Mục | Giá trị |
| --- | --- |
| Yêu cầu đăng nhập | Có (subscription yêu cầu tài khoản khách hàng) |
| Truy cập khách vãng lai | Không |
| Vai trò khách hàng | Có |
| Yêu cầu CSRF | Có (xử lý bởi form checkout Shopping) |
| Validate thanh toán | Yêu cầu GMO credit card cho subscription |

---

## 11. Trường hợp biên

| Trường hợp | Xử lý |
| --- | --- |
| Khách vãng lai thử dùng subscription | Ẩn tuỳ chọn subscription cho guest checkout |
| Chọn phương thức thanh toán không phải GMO | Ẩn hoặc vô hiệu hoá tuỳ chọn subscription |
| Sản phẩm hết hàng khi checkout | Validate giỏ hàng chặn checkout (EC-CUBE có sẵn) |
| Mất session giữa xác nhận và hoàn tất | Payload subscription bị mất → đơn hàng tạo không có subscription |
| Exception từ `SubscriptionActivator` | Đơn hàng thành công nhưng subscription KHÔNG tạo được → admin cần xử lý |
| Gửi đơn trùng lặp (double submit) | Chống double-submit có sẵn của EC-CUBE |
| `SUBSCRIPTION_ENABLED=false` | Khối tuỳ chọn subscription không render |
| Giá trị cycle không hợp lệ | `SubscriptionCycleParser` trả về null → từ chối hoặc dùng mặc định |
| Thanh toán GMO bị từ chối | Checkout thất bại hoàn toàn (không có đơn hàng, không có subscription) |
| Khách hàng đã có subscription đang hoạt động | Cho phép — hỗ trợ nhiều subscription cho mỗi khách hàng |
| Nhấn nút Back sau khi hoàn tất | EC-CUBE có sẵn ngăn chặn re-checkout |

---

## 12. Kịch bản kiểm thử

### Luồng chính (Happy Path)

| ID | Kịch bản | Kết quả mong đợi |
| --- | --- | --- |
| TC-001 | Checkout không có subscription | Đơn hàng bình thường, không tạo subscription |
| TC-002 | Checkout có subscription (hàng tháng) | Đơn hàng + Subscription được tạo |
| TC-003 | Checkout có subscription (hàng tuần) | Đơn hàng + Subscription với plan weekly |
| TC-004 | Xác nhận subscription trong MyPage | Subscription mới hiển thị tại `/mypage/subscriptions` |
| TC-005 | Xác nhận SubscriptionItems | Items khớp với OrderItems |
| TC-006 | Xác nhận snapshot số tiền | Số tiền Subscription khớp với số tiền Order |
| TC-007 | Xác nhận tính toán ngày | `next_billing_at` = `next_fulfillment_at - 1 ngày` |
| TC-008 | Xác nhận SubscriptionEventLog | Sự kiện `activated` được ghi log |

### Lỗi

| ID | Kịch bản | Kết quả mong đợi |
| --- | --- | --- |
| TC-101 | Thanh toán GMO bị từ chối | Checkout thất bại, không tạo subscription |
| TC-102 | Giá trị cycle không hợp lệ | Lỗi validate hoặc dùng mặc định |
| TC-103 | Khách vãng lai checkout có subscription | Tuỳ chọn không khả dụng |
| TC-104 | `SUBSCRIPTION_ENABLED=false` | Tuỳ chọn không hiển thị |
| TC-105 | Hết phiên đăng nhập | Payload subscription bị mất |
| TC-106 | Exception từ SubscriptionActivator | Đơn hàng OK, subscription thiếu → cảnh báo admin |

### Trường hợp biên

| ID | Kịch bản | Kết quả mong đợi |
| --- | --- | --- |
| TC-201 | Gửi đơn trùng lặp | Chỉ tạo một đơn hàng + subscription |
| TC-202 | Nhiều subscription | Mỗi lần checkout tạo subscription riêng |
| TC-203 | Chu kỳ test (test_10m) | Chỉ khả dụng khi `SUBSCRIPTION_ALLOW_TEST_INTERVAL=true` |
| TC-204 | Đơn hàng lớn (nhiều sản phẩm) | Tất cả sản phẩm được snapshot vào SubscriptionItems |
| TC-205 | Nhấn Back sau khi hoàn tất | Không tạo trùng |

---

## 13. Ghi chú triển khai EC-CUBE

### 13.1 Phần đã triển khai (ĐÃ IMPLEMENT)

#### Form Extension

**File:** `app/Customize/Form/Extension/ShoppingOrderTypeExtension.php`

| Trường | Kiểu | Mô tả |
| --- | --- | --- |
| `subscription_enabled` | CheckboxType | Checkbox 定期購入にする |
| `subscription_cycle` | ChoiceType | Dropdown chu kỳ (monthly_1, weekly_1, test_10m, v.v.) |
| `gmo_card_no` | TextType | Số thẻ GMO (có điều kiện) |
| `gmo_card_expire` | TextType | Hạn thẻ GMO (có điều kiện) |
| `gmo_card_security_code` | TextType | Mã bảo mật GMO (có điều kiện) |

> Sự kiện `onSubmit`: parse cycle, lưu payload subscription vào session.

#### Event Subscriber

**File:** `app/Customize/EventSubscriber/Subscription/SubscriptionShoppingCompleteSubscriber.php`

| Sự kiện | Hành động |
| --- | --- |
| `FRONT_SHOPPING_COMPLETE_INITIALIZE` | Đọc payload từ session → gọi `SubscriptionActivator::activate()` |

#### Services

| Service | File | Mô tả |
| --- | --- | --- |
| `SubscriptionActivator` | `app/Customize/Service/Subscription/SubscriptionActivator.php` | Tạo Subscription + SubscriptionItems từ Order |
| `SubscriptionCycleParser` | `app/Customize/Service/Subscription/SubscriptionCycleParser.php` | Parse giá trị cycle → `plan_type` + `interval_count` |
| `SubscriptionScheduler` | `app/Customize/Service/Subscription/SubscriptionScheduler.php` | Tính toán `next_billing_at`, `next_fulfillment_at` |

#### Template

**File:** `app/template/default/Shopping/confirm.twig` (đã sửa)

> Dòng ~150-166: thêm checkbox subscription + dropdown chu kỳ vào trang xác nhận.

#### Biến môi trường

| Biến | Mô tả |
| --- | --- |
| `SUBSCRIPTION_ENABLED` | Bật/tắt tính năng subscription toàn hệ thống |
| `SUBSCRIPTION_ALLOW_TEST_INTERVAL` | Hiển thị tuỳ chọn chu kỳ test_10m |
| `SUBSCRIPTION_MAX_RETRY` | Số lần thử thanh toán tối đa |
| `SUBSCRIPTION_RETRY_DAYS` | Lịch thử lại (VD: `1,3,7`) |

### 13.2 Phần KHÔNG triển khai

| Tính năng | Trạng thái | Ghi chú |
| --- | --- | --- |
| Wizard tạo subscription riêng trong MyPage | ❌ Không implement | Tạo subscription qua checkout |
| Bảng cấu hình sản phẩm subscription | ❌ Không implement | Mọi sản phẩm đều có thể subscription |
| Wizard chọn sản phẩm theo bước | ❌ Không implement | Dùng giỏ hàng/checkout bình thường |
| Chọn phương thức thanh toán đã lưu | ❌ Không implement | Nhập thẻ GMO tại checkout |

### 13.3 Tích hợp MyPage

> Sau khi subscription được tạo, khách hàng xem/quản lý tại:

| Màn hình | Route | Tài liệu |
| --- | --- | --- |
| Danh sách subscription | `/mypage/subscriptions` | `EC-CUBE_MyPage_Subscription_Management_Spec_Updated.md` |
| Chi tiết subscription | `/mypage/subscriptions/{id}` | `EC-CUBE_MyPage_Subscription_Detail_Spec_Updated.md` |

> CTA trên MyPage ("新しい定期コースを申し込む") nên điều hướng khách hàng về trang danh sách sản phẩm hoặc trang sản phẩm subscription cụ thể, **không phải** MyPage wizard.

---

*Cập nhật lần cuối: 2026-05-11 15:48:00*
