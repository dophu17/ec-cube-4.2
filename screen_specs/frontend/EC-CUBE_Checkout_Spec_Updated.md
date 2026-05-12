# EC-CUBE Màn Hình Checkout (Spec Updated)

> Phiên bản lean spec cho AI coding (giữ nguyên sections 3 và 7 theo yêu cầu).

**Đối tượng**: Trang checkout EC-CUBE / luồng shopping
**Mục đích**: Tài liệu đặc tả cập nhật cho Notion với mapping API / bảng EC-CUBE, luồng thanh toán GMO, và đề xuất bảng tuỳ chỉnh.

---

## 1. Tổng Quan

### Mục đích

- Hiển thị thông tin khách hàng & địa chỉ giao hàng (chỉ đọc)
- Cho phép chọn phương thức thanh toán
- Nhập thông tin thẻ nếu chọn credit card (GMO Direct)
- Cho phép chọn mua định kỳ (subscription) nếu tính năng được bật
- Xác nhận thông tin đơn hàng trước khi chuyển sang bước Xác nhận

### Vai trò trong hệ thống

```
Giỏ hàng → Checkout (注文手続き) → Xác nhận (確認) → Hoàn thành (完了)
```

### User

- Guest (非会員 - Khách vãng lai)
- Khách hàng đã đăng nhập (会員 - Thành viên)

---

## 2. Phạm Vi

### Trong phạm vi

- Thanh tiến trình checkout
- Hiển thị thông tin khách hàng (chỉ đọc, inline edit cho guest)
- Hiển thị địa chỉ giao hàng (chỉ đọc, thay đổi qua redirect)
- Chọn phương thức giao hàng / ngày giao / thời gian giao
- Chọn phương thức thanh toán
- Nhập credit card (GMO Direct fields: `gmo_card_no`, `gmo_card_expire`, `gmo_card_security_code`)
- Tuỳ chọn Subscription (checkbox + dropdown chu kỳ) — ✅ Đã triển khai
- Hiển thị tóm tắt đơn hàng
- Sử dụng điểm (nếu được bật)
- Nhập tin nhắn
- CTA chuyển sang xác nhận
- Badge tin cậy & bảo mật

### Ngoài phạm vi

- Chỉnh sửa trực tiếp thông tin khách hàng cho thành viên (chỉ redirect)
- Chỉnh sửa địa chỉ inline
- Hoàn thành đơn hàng (nằm ở Xác nhận → Hoàn thành)
- Chi tiết quản lý subscription (nằm ở MyPage)

---

## 3. Cấu Trúc Màn Hình

| Thành phần | Mô tả |
| --- | --- |
| Thanh tiến trình | 1 カート (Giỏ hàng) → 2 (お客様情報 - Thông tin KH) → 3 ご注文手続き (Đặt hàng) → 4 確認 (Xác nhận) → 5 完了 (Hoàn thành) |
| Thông tin khách hàng | Thông tin KH (chỉ đọc cho thành viên, inline edit cho guest) |
| Địa chỉ giao hàng | Địa chỉ giao hàng + thay đổi qua redirect |
| Tuỳ chọn giao hàng | Chọn nhà vận chuyển / ngày giao / thời gian giao |
| Phương thức thanh toán | Chọn phương thức thanh toán (radio) |
| Form thẻ tín dụng | GMO Direct card fields (hiển thị trên trang Xác nhận) |
| Tuỳ chọn Subscription | Checkbox + dropdown chu kỳ (hiển thị trên trang Xác nhận, chỉ cho thành viên) |
| Tóm tắt đơn hàng | Tóm tắt đơn hàng + điểm |
| Tin nhắn | Tin nhắn cho đơn hàng |
| Nút CTA | 注文内容を確認する (Xác nhận nội dung đơn hàng → trang xác nhận) |
| Tin cậy & Bảo mật | SSL / DigiCert / TRUSTe |

> **Lưu ý**: EC-CUBE chia thành 2 trang: **Shopping/index.twig** (chọn payment/delivery/point) và **Shopping/confirm.twig** (xác nhận + nhập thẻ + subscription). Form thẻ tín dụng và Tuỳ chọn Subscription nằm ở **trang Xác nhận**, không phải trang Index.

---

## 4. Tham Số

### 4.1 Form Shopping Index (`OrderType`)

| Tên | Kiểu | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `Payment` | EntityType | có | - | Chọn phương thức thanh toán (radio) |
| `Shippings[].Delivery` | EntityType | có | - | Nhà vận chuyển cho mỗi lần giao |
| `Shippings[].shipping_delivery_date` | ChoiceType | không | - | Ngày giao hàng |
| `Shippings[].DeliveryTime` | EntityType | không | - | Khung giờ giao hàng |
| `use_point` | IntegerType | không | 0 | Sử dụng điểm (nếu được bật, chỉ thành viên) |
| `message` | TextareaType | không | null | Tin nhắn đơn hàng |
| `redirect_to` | HiddenType | không | - | Đích redirect khi thay đổi địa chỉ / thanh toán |
| `_token` | HiddenType | có | - | CSRF token |

### 4.2 Trang Xác Nhận — Fields mở rộng (`ShoppingOrderTypeExtension`)

| Tên | Kiểu | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `gmo_card_no` | TextType | không* | null | Số thẻ GMO Direct. *Bắt buộc nếu payment = GMO Direct Credit Card |
| `gmo_card_expire` | TextType | không* | null | Hạn thẻ (YYMM). *Bắt buộc nếu payment = GMO Direct |
| `gmo_card_security_code` | TextType | không* | null | Security code. *Bắt buộc nếu payment = GMO Direct |
| `subscription_enabled` | CheckboxType | không | false | Mua định kỳ (subscription). Chỉ hiển thị khi `subscriptionEnabled = true` và user là thành viên |
| `subscription_cycle` | ChoiceType | không | null | Chu kỳ thanh toán: `monthly_1`, `monthly_3`, `weekly_1`, `test_10m` (chỉ test) |

> **Lưu ý**: Tất cả extended fields đều `mapped: false` — không lưu trực tiếp vào Order entity. Dữ liệu subscription lưu qua Session → `SubscriptionShoppingCompleteSubscriber` xử lý sau khi checkout.

---

## 5. Nguồn Dữ Liệu

### 5.1 Bảng chuẩn EC-CUBE

| Bảng | Mô tả | Sử dụng |
| --- | --- | --- |
| `dtb_customer` | Thông tin khách hàng | Tên / email / điện thoại khách hàng thành viên |
| `dtb_customer_address` | Địa chỉ đã lưu | Sổ địa chỉ nếu thành viên thay đổi giao hàng |
| `dtb_cart` | Session giỏ hàng | Dữ liệu giỏ hàng trước checkout |
| `dtb_cart_item` | Mục giỏ hàng | Sản phẩm được chọn cho đơn hàng |
| `dtb_product` | Master sản phẩm | Tên / trạng thái sản phẩm |
| `dtb_product_class` | SKU / class sản phẩm | Giá, tồn kho, giới hạn bán |
| `dtb_product_stock` | Tồn kho sản phẩm | Kiểm tra tồn kho |
| `dtb_order` | Đơn hàng | Dữ liệu pre-order / đơn hàng trong luồng shopping |
| `dtb_order_item` | Mục đơn hàng | Line items đơn hàng |
| `dtb_shipping` | Vận chuyển | Địa chỉ giao hàng, ngày giao, thông tin giao hàng |
| `dtb_payment` | Phương thức thanh toán | Các phương thức thanh toán khả dụng |
| `dtb_delivery` | Phương thức giao hàng | Phương thức giao hàng / vận chuyển |
| `dtb_delivery_fee` | Phí giao hàng | Tính phí vận chuyển theo tỉnh / phương thức |
| `mtb_pref` | Master tỉnh/thành | Tỉnh/thành địa chỉ |
| `mtb_order_status` | Trạng thái đơn hàng | Vòng đời trạng thái đơn hàng |
| `mtb_order_item_type` | Loại mục đơn hàng | Các loại line: sản phẩm / vận chuyển / giảm giá / thuế |
| `dtb_base_info` | Cấu hình cửa hàng | Thuế / vận chuyển / miễn phí ship / điểm |

### 5.2 Bảng Plugin / Tuỳ Chỉnh

| Bảng / Plugin | Ghi chú |
| --- | --- |
| Plugin `EccubePaymentLite42` | Thanh toán GMO Epsilon. Dùng cho luồng token payment nếu cần |
| Plugin `RemisePayment42` | Thanh toán REMISE (CVS, v.v.). Routes: `/shopping/remise_payment` |
| Custom GMO Direct (`GmoApiClient`) | ✅ Đã triển khai trong `app/Customize/Service/Gmo/GmoApiClient.php`. Xử lý thanh toán thẻ trực tiếp |
| `dtb_subscription` | ✅ Đã triển khai. Dữ liệu subscription tạo bởi `SubscriptionActivator` sau checkout |
| `dtb_subscription_item` | ✅ Đã triển khai. Line items subscription |
| `dtb_subscription_order` | ✅ Đã triển khai. Bản ghi chu kỳ thanh toán, lưu `gmo_access_id` / `gmo_access_pass` |
| Điểm (EC-CUBE tích hợp sẵn) | Sử dụng `BaseInfo.isOptionPoint` + `Order.UsePoint` / `Order.AddPoint` |

### 5.3 Bảng Tuỳ Chỉnh — Đề xuất nếu chưa có

#### `dtb_checkout_payment_token`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int | có | PK |
| `order_id` | int | có | FK → `dtb_order.id` |
| `payment_method` | varchar | có | `credit_card` / `paypay` |
| `provider` | varchar | có | `gmo` / `paypay` |
| `token_reference` | varchar | có | Chỉ token/reference. **Không lưu dữ liệu thẻ thô** |
| `transaction_id` | varchar | không | ID giao dịch nhà cung cấp |
| `status` | varchar | có | `created` / `tokenized` / `failed` / `expired` |
| `error_code` | varchar | không | Mã lỗi nhà cung cấp |
| `error_message` | text | không | Lỗi nhà cung cấp đã sanitize |
| `expired_at` | datetime | không | Hạn token |
| `create_date` | datetime | có | Convention EC-CUBE |
| `update_date` | datetime | có | Convention EC-CUBE |

> **Lưu ý**: Chỉ tạo nếu payment plugin không có bảng lưu transaction/token reference. Hiện tại `dtb_subscription_order` đã lưu `gmo_access_id` / `gmo_access_pass` cho subscription billing. Không lưu `card_number` / `security_code`.

#### `dtb_checkout_selected_item`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int | có | PK |
| `cart_key` | varchar | có | Cart key guest/thành viên |
| `cart_item_id` | int | có | FK → `dtb_cart_item.id` |
| `is_selected` | boolean | có | Đã chọn từ giỏ hàng multi-select |
| `purchase_type` | varchar | có | `normal` / `subscription` |
| `create_date` | datetime | có | Convention EC-CUBE |
| `update_date` | datetime | có | Convention EC-CUBE |

> **Lưu ý**: Chỉ cần nếu Giỏ hàng có multi-select và lựa chọn cần persist qua Checkout. Nếu chỉ lưu session/frontend state thì không cần bảng.

#### `dtb_checkout_audit_log`

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int | có | PK |
| `order_id` | int | không | FK → `dtb_order.id` |
| `cart_key` | varchar | không | Cart key |
| `customer_id` | int | không | FK → `dtb_customer.id` |
| `event` | varchar | có | `open_checkout` / `select_payment` / `token_fail` |
| `metadata` | json/text | không | Metadata đã sanitize |
| `ip_address` | varchar | không | IP client |
| `user_agent` | text | không | Thông tin trình duyệt |
| `create_date` | datetime | có | Convention EC-CUBE |

> **Lưu ý**: Tuỳ chọn. Có thể dùng app log / analytics thay vì bảng DB.

### 5.4 API / Routes

#### Routes Shopping Core (`ShoppingController`)

| Route | Method | Tên Route | Auth | Mô tả | Trạng thái |
| --- | --- | --- | --- | --- | --- |
| `/shopping` | GET | `shopping` | Không/Có | Hiển thị trang checkout (index.twig) | ✅ Đã triển khai |
| `/shopping/redirect_to` | POST | `shopping_redirect_to` | Không/Có | Lưu form + redirect đến màn hình khác (shipping, v.v.) | ✅ Đã triển khai |
| `/shopping/confirm` | POST | `shopping_confirm` | Không/Có | Hiển thị trang xác nhận sau validation | ✅ Đã triển khai |
| `/shopping/checkout` | POST | `shopping_checkout` | Không/Có | Hoàn thành đơn hàng từ bước xác nhận | ✅ Đã triển khai |
| `/shopping/complete` | GET | `shopping_complete` | Không/Có | Hiển thị trang hoàn thành | ✅ Đã triển khai |
| `/shopping/shipping/{id}` | GET/POST | `shopping_shipping` | Có | Thay đổi địa chỉ giao hàng (thành viên) | ✅ Đã triển khai |
| `/shopping/shipping_edit/{id}` | GET/POST | `shopping_shipping_edit` | Không | Thay đổi địa chỉ giao hàng (guest) | ✅ Đã triển khai |
| `/shopping/login` | GET | `shopping_login` | Không | Trang đăng nhập cho checkout | ✅ Đã triển khai |
| `/shopping/error` | GET | `shopping_error` | Không | Trang lỗi shopping | ✅ Đã triển khai |

#### Routes Khách Vãng Lai (`NonMemberShoppingController`)

| Route | Method | Tên Route | Auth | Mô tả | Trạng thái |
| --- | --- | --- | --- | --- | --- |
| `/shopping/nonmember` | GET/POST | `shopping_nonmember` | Không | Nhập thông tin KH guest (nonmember.twig) | ✅ Đã triển khai |
| `/shopping/customer` | POST | `shopping_customer` | Không | AJAX cập nhật thông tin KH guest | ✅ Đã triển khai |

#### Routes Thanh Toán Plugin

| Route | Method | Tên Route | Plugin | Mô tả |
| --- | --- | --- | --- | --- |
| `/shopping/remise_payment` | POST | `remise_payment` | RemisePayment42 | Xử lý thanh toán REMISE |
| `/remise_payment4_back` | GET | `remise_payment4_back` | RemisePayment42 | Quay lại / huỷ thanh toán |
| `/remise_payment4_result_card` | GET | `remise_payment4_result_card` | RemisePayment42 | Callback kết quả thanh toán thẻ |
| `/remise_payment4_result_cvs` | GET | `remise_payment4_result_cvs` | RemisePayment42 | Callback kết quả thanh toán CVS |

### 5.5 Dữ liệu mẫu — Tóm tắt đơn hàng (Twig context)

```twig
{# Các biến tóm tắt đơn hàng có sẵn trong template Shopping #}
{{ Order.subtotal|price }}           {# Tạm tính #}
{{ Order.charge|price }}             {# Phí phương thức thanh toán #}
{{ Order.deliveryFeeTotal|price }}   {# Tổng phí giao hàng #}
{{ Order.taxable_discount|price }}   {# Giảm giá (nếu < 0) #}
{{ Order.taxable_total|price }}      {# Tổng bao gồm thuế #}
{{ Order.payment_total|price }}      {# Tổng thanh toán cuối cùng #}
{{ Order.UsePoint }}                 {# Điểm đã dùng #}
{{ Order.AddPoint }}                 {# Điểm nhận được #}
{{ Order.total_by_tax_rate }}        {# Phân tích theo thuế suất #}
{{ Order.tax_by_tax_rate }}          {# Số tiền thuế theo thuế suất #}
```

---

## 6. Trạng Thái Màn Hình

### 6.1 Trạng thái ban đầu

- Giỏ hàng tồn tại và có mục hợp lệ
- Bước 3 đang active (ご注文手続き - Đặt hàng)
- Thông tin KH chỉ đọc (thành viên) hoặc inline edit (guest)
- Địa chỉ giao hàng chỉ đọc + nút thay đổi (redirect)
- Phương thức giao hàng / ngày / thời gian có thể chọn
- Chọn phương thức thanh toán bằng radio
- Tóm tắt đơn hàng hiển thị tạm tính / phí ship / phí thanh toán / giảm giá / tổng / điểm
- Textarea tin nhắn

### 6.2 Trạng thái đang tải

- Tải dữ liệu checkout (`OrderHelper.initializeOrder`)
- `PurchaseFlow` validate + tính toán
- Khi thay đổi delivery/payment → submit qua `shopping_redirect_to` → tính lại
- Vô hiệu hoá CTA trong khi xử lý (loading overlay)

### 6.3 Trạng thái trống

- Giỏ hàng trống → chuyển hướng `/cart`
- `OrderHelper.verifyCart()` thất bại → chuyển hướng `/cart`

### 6.4 Trạng thái lỗi

| Trường hợp | Điều kiện | UI | Thử lại / Điều hướng |
| --- | --- | --- | --- |
| Lỗi PurchaseFlow | `flowResult.hasError()` | Chuyển hướng trang lỗi | `/shopping/error` |
| Cảnh báo PurchaseFlow | `flowResult.hasWarning()` | Thông báo cảnh báo, đồng bộ giỏ hàng | Tiếp tục checkout |
| Lỗi validation | Form không hợp lệ | Lỗi inline | Sửa input |
| Giỏ hàng trống | Không có mục | Chuyển hướng giỏ hàng | `/cart` |
| Tồn kho không khớp | Tồn kho đã thay đổi | Hiển thị cảnh báo mục + `PurchaseFlow` tính lại | Quay lại giỏ / tải lại |
| Giá thay đổi | Giá đã tính lại | Hiển thị tóm tắt cập nhật | Xác nhận lại |
| Session hết hạn | `pre_order_id` / session không hợp lệ | Lỗi session | Tải lại / giỏ hàng |
| Lỗi thanh toán | Thẻ GMO thất bại (ở xác nhận) | Hiển thị lỗi thanh toán | Ở lại trang xác nhận |
| Phương thức không khả dụng | Thanh toán bị admin vô hiệu hoá | Hiển thị lỗi | Chọn phương thức khác |

---

## 7. Bố Cục UI

### 7.1 Cấu trúc bố cục

```
Trang Shopping Index (index.twig)
├── Thanh tiến trình (ec-progress)
├── Thông báo cảnh báo (alert.twig include)
├── Form (action → shopping_confirm)
│   ├── ec-orderRole__detail
│   │   ├── Thông tin KH (ec-orderAccount)
│   │   │   └── Guest: inline edit + AJAX cập nhật
│   │   ├── Giao hàng (ec-orderDelivery)
│   │   │   ├── Sản phẩm theo lần giao
│   │   │   ├── Địa chỉ giao hàng + nút thay đổi (redirect)
│   │   │   ├── Bộ chọn nhà vận chuyển
│   │   │   ├── Bộ chọn ngày giao
│   │   │   ├── Bộ chọn thời gian giao
│   │   │   └── Nút giao nhiều địa chỉ
│   │   ├── Phương thức thanh toán (ec-orderPayment, radio)
│   │   ├── Sử dụng điểm (nếu được bật + thành viên)
│   │   └── Nhập tin nhắn (ec-orderConfirm)
│   └── ec-orderRole__summary
│       └── Hộp tổng (ec-totalBox)
│           ├── Tạm tính / Phí TT / Phí ship / Giảm giá / Thuế
│           ├── Tổng thanh toán
│           ├── Điểm (Đã dùng / Nhận được)
│           └── CTA: 注文内容を確認する (Xác nhận nội dung đơn hàng)
└── Link quay lại giỏ hàng
```

```
Trang Shopping Xác Nhận (confirm.twig)
├── Thanh tiến trình (ec-progress, bước 4 active)
├── Form (action → shopping_checkout)
│   ├── ec-orderRole__detail
│   │   ├── Thông tin KH (chỉ đọc)
│   │   ├── Thông tin giao hàng (chỉ đọc)
│   │   ├── Thông tin thanh toán (chỉ đọc, hiển thị phương thức + phí)
│   │   ├── 🔹 Tuỳ chọn Subscription (nếu được bật + thành viên)
│   │   │   ├── subscription_enabled checkbox
│   │   │   └── subscription_cycle dropdown
│   │   ├── 🔹 Fields thẻ GMO (nếu payment = GmoDirectCreditCard)
│   │   │   ├── gmo_card_no
│   │   │   ├── gmo_card_expire (YYMM)
│   │   │   └── gmo_card_security_code
│   │   ├── Thông tin điểm (chỉ đọc)
│   │   ├── Tin nhắn (chỉ đọc)
│   │   └── Thông tin luật thương mại
│   └── ec-orderRole__summary
│       └── Hộp tổng (giống index)
│           └── CTA: ご注文完了 (Hoàn thành đơn hàng)
└── Link quay lại 注文手続き (Đặt hàng)
```

### 7.2 Bố cục ban đầu — Trang Index (CHÚ THÍCH)

| STT | Thành phần | Mô tả | Hành động |
| --- | --- | --- | --- |
| 1 | Thanh tiến trình | Chỉ báo bước (ec-progress) | Tĩnh |
| 2 | Thông tin KH | Tên, email, điện thoại, địa chỉ | Chỉ đọc (thành viên) / Chỉnh sửa (guest) |
| 3 | Địa chỉ giao hàng | Thông tin địa chỉ theo lần giao | Thay đổi → redirect |
| 4 | Sản phẩm | Sản phẩm theo lần giao | Chỉ đọc |
| 5 | Nhà vận chuyển | 配送方法 (Phương thức giao hàng) | Chọn (trigger change) |
| 6 | Ngày giao | お届け日 (Ngày giao hàng) | Chọn |
| 7 | Thời gian giao | お届け時間 (Thời gian giao hàng) | Chọn |
| 8 | Phương thức thanh toán | Chọn radio | Chuyển đổi (trigger change → tính lại) |
| 9 | Sử dụng điểm | 利用ポイント (Điểm sử dụng) | Input (thành viên + điểm được bật) |
| 10 | Tin nhắn | その他お問い合わせ (Yêu cầu khác) | Textarea |
| 11 | Tóm tắt đơn hàng | Phân tích giá | Tự động tính lại |
| 12 | CTA | 注文内容を確認する (Xác nhận nội dung đơn hàng) | Validate + submit → xác nhận |
| 13 | Quay lại giỏ hàng | カートに戻る (Quay lại giỏ hàng) | Link tới `/cart` |

### 7.3 Responsive

| Thiết bị | Bố cục |
| --- | --- |
| Desktop | 2 cột (`ec-orderRole__detail` + `ec-orderRole__summary`) |
| Tablet | 2 cột hoặc xếp chồng |
| Mobile | 1 cột |
| CTA | Sticky bottom trên mobile |

---

## 8. Chi Tiết Thành Phần UI

### Thông tin khách hàng

| Phần tử | Kiểu | Mô tả |
| --- | --- | --- |
| `name01` + `name02` | text | 山田 太郎 |
| `kana01` + `kana02` | text | ヤマダ タロウ |
| `companyName` | text | 会社名 (Tên công ty) |
| `postal_code` | text | 〒123-4567 |
| `pref` + `addr01` + `addr02` | text | 東京都千代田区... |
| `phone_number` | text | 090-xxxx |
| `email` | text | sample@email.com |

- **Thành viên**: Chỉ đọc
- **Guest**: Inline edit qua AJAX → `POST /shopping/customer` (`shopping_customer`)

### Phương thức thanh toán

| Phần tử | Kiểu | Mô tả |
| --- | --- | --- |
| Payment radio | EntityType radio | Danh sách `dtb_payment` khả dụng cho đơn hàng |

> Phương thức thanh toán được render qua `form.Payment` — mỗi option là một `Payment` entity. Khi thay đổi → trigger `data-trigger="change"` → submit qua `shopping_redirect_to` → tính lại phí.

### Form thẻ tín dụng (GMO Direct — trên trang Xác nhận)

| Trường | Tên form | Kiểu | Ghi chú |
| --- | --- | --- | --- |
| カード番号 (Số thẻ) | `gmo_card_no` | TextType | `inputmode="numeric"`, `autocomplete="cc-number"`. **Không lưu vào DB** |
| 有効期限 (Hạn thẻ) | `gmo_card_expire` | TextType | Format: YYMM. `autocomplete="cc-exp"`. **Không lưu vào DB** |
| セキュリティコード (Mã bảo mật) | `gmo_card_security_code` | TextType | `autocomplete="cc-csc"`. **Không lưu vào DB** |

> Chỉ hiển thị khi `isGmoDirect = true` (kiểm tra `Order.Payment.methodClass` chứa `'GmoDirectCreditCard'`). Tất cả fields đều `mapped: false`.

### Tuỳ chọn Subscription (trên trang Xác nhận)

| Trường | Tên form | Kiểu | Ghi chú |
| --- | --- | --- | --- |
| Mua định kỳ | `subscription_enabled` | CheckboxType | Label: "Mua định kỳ (subscription)" |
| Chu kỳ thanh toán | `subscription_cycle` | ChoiceType | Choices: `monthly_1`, `monthly_3`, `weekly_1`, `test_10m` (test) |

> Chỉ hiển thị khi `is_granted('ROLE_USER')` và `form.subscription_enabled is defined` (tức `subscriptionEnabled = true` trong config). Dữ liệu lưu vào Session, xử lý bởi `SubscriptionShoppingCompleteSubscriber`.

### Tóm tắt đơn hàng

| Mục | Biến twig | Ví dụ | Ghi chú |
| --- | --- | --- | --- |
| Tạm tính | `Order.subtotal` | ¥13,940 | 小計 |
| Phí thanh toán | `Order.charge` | ¥0 | 手数料 (Phí phương thức thanh toán) |
| Phí giao hàng | `Order.deliveryFeeTotal` | 無料 (Miễn phí) | 送料 |
| Giảm giá | `Order.taxable_discount` | -¥796 | Hiển thị nếu < 0 |
| Tổng cộng | `Order.taxable_total` | ¥13,144 | 合計 (税込) (Tổng bao gồm thuế) |
| Tổng thanh toán | `Order.payment_total` | ¥13,144 | お支払い合計 (Tổng thanh toán) |
| Phân tích thuế | `Order.total_by_tax_rate` | - | Phân tích theo thuế suất |
| Điểm đã dùng | `Order.UsePoint` | 0 pt | Chỉ thành viên + điểm được bật |
| Điểm nhận được | `Order.AddPoint` | 131 pt | Chỉ thành viên + điểm được bật |

---

## 9. Logic Nghiệp Vụ

### Luồng Checkout

```
1. GET /shopping
   ├── Kiểm tra trạng thái đăng nhập (OrderHelper.isLoginRequired)
   │   └── Chưa đăng nhập + không phải guest → chuyển hướng /shopping/login
   ├── Kiểm tra giỏ hàng (OrderHelper.verifyCart)
   │   └── Không hợp lệ → chuyển hướng /cart
   ├── Khởi tạo đơn hàng (OrderHelper.initializeOrder)
   ├── Thực thi PurchaseFlow (validate + tính toán)
   │   ├── Lỗi → chuyển hướng /shopping/error
   │   └── Cảnh báo → đồng bộ giỏ hàng qua CartPurchaseFlow
   ├── Cập nhật thông tin KH nếu thành viên
   └── Render form (OrderType + extensions)

2. User chọn payment / delivery / point → trigger change
   └── POST /shopping/redirect_to
       ├── Lưu dữ liệu form vào Order
       ├── Thực thi PurchaseFlow (tính lại)
       └── Chuyển hướng tới route đích (hoặc quay lại /shopping)

3. User nhấn CTA → POST /shopping/confirm
   ├── Validate form
   ├── Thực thi PurchaseFlow
   ├── PaymentMethod::verify (kiểm tra tính hợp lệ thanh toán)
   │   └── Lỗi → chuyển hướng quay lại /shopping
   └── Render trang xác nhận (confirm.twig)

4. User nhập thông tin thẻ (nếu GMO Direct) + chọn subscription (nếu muốn)
   └── POST /shopping/checkout
       ├── Validate form
       ├── Thực thi PurchaseFlow
       ├── PaymentMethod::checkout (xử lý thanh toán)
       │   ├── PaymentDispatcher dispatch response
       │   └── Lỗi → chuyển hướng /shopping/error
       ├── PurchaseFlow::commit (hoàn tất đơn hàng)
       ├── CartService::clear
       ├── MailService::sendOrderMail
       ├── ✅ SubscriptionShoppingCompleteSubscriber
       │   └── Nếu subscription_enabled → SubscriptionActivator.activate(Order)
       └── Chuyển hướng /shopping/complete
```

### Logic thanh toán

- **GMO Direct Credit Card**: Card fields gửi kèm checkout form → `GmoApiClient` xử lý. Không lưu dữ liệu thẻ thô
- **REMISE Payment**: Chuyển hướng qua `/shopping/remise_payment` → thanh toán bên ngoài → callback
- **EccubePaymentLite42** (Epsilon): Luồng token payment qua plugin
- **Chuyển khoản / COD**: Không cần thanh toán online. Ghi nhận đơn hàng trực tiếp

### Logic Subscription (✅ Đã triển khai)

```
Khi checkout POST:
1. ShoppingOrderTypeExtension lắng nghe FormEvents::SUBMIT
2. Nếu subscription_enabled = true && cycle hợp lệ:
   → Lưu {enabled, cycle} vào Session[SUBSCRIPTION_BAG][preOrderId]
3. Nếu không:
   → Xoá khỏi Session bag

Sau khi đơn hàng hoàn thành:
4. SubscriptionShoppingCompleteSubscriber lắng nghe sự kiện order.complete
5. Nếu Session bag có dữ liệu cho đơn hàng:
   → SubscriptionActivator.activate(Order, cycle)
   → Tạo bản ghi Subscription + SubscriptionItem + SubscriptionOrder
```

### Quy tắc validation

- Validate CSRF token
- Giỏ hàng tồn tại + các mục hợp lệ
- Validate trạng thái sản phẩm / tồn kho / giá qua `PurchaseFlow`
- Phương thức thanh toán hợp lệ
- Validate lại tồn kho / giá trước khi Xác nhận và trước khi Hoàn thành
- Guest checkout: giữ `cart_key` / session ổn định

---

## 10. Phân Quyền

| Mục | Giá trị |
| --- | --- |
| Yêu cầu đăng nhập | Không (hỗ trợ guest checkout) |
| Guest checkout | Có (qua `/shopping/nonmember`) |
| Thành viên checkout | Có |
| Thẻ tín dụng (GMO Direct) | Cần session + giỏ hàng hợp lệ |
| Tuỳ chọn subscription | **Yêu cầu đăng nhập** (chỉ thành viên) |
| Sử dụng thẻ đã lưu | Yêu cầu đăng nhập nếu hỗ trợ |
| Sử dụng điểm | Yêu cầu đăng nhập (chỉ thành viên) |

---

## 11. Trường Hợp Biên

- Nhấn nhanh liên tục CTA → loading overlay + khoá gửi form
- Thẻ GMO thất bại → hiển thị lỗi, ở lại trang xác nhận
- Giỏ hàng trống → chuyển hướng `/cart`
- Chưa chọn thanh toán → lỗi validation form
- Session hết hạn (`pre_order_id` không hợp lệ) → chuyển hướng `/shopping/error`
- Tồn kho thay đổi trong checkout → cảnh báo `PurchaseFlow`, đồng bộ giỏ hàng
- Giá thay đổi trong checkout → tính lại, hiển thị tóm tắt cập nhật
- Phương thức thanh toán bị admin vô hiệu hoá trong khi user đang trên màn hình → lỗi khi submit
- REMISE redirect bị huỷ → user quay lại, đơn hàng chưa hoàn thành
- Nhấn nút Back trình duyệt sau thanh toán → kiểm tra đơn hàng đã hoàn thành
- CTA sticky mobile chồng lên form
- Guest inline edit thất bại → xử lý lỗi AJAX
- Subscription đã bật nhưng chưa chọn chu kỳ → dữ liệu subscription không lưu (bỏ qua)
- Subscription bật cho guest → không hiển thị (chỉ thành viên)

---

## 12. Test Cases

### Happy Path

- Checkout thẻ tín dụng (GMO Direct) → xác nhận → hoàn thành ✅
- Chọn thanh toán REMISE → xác nhận → chuyển hướng → callback → hoàn thành
- Thanh toán Epsilon (EccubePaymentLite42) → xác nhận → token → hoàn thành
- Chuyển khoản / COD → xác nhận → hoàn thành
- Guest checkout thành công
- Thành viên checkout thành công
- Thành viên checkout + subscription bật → subscription được tạo ✅
- Sử dụng điểm → tính lại tổng

### Lỗi

- Thẻ không hợp lệ (GMO Direct)
- Chưa chọn thanh toán
- CSRF không hợp lệ
- Tồn kho không khớp
- Session hết hạn
- Lỗi `PurchaseFlow`
- Guest AJAX cập nhật thất bại

### Trường hợp biên

- Nhấn đúp CTA
- Delay mạng
- Nhấn nút Back trình duyệt (từ xác nhận / từ hoàn thành)
- CTA sticky mobile
- Giá thay đổi sau giỏ hàng
- Thay đổi giao hàng → tính lại phí
- Thay đổi thanh toán → tính lại phí
- Bật / tắt checkbox subscription → cập nhật session bag

---

## 13. Ghi Chú Triển Khai EC-CUBE

### Triển khai hiện có (✅)

| Thành phần | Vị trí | Mô tả |
| --- | --- | --- |
| `ShoppingController` | `src/Eccube/Controller/ShoppingController.php` | Luồng shopping chính (index, redirect_to, confirm, checkout, complete) |
| `NonMemberShoppingController` | `src/Eccube/Controller/NonMemberShoppingController.php` | Thông tin KH guest (nonmember, customer AJAX) |
| `OrderType` | `src/Eccube/Form/Type/Shopping/OrderType.php` | Kiểu form shopping |
| `ShoppingOrderTypeExtension` | `app/Customize/Form/Extension/ShoppingOrderTypeExtension.php` | Thêm GMO card fields + subscription fields |
| `SubscriptionShoppingCompleteSubscriber` | `app/Customize/EventSubscriber/Subscription/` | Tạo subscription sau checkout hoàn thành |
| `SubscriptionActivator` | `app/Customize/Service/Subscription/SubscriptionActivator.php` | Tạo Subscription + items + bản ghi đơn hàng đầu tiên |
| `GmoApiClient` | `app/Customize/Service/Gmo/GmoApiClient.php` | GMO Direct payment API client |
| `PurchaseFlow` | `src/Eccube/Service/PurchaseFlow/` | Validate + tính toán đơn hàng (tồn kho, giá, thuế, giảm giá) |
| `OrderHelper` | `src/Eccube/Service/OrderHelper.php` | Khởi tạo đơn hàng, kiểm tra giỏ hàng, kiểm tra đăng nhập |
| `CartService` | `src/Eccube/Service/CartService.php` | Quản lý giỏ hàng |
| `PaymentDispatcher` | `src/Eccube/Service/Payment/PaymentDispatcher.php` | Dispatch xử lý thanh toán |
| `Shopping/index.twig` | `src/Eccube/Resource/template/default/Shopping/index.twig` | Trang checkout index |
| `Shopping/confirm.twig` | `app/template/default/Shopping/confirm.twig` | Trang xác nhận (tuỳ chỉnh: subscription + GMO Direct fields) |

### Nguyên tắc chính

- Ưu tiên dùng `ShoppingController` / `PurchaseFlow` / `OrderHelper` / `CartService` theo convention EC-CUBE
- Không sửa trực tiếp bảng core. Bảng tuỳ chỉnh phải tạo qua migration / plugin hoặc entity extension
- Logic thanh toán đi qua `PaymentMethodInterface` → `PaymentDispatcher`, không triển khai trực tiếp trong Twig
- Dữ liệu thẻ **không được lưu** vào DB hoặc log. Chỉ lưu token/reference nếu cần
- Validation tồn kho / giá / thanh toán phải chạy lại trước khi vào Xác nhận và trước khi Hoàn thành
- Guest checkout cần giữ `cart_key` / session ổn định
- Logic subscription tách riêng qua `ShoppingOrderTypeExtension` + `SubscriptionShoppingCompleteSubscriber`, không sửa core `ShoppingController`

---

*Tạo lúc: 2026-05-10 17:07:41*
*Cập nhật: 2026-05-11*
