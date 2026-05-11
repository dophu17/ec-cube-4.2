## 1. Tổng quan

**Mục đích**

- Màn **chỉnh sửa / đăng ký thành viên** EC-CUBE Admin (`会員登録`). Trên bản Customize của repo, khi khách đã có **ID** (bản ghi đã lưu), màn hiển thị thêm khối **Subscription（定期購読）** để quản trị viên **xem nhanh** mọi subscription gắn với khách và **điều hướng** sang danh sách subscription đã lọc hoặc màn chi tiết từng subscription.

**Vai trò trong hệ thống**

- Bổ sung ngữ cảnh **khách ↔ subscription** cạnh thông tin hội viên, địa chỉ giao, lịch sử đơn; **không** tạo subscription mới và **không** charge / hủy subscription trên chính card này (thực hiện ở `/admin/subscription/{id}` hoặc cron).

**Đối tượng**

- Quản trị viên đã đăng nhập EC-CUBE Admin.

## 2. Phạm vi

**Trong phạm vi (phần Subscription Customize)**

- Hiển thị card **Subscription（定期購読）** với bảng rút gọn (khi có dữ liệu) hoặc thông báo trống.
- Nút **Danh sách subscription của khách này** → `GET /admin/subscription?customer_id={Customer.id}`.
- Nút **Chi tiết** từng dòng → `GET /admin/subscription/{id}`.

**Trong phạm vi (core EC-CUBE — chỉ tham chiếu)**

- Form thông tin khách, địa chỉ, lịch sử đơn, memo, lưu **登録**, v.v. — xem template gốc `src/Eccube/Resource/template/admin/Customer/edit.twig` và controller `CustomerEditController`.

**Ngoài phạm vi**

- Tạo subscription (checkout / sau thanh toán kỳ đầu).
- Cấu hình GMO, `SUBSCRIPTION_ENABLED`, policy retry — xem `README_SUBSCRIPTION_GMO.md`.

## 3. Cấu trúc màn hình & chức năng

- **Header** + **sidebar:** khu **会員管理** → **会員登録** (`menus`: `customer`, `customer_edit`).
- **Vùng chính:** form khách (core) + các card (địa chỉ, …).
- **Card Subscription (Customize):** đặt **sau** card **お届け先住所**, **trước** card **注文履歴**; `id="ex-customer-subscriptions"` để neo test / tài liệu.
- **Thanh conversion** cuối trang: **会員一覧**, dropdown trạng thái hội viên, **登録** (core).

## 4. Tham số & đường dẫn

| Tham số | Kiểu | Bắt buộc | Mô tả |
| --- | --- | --- | --- |
| `id` (URL) | số | Có (chỉnh sửa) | Id `dtb_customer`. Ví dụ: `/admin/customer/50000/edit` → `id = 50000`. |
| `page_no`, `page_count` (query) | số | Không | Phân trang **lịch sử đơn** trên cùng URL (core), không ảnh hưởng bảng subscription. |

**Điều kiện truy cập**

| Mục | Giá trị |
| --- | --- |
| URL ví dụ (local) | `http://localhost:8080/index.php/admin/customer/50000/edit` |
| Quyền | Đăng nhập Admin, khu **会員管理** (chỉnh sửa khách). |

**Điều hướng menu**

- **会員管理** → **会員登録** (khi sửa: cùng menu với tạo mới; context là khách `id` cụ thể).

## 5. Nguồn dữ liệu

| Nguồn | Mô tả |
| --- | --- |
| `dtb_customer` | Thông tin form khách (core). |
| `dtb_subscription` | Các subscription có `customer_id` = khách hiện tại; query `Customize\Repository\SubscriptionRepository::findByCustomerIdForAdmin($customerId)` — `ORDER BY id DESC`. |
| Biến Twig `CustomerSubscriptions` | Do **Customize** inject qua `kernel.view` (không sửa core controller): `Customize\EventSubscriber\Admin\CustomerSubscriptionViewSubscriber` trên route `admin_customer_edit` / `admin_customer_new`. |

**Ghi chú**

- **会員新規追加** (`admin_customer_new`, chưa có `Customer.id`): card Subscription **không** render (`{% if Customer.id %}`).

## 6. Trạng thái màn hình

**6.1 Khách đã lưu, có subscription**

- Bảng ít nhất một dòng; badge trạng thái (vd. `active`).

**6.2 Khách đã lưu, chưa có subscription**

- Card vẫn hiện; nội dung: `admin.customer.subscription_empty` (văn bản dịch, vd. « Khách chưa có subscription nào. »).

**6.3 Khách mới (chưa lưu lần đầu)**

- Không có card Subscription.

**6.4 Sau khi bấm 登録 (core)**

- Redirect về cùng `admin_customer_edit` với `id`; dữ liệu subscription reload theo DB.

## 7. Bố cục giao diện

**7.1 Card Subscription (sơ đồ)**

```
Card #ex-customer-subscriptions
 ├─ Header
 │   ├─ Tiêu đề: admin.customer.subscription_heading (Subscription（定期購読）)
 │   └─ [Danh sách subscription của khách này] → admin_subscription_index?customer_id=
 └─ Body
     ├─ (có dữ liệu) table-responsive
     │   └─ Bảng: ID | Trạng thái | Chu kỳ | Next billing | Tổng tiền | [Chi tiết]
     └─ (không dữ liệu) đoạn text-muted (subscription_empty)
```

**7.2 Cột bảng (theo template)**

| Cột | Nguồn dữ liệu entity |
| --- | --- |
| ID | `Subscription.id` |
| Trạng thái | `Subscription.status` (badge) |
| Chu kỳ | `planType` × `intervalCount` |
| Next billing | `nextBillingAt` (filter `date_sec`) |
| Tổng tiền | `totalAmount` (filter `price`) |
| Hành động | Link **Chi tiết** → `admin_subscription_detail` |

## 8. Chi tiết thành phần giao diện (Subscription)

**8.0 Bảng tóm tắt**

| Thành phần | Khóa dịch / nhãn | Chức năng |
| --- | --- | --- |
| Tiêu đề card | `admin.customer.subscription_heading` | Nhận diện khối |
| Nút danh sách | `admin.customer.subscription_list_link` | Mở admin subscription list đã filter `customer_id` |
| Cột ID | `admin.customer.subscription_col_id` | Định danh subscription |
| Cột trạng thái | `admin.subscription.table.status` | Trạng thái nghiệp vụ |
| Chu kỳ | `admin.subscription.table.plan` | Plan + bội số interval |
| Next billing | `admin.subscription.table.next_billing` | Lần charge dự kiến tiếp |
| Tổng | `admin.subscription.table.total` | Snapshot tiền (¥) |
| Chi tiết | `admin.customer.subscription_open_detail` | Màn quản trị subscription đầy đủ |
| Trống | `admin.customer.subscription_empty` | Không có bản ghi |

**8.1 Không có chỉnh sửa inline**

- Card chỉ **đọc**; mọi thao tác charge / hủy thực hiện trên màn `/admin/subscription/{id}`.

## 9. Logic nghiệp vụ

- Danh sách subscription trên card **độc lập** phân trang đơn hàng của khách.
- Thứ tự subscription: mới nhất theo `id` DESC (theo repository).
- Trạng thái hiển thị dạng chuỗi raw (`active`, `past_due`, …) — đồng nhất với màn danh sách subscription admin.

## 10. Sự kiện & xử lý

| Sự kiện | Hệ thống |
| --- | --- |
| Click « Danh sách subscription của khách này » | GET `/admin/subscription?customer_id={id}` |
| Click « Chi tiết » | GET `/admin/subscription/{subId}` |
| Submit form khách + 登録 | POST core → redirect (không mô tả chi tiết tại đây) |

## 11. Điều hướng (luồng)

```
会員一覧 / 他画面
  → 会員編集 (customer/{id}/edit)
      ├─ Card Subscription: xem bảng
      ├─ → 一覧 subscription (?customer_id=)
      └─ → Chi tiết subscription / quay lại chỉnh sửa khách
```

Tham chiếu thêm: `screen_specs/ADMIN_SUBSCRIPTION_LIST_SCREEN_SPEC.md`.

## 12. Quyền & phân quyền

- Cùng quyền truy cập **chỉnh sửa khách** như màn customer edit chuẩn; không có route riêng cho block subscription.

## 13. Tích hợp bên ngoài

- **GMO / billing:** không gọi từ card này.
- **Đồng bộ dữ liệu:** subscription do luồng checkout + `subscription:run` / admin subscription detail cập nhật; F5 màn khách phản ánh DB.

## 14. Môi trường

| Môi trường | Ghi chú |
| --- | --- |
| Local | URL có thể có `index.php`; port tùy Docker. |
| Staging / Production | Path `/admin/customer/{id}/edit`; đổi domain và TLS. |

## 15. Trường hợp biên

- Khách tồn tại nhưng không có subscription → card + message trống.
- Nhiều subscription → nhiều dòng, cùng nút danh sách phía header.
- Template override: file nằm tại `app/template/admin/Customer/edit.twig` — khi nâng cấp EC-CUBE cần **merge** thay đổi từ core nếu core sửa cùng file.

## 16. Log & theo dõi

- Không log riêng cho block subscription; vận hành subscription xem log app / màn chi tiết subscription.

## 17. Quan điểm kiểm thử

**Luồng chính**

- Khách có subscription → card hiển thị đúng số dòng, đúng link `customer_id` và `detail` id.

**Trống**

- Khách không subscription → message `subscription_empty`.

**Biên**

- Khách mới (chưa id) → không thấy card.
- Sau khi tạo subscription (checkout) → mở lại màn khách → dòng mới xuất hiện.

**File triển khai**

- Twig: `app/template/admin/Customer/edit.twig` (khối `ex-customer-subscriptions`).
- Subscriber: `app/Customize/EventSubscriber/Admin/CustomerSubscriptionViewSubscriber.php`.
- Locale: `app/Customize/Resource/locale/messages.ja.yaml` / `messages.en.yaml` — khóa `admin.customer.subscription_*`.
