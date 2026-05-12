# MyPage — Quản Lý Phương Thức Thanh Toán (Payment Methods Screen Spec)

Tác giả: An Ngô
Ngày tạo: 8 tháng 5, 2026 15:45
Cập nhật: 11 tháng 5, 2026 16:36

---

## 1. Tổng Quan

### Mục đích

Màn hình My Page — Payment Methods（決済方法）cho phép người dùng:

- Quản lý phương thức thanh toán
- Xem danh sách thẻ tín dụng đã đăng ký
- Thêm / chỉnh sửa / xoá thẻ
- Liên kết các phương thức thanh toán khác
- Chọn phương thức dùng cho subscription billing

### Vai trò trong hệ thống

```
My Page
 ↓
Quản lý Payment
 ↓
Subscription / Checkout Billing
```

### User

| Vai trò | Truy cập |
| --- | --- |
| Khách hàng (Customer) | Có |
| Guest | Không |
| Admin | Không |

> **Lưu ý quan trọng:**
> - Dự án sử dụng **Custom GMO** (`app/Customize/Service/Gmo/GmoApiClient.php`), **KHÔNG** dùng plugin EccubePaymentLite42 cho payment management.
> - Tính năng lưu thẻ cho khách hàng (SaveMember / SaveCard / SearchCard / DeleteCard) **sẽ được triển khai trong tương lai**.
> - `GmoApiClient` hiện tại chỉ hỗ trợ: `payWithTestCard()`, `payWithToken()`, `payWithCard()`. Cần mở rộng thêm các method quản lý thẻ.
> - Subscription entity đã lưu `gmo_member_id` và `gmo_card_seq` trực tiếp trên `dtb_subscription`.

---

## 2. Phạm Vi

### ✅ Trong phạm vi

- Hiển thị danh sách phương thức thanh toán
- Hiển thị thẻ tín dụng đã đăng ký
- Thêm thẻ mới
- Chỉnh sửa metadata thẻ nếu provider hỗ trợ
- Xoá thẻ
- Nút tích hợp PayPay
- Hiển thị tuỳ chọn chuyển khoản ngân hàng
- Responsive desktop / mobile
- Phần thông tin bảo mật
- Kiểm tra quyền sở hữu
- Chặn xoá thẻ nếu đang dùng cho active subscription

### ❌ Ngoài phạm vi

- Thanh toán checkout trực tiếp
- Hoàn tiền
- Lịch sử thanh toán
- Xử lý chuyển khoản thực tế
- Lưu dữ liệu thẻ thô
- Quản lý chi tiết subscription
- Đăng ký coupon

---

## 3. Cấu Trúc Màn Hình

| Thành phần | Mô tả |
| --- | --- |
| Header | Header toàn site |
| My Page Sidebar | Menu điều hướng |
| Danh sách thẻ tín dụng | Danh sách thẻ đã đăng ký |
| Card Item | Mục hiển thị thông tin thẻ |
| Nút thêm thẻ | 新しいカードを登録する (Đăng ký thẻ mới) |
| Phương thức khác | PayPay / 銀行振込 (Chuyển khoản ngân hàng) |
| Thông báo bảo mật | Thông tin bảo mật |
| Footer | Footer toàn site |

---

## 4. Tham Số

| Tên | Kiểu | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- | --- |
| `customer_id` | bigint | có | - | Khách hàng đã đăng nhập |
| `card_id` | bigint/string | không | - | ID thẻ local hoặc provider card seq |
| `payment_method` | string | không | `credit_card` | `credit_card` / `paypay` / `bank_transfer` |
| `return_url` | string | không | `/mypage/payment` | URL redirect |
| `csrf_token` | string | có | - | CSRF token cho thêm / sửa / xoá / kết nối |
| `gmo_member_id` | string | không | - | GMO member id (dùng Custom GMO, lưu trên `dtb_subscription`) |
| `card_seq` | string | không | - | GMO saved card sequence (lưu trên `dtb_subscription.gmo_card_seq`) |
| `paypay_account_id` | string | không | - | Tham chiếu tài khoản PayPay đã liên kết |
| `is_default` | boolean | không | false | Cờ phương thức thanh toán mặc định |
| `subscription_id` | int | không | null | Dùng khi chọn thanh toán cho subscription |

---

## 5. Nguồn Dữ Liệu

### 5.1 Bảng chuẩn EC-CUBE

| Bảng | Mô tả | Sử dụng |
| --- | --- | --- |
| `dtb_customer` | Master khách hàng | Đăng nhập / kiểm tra quyền sở hữu |
| `dtb_order` | Master đơn hàng | Tuỳ chọn: kiểm tra lịch sử sử dụng thanh toán |
| `dtb_order_item` | Mục đơn hàng | Tuỳ chọn: quan hệ subscription/đơn hàng nếu cần |
| `dtb_payment` | Master phương thức thanh toán | Nhãn / cài đặt phương thức thanh toán khả dụng |
| `dtb_customer_address` | Địa chỉ khách hàng | Không trực tiếp cần, nhưng nhất quán với dữ liệu My Page |
| `mtb_customer_status` | Trạng thái khách hàng | Kiểm tra khách hàng active |
| `mtb_order_status` | Trạng thái đơn hàng | Tuỳ chọn: quan hệ đơn hàng / thanh toán active |

> **Lưu ý:**
> - EC-CUBE core không nên lưu dữ liệu thẻ thô.
> - Thông tin thẻ đã lưu do **Custom GMO** (`GmoApiClient`) quản lý bằng member id / card seq.
> - Không sửa trực tiếp bảng core.

### 5.2 Bảng hiện có liên quan

| Bảng / Component | Ghi chú |
| --- | --- |
| `dtb_subscription` | ✅ Đã có `gmo_member_id`, `gmo_card_seq` — dùng trực tiếp để liên kết thanh toán subscription |
| `GmoApiClient` | ✅ Đã có (`app/Customize/Service/Gmo/GmoApiClient.php`). **Cần mở rộng** thêm: `saveMember()`, `saveCard()`, `searchCard()`, `deleteCard()` |
| Plugin EccubePaymentLite42 | Có `EditCreditCardController` tại `/mypage/eccube_payment_lite/credit_card` — **KHÔNG sử dụng**, dùng Custom GMO |
| Plugin RemisePayment42 | Có routes cập nhật thẻ autocharge — **KHÔNG sử dụng** cho payment management |

### 5.3 Bảng Tuỳ Chỉnh — Đề xuất nếu chưa có

#### `dtb_customer_payment_method`

Dùng khi dự án cần quản lý unified payment methods trong My Page.

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int | có | PK |
| `customer_id` | int | có | FK → `dtb_customer.id` |
| `payment_method` | varchar | có | `credit_card` / `paypay` / `bank_transfer` |
| `provider` | varchar | không | `gmo` / `paypay` / `manual` |
| `provider_customer_id` | varchar | không | GMO member id / PayPay account id |
| `provider_payment_id` | varchar | không | GMO card_seq / PayPay reference |
| `masked_number` | varchar | không | `**** **** **** 4242` |
| `brand` | varchar | không | VISA / MasterCard / JCB |
| `holder_name` | varchar | không | Tên chủ thẻ nếu cho phép |
| `expiry_month` | varchar | không | MM |
| `expiry_year` | varchar | không | YYYY |
| `is_default` | boolean | có | Phương thức mặc định |
| `status` | varchar | có | `active` / `expired` / `deleted` / `failed` |
| `create_date` | datetime | có | Convention EC-CUBE |
| `update_date` | datetime | có | Convention EC-CUBE |

> **Quan trọng:**
> - Không lưu `card_number`, `security_code`, raw token.
> - Chỉ lưu masked info và provider reference.
> - `Subscription.gmo_card_seq` đã lưu trực tiếp trên entity. Bảng này bổ sung khi cần quản lý nhiều thẻ cho khách hàng.

#### `dtb_customer_payment_link`

Dùng nếu PayPay hoặc external wallet cần quản lý account linkage riêng.

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int | có | PK |
| `customer_id` | int | có | FK → `dtb_customer.id` |
| `provider` | varchar | có | `paypay` / khác |
| `provider_account_id` | varchar | có | Tham chiếu tài khoản bên ngoài |
| `display_name` | varchar | không | Nhãn hiển thị |
| `status` | varchar | có | `connected` / `disconnected` / `expired` |
| `linked_at` | datetime | không | Thời điểm liên kết |
| `unlinked_at` | datetime | không | Thời điểm huỷ liên kết |
| `create_date` | datetime | có | Convention EC-CUBE |
| `update_date` | datetime | có | Convention EC-CUBE |

#### `dtb_customer_payment_audit_log`

Tuỳ chọn. Dùng nếu cần audit thao tác payment method trong DB.

| Cột | Kiểu | Bắt buộc | Ghi chú |
| --- | --- | --- | --- |
| `id` | int | có | PK |
| `customer_id` | int | có | FK → `dtb_customer.id` |
| `payment_method_id` | int | không | FK → `dtb_customer_payment_method.id` |
| `event` | varchar | có | `add_card` / `delete_card` / `connect_paypay` / `fail` |
| `provider` | varchar | không | `gmo` / `paypay` |
| `metadata` | json/text | không | Metadata đã sanitize |
| `ip_address` | varchar | không | IP client |
| `user_agent` | text | không | Thông tin trình duyệt |
| `create_date` | datetime | có | Convention EC-CUBE |

### 5.4 API / Routes

#### Routes Frontend (CẦN TRIỂN KHAI)

| Route | Method | Auth | Mô tả |
| --- | --- | --- | --- |
| `/mypage/payment` | GET | Có | Màn hình phương thức thanh toán |
| `/mypage/payment/cards` | GET | Có | Lấy danh sách thẻ đã đăng ký |
| `/mypage/payment/cards/add` | POST | Có | Thêm thẻ mới qua GMO token/reference |
| `/mypage/payment/cards/{card_id}/edit` | POST | Có | Chỉnh sửa metadata thẻ / cờ mặc định |
| `/mypage/payment/cards/{card_id}/delete` | POST | Có | Xoá thẻ đã lưu |
| `/mypage/payment/default` | POST | Có | Đặt phương thức thanh toán mặc định |
| `/mypage/payment/paypay/connect` | POST | Có | Bắt đầu kết nối PayPay |
| `/mypage/payment/paypay/callback` | GET/POST | Có | Callback kết nối PayPay |
| `/mypage/payment/paypay/disconnect` | POST | Có | Ngắt kết nối PayPay |
| `/mypage/payment/bank-transfer` | GET | Có | Thông tin chuyển khoản ngân hàng |
| `/login?redirect_url=/mypage/payment` | GET | Không | Chuyển hướng khi session hết hạn |

> **Lưu ý:**
> - **KHÔNG** dùng routes plugin EccubePaymentLite42 (`/mypage/eccube_payment_lite/credit_card`).
> - **KHÔNG** dùng routes plugin RemisePayment42.
> - Controller đặt tại `app/Customize/Controller/Mypage/MypagePaymentController.php`.
> - Tất cả write action cần CSRF + kiểm tra quyền sở hữu.

### 5.5 Dữ liệu mẫu

```json
{
  "cards": [
    {
      "id": 1,
      "provider": "gmo",
      "card_seq": "0",
      "brand": "VISA",
      "masked_number": "**** **** **** 4242",
      "holder_name": "山田 太郎",
      "expiry": "12/28",
      "is_default": true,
      "is_used_by_active_subscription": false
    }
  ],
  "paypay": {
    "connected": false
  },
  "bank_transfer": {
    "available": true
  }
}
```

---

## 6. Trạng Thái Màn Hình

### 6.1 Trạng thái ban đầu

**Điều kiện:**
- User đã đăng nhập
- API thành công

**UI:**
- Menu sidebar
- Danh sách thẻ đã đăng ký
- Nút thêm thẻ
- Phương thức thanh toán khác

### 6.2 Trạng thái đang tải

- Skeleton loading thẻ
- Vô hiệu hoá nút hành động

### 6.3 Trạng thái trống

**Điều kiện:** Không có thẻ đã đăng ký

**UI:**
> 登録済みのクレジットカードはありません。(Không có thẻ tín dụng đã đăng ký.)

**Nút:**
> ＋ 新しいカードを登録する (Đăng ký thẻ mới)

### 6.4 Trạng thái lỗi

| Trường hợp | UI | Hành động |
| --- | --- | --- |
| Lỗi API | 決済情報の取得に失敗しました。(Không thể lấy thông tin thanh toán.) | Thử lại |
| Chưa xác thực | Chuyển hướng đăng nhập | `/login?redirect_url=/mypage/payment` |
| Lỗi GMO token | カード登録に失敗しました。(Đăng ký thẻ thất bại.) | Thử lại |

---

## 7. Bố Cục UI

### 7.1 Hình ảnh bố cục

![Payment Methods.png](%F0%9F%93%98%20%E3%83%9E%E3%82%A4%E3%83%9A%E3%83%BC%E3%82%B8%20%E2%80%93%20%E6%94%AF%E6%89%95%E3%81%84%E6%96%B9%E6%B3%95%E7%94%BB%E9%9D%A2%E4%BB%95%E6%A7%98%20My%20Page%20%E2%80%93%20Payment%20Methods%20Scre/Payment_Methods.png)

Tham chiếu:
- Bố cục Desktop
- Bố cục responsive Mobile

### 7.2 Bố cục ban đầu

| STT | Thành phần | Mô tả | Hành vi |
| --- | --- | --- | --- |
| 1 | Header | Header toàn site | Điều hướng |
| 2 | Sidebar Menu | Menu My Page | Điều hướng |
| 3 | Tiêu đề thẻ tín dụng | 登録済みのクレジットカード (Thẻ tín dụng đã đăng ký) | Tĩnh |
| 4 | Nút thêm thẻ nhỏ | カードを追加する (Thêm thẻ) | Mở form |
| 5 | Card Item | Thông tin thẻ VISA | Hiển thị thẻ |
| 6 | Nút chỉnh sửa | 編集 (Chỉnh sửa) | Mở modal chỉnh sửa |
| 7 | Nút xoá | 削除 (Xoá) | Xoá thẻ |
| 8 | Nút thêm thẻ lớn | 新しいカードを登録する (Đăng ký thẻ mới) | Thêm thẻ |
| 9 | Phương thức khác | PayPay / 銀行振込 (Chuyển khoản) | Tích hợp bên ngoài |
| 10 | Thông báo bảo mật | Thông tin bảo mật | Chỉ hiển thị |
| 11 | Footer | Liên kết footer | Điều hướng |

### 7.3 Cấu trúc bố cục

```
Màn hình Phương thức thanh toán
├── Header
├── Container chính
│   ├── Sidebar Menu
│   └── Nội dung Payment
│       ├── Phần thẻ tín dụng
│       │   ├── Header phần
│       │   ├── Danh sách thẻ
│       │   │   └── Card Item
│       │   └── Nút thêm thẻ
│       ├── Phần phương thức khác
│       │   ├── Hàng PayPay
│       │   └── Hàng chuyển khoản ngân hàng
│       └── Thông báo bảo mật
└── Footer
```

### 7.4 Responsive / Safe Area

| Mục | Giá trị |
| --- | --- |
| Safe Area | Có |
| Scroll | Có |
| Responsive | Desktop / Mobile |
| Sidebar Mobile | Menu accordion |
| Footer Mobile | Danh sách thu gọn |

---

## 8. Chi Tiết Thành Phần UI

### Thành phần: Sidebar Menu

| Phần tử | Mô tả | Kiểu |
| --- | --- | --- |
| 会員情報 (Thông tin thành viên) | Thông tin thành viên | menu |
| お届け先情報 (Thông tin giao hàng) | Địa chỉ | menu |
| 注文履歴 (Lịch sử đơn hàng) | Lịch sử đơn hàng | menu |
| 定期購入管理 (Quản lý Subscription) | Subscription | menu |
| クーポン一覧 (Danh sách coupon) | Coupon | menu |
| ポイント履歴 (Lịch sử điểm) | Điểm | menu |
| **決済方法 (Phương thức thanh toán)** | **Phương thức thanh toán** | **active menu** |
| ログアウト (Đăng xuất) | Đăng xuất | menu |

### Thành phần: Card Item

| Phần tử | Mô tả | Kiểu |
| --- | --- | --- |
| Brand Logo | Logo VISA | image |
| Masked Number | `**** **** **** 4242` | text |
| Expiry | `12/28` | text |
| Holder Name | 山田 太郎 | text |
| Nút chỉnh sửa | 編集 (Chỉnh sửa) | secondary |
| Nút xoá | 削除 (Xoá) | danger |

### Thành phần: Form Thêm Thẻ

| Trường | Mô tả | Kiểu |
| --- | --- | --- |
| `card_number` | Số thẻ | text / tokenized |
| `expiry` | Hạn thẻ | text |
| `holder_name` | Tên chủ thẻ | text |
| `security_code` | Mã bảo mật | password / tokenized |
| submit | 登録する (Đăng ký) | primary |

> **Lưu ý:**
> - Form input phải được tokenize bởi GMO / provider.
> - Dữ liệu thẻ thô **không được lưu** hoặc ghi log.
> - Sử dụng `GmoApiClient` mở rộng (cần thêm `saveMember()`, `saveCard()`).

### Thành phần: Phương Thức Khác

| Phần tử | Mô tả | Kiểu |
| --- | --- | --- |
| PayPay | Kết nối PayPay | row |
| Nút kết nối | 連携する (Kết nối) | button |
| Chuyển khoản ngân hàng | 銀行振込 | row |
| Nút sử dụng | 利用する (Sử dụng) | button |

---

## 9. Logic Nghiệp Vụ

### Thẻ tín dụng

```
nếu không có thẻ:
    hiển thị trạng thái trống
ngược lại:
    hiển thị danh sách thẻ
```

### Thêm thẻ

```
User nhập thông tin
 ↓
GMO Tokenization (qua GmoApiClient mở rộng)
 ↓
Nhận token
 ↓
Gọi SaveMember + SaveCard trên GMO PG
 ↓
Lưu masked info + card_seq vào dtb_customer_payment_method
```

> ⚠️ Cần mở rộng `GmoApiClient`: thêm `saveMember()`, `saveCard()`.

### Chỉnh sửa thẻ

Cho phép cập nhật:
- Tên chủ thẻ
- Hạn thẻ (tuỳ GMO hỗ trợ)

Không cho chỉnh sửa:
- Số thẻ thô

### Xoá thẻ

```
nếu active subscription đang dùng thẻ (kiểm tra Subscription.gmo_card_seq):
    chặn xoá → hiển thị cảnh báo
ngược lại:
    cho phép xoá → gọi DeleteCard trên GMO PG
```

> ⚠️ Cần mở rộng `GmoApiClient`: thêm `deleteCard()`.
> Kiểm tra thẻ đang dùng qua: `SubscriptionRepository::findBy(['gmo_card_seq' => $cardSeq, 'status' => 'active'])`.

### PayPay

```
Nhấn kết nối
 ↓
OAuth / luồng bên ngoài
 ↓
Liên kết tài khoản PayPay
```

---

## 10. Phân Quyền

| Mục | Giá trị |
| --- | --- |
| Yêu cầu đăng nhập | Có |
| Truy cập Guest | Không |
| Kiểm tra quyền sở hữu | Có |
| CSRF | Có cho tất cả write actions |

---

## 11. Trường Hợp Biên

| Trường hợp | Xử lý |
| --- | --- |
| Không có thẻ | Trạng thái trống |
| Network timeout | Thử lại |
| Nhấn đúp | Vô hiệu hoá nút |
| Thẻ hết hạn | Cảnh báo |
| Xoá thẻ đang active cho subscription | Chặn xoá |
| Mobile màn hình nhỏ | Bố cục dọc |
| PayPay auth thất bại | Thông báo lỗi |

---

## 12. Test Cases

### Happy Path

| STT | Test | Kết quả mong đợi |
| --- | --- | --- |
| 1 | Mở màn hình | Hiển thị đúng |
| 2 | Thêm thẻ | Thẻ được lưu |
| 3 | Chỉnh sửa thẻ | Thông tin cập nhật |
| 4 | Xoá thẻ | Thẻ bị xoá |
| 5 | Kết nối PayPay | Tài khoản đã liên kết |

### Lỗi

| STT | Test | Kết quả mong đợi |
| --- | --- | --- |
| 1 | API thất bại | Hiển thị lỗi |
| 2 | GMO token thất bại | Thử lại |
| 3 | Chưa xác thực | Chuyển hướng đăng nhập |

### Trường hợp biên

| STT | Test | Kết quả mong đợi |
| --- | --- | --- |
| 1 | Mobile responsive | Bố cục đúng |
| 2 | Gửi đúp | Một request duy nhất |
| 3 | Xoá thẻ active subscription | Chặn xoá |
| 4 | Mạng chậm | Trạng thái loading |

---

## 13. Ghi Chú Triển Khai EC-CUBE

### Backend hiện có

| Thành phần | Vị trí | Mô tả |
| --- | --- | --- |
| `GmoApiClient` | `app/Customize/Service/Gmo/GmoApiClient.php` | ✅ Client GMO hiện có. **Cần mở rộng** thêm SaveMember / SaveCard / SearchCard / DeleteCard |
| `Subscription.gmo_member_id` | `app/Customize/Entity/Subscription.php` | ✅ Đã lưu GMO member ID trên subscription |
| `Subscription.gmo_card_seq` | `app/Customize/Entity/Subscription.php` | ✅ Đã lưu GMO card sequence trên subscription |
| `SubscriptionRepository` | `app/Customize/Repository/SubscriptionRepository.php` | ✅ Dùng kiểm tra thẻ đang active |

### Backend cần triển khai

#### `GmoApiClient` — Mở rộng (CẦN THÊM)

| Method | Mô tả | GMO PG API |
| --- | --- | --- |
| `saveMember($customerId)` | Đăng ký member trên GMO PG | `SaveMember.idPass` |
| `saveCard($memberId, $cardNo, $expire, $securityCode)` | Lưu thẻ qua GMO PG | `SaveCard.idPass` |
| `searchCard($memberId)` | Tìm thẻ đã lưu | `SearchCard.idPass` |
| `deleteCard($memberId, $cardSeq)` | Xoá thẻ | `DeleteCard.idPass` |
| `tradeSavedCard($memberId, $cardSeq, $orderId, $amount)` | Thanh toán bằng thẻ đã lưu | `ExecTran` with `MemberID` + `CardSeq` |

#### Controller (CẦN TẠO)

**File:** `app/Customize/Controller/Mypage/MypagePaymentController.php`

| Phương thức | Mô tả |
| --- | --- |
| `index()` | Màn hình danh sách phương thức thanh toán |
| `cards()` | Lấy danh sách thẻ (qua `GmoApiClient::searchCard`) |
| `addCard()` | Thêm thẻ mới |
| `editCard()` | Chỉnh sửa metadata thẻ |
| `deleteCard()` | Xoá thẻ (kiểm tra subscription active trước) |
| `setDefault()` | Đặt phương thức mặc định |
| `connectPayPay()` | Bắt đầu kết nối PayPay |
| `payPayCallback()` | Callback kết nối PayPay |

### Ghi chú triển khai

> **Kiến trúc:**
> - **KHÔNG** dùng plugin EccubePaymentLite42 cho payment management. Dùng Custom GMO (`GmoApiClient`).
> - **KHÔNG** dùng plugin RemisePayment42 cho card management.
> - Không sửa trực tiếp bảng core.
> - Bảng tuỳ chỉnh phải tạo bằng migration / entity extension.

> **Bảo mật:**
> - Không lưu dữ liệu thẻ thô, security code, hoặc raw token trong DB / log.
> - Route nên đồng nhất: `/mypage/payment`.
> - Xoá thẻ phải kiểm tra `Subscription.gmo_card_seq` trước khi xoá.
> - Tất cả write action cần CSRF + kiểm tra quyền sở hữu.

> **Template:**
> - Kế thừa `default_frame.twig`, include `Mypage/navi.twig`.
> - Đặt `{% set mypageno = 'payment' %}`.
> - Navi.twig cần thêm link 決済方法 (Phương thức thanh toán).

> **Card ID mapping:**
> - `card_id` ở UI nên map tới `gmo_card_seq` (provider card sequence) hoặc local `dtb_customer_payment_method.id`.

---

*Tạo lúc: 2026-05-08 15:45*
*Cập nhật: 2026-05-11*
