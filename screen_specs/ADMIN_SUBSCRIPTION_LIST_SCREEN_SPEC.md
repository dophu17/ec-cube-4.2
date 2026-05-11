## 1. Tổng quan

**Mục đích**

- Cho phép quản trị viên **xem danh sách subscription** (đăng ký định kỳ), **lọc** theo trạng thái / khoảng ngày billing / số lần retry / một khách cụ thể, và **mở chi tiết** từng subscription.

**Vai trò trong hệ thống**

- Vận hành và hỗ trợ: nhìn toàn bộ (hoặc theo khách) các bản ghi `dtb_subscription`; **không** phải chỗ tạo subscription mới (subscription sinh sau checkout thành công) và **không** thực hiện charge GMO trực tiếp trên màn danh sách (charge / hủy nằm ở **màn chi tiết** `/admin/subscription/{id}` hoặc cron `subscription:run`).

**Đối tượng**

- Quản trị viên đã đăng nhập EC-CUBE Admin.

## 2. Phạm vi

**Trong phạm vi**

- Hiển thị bảng subscription với phân trang.
- Gửi **form lọc GET** (status, next billing từ/đến, retry đúng giá trị, customer_id ẩn khi đang lọc theo khách).
- Điều hướng: **Chi tiết** subscription, **hồ sơ khách**, **xóa lọc** (về danh sách toàn shop nếu không còn query).

**Ngoài phạm vi**

- Tạo/sửa snapshot subscription, charge GMO, hủy subscription (xem màn chi tiết / command).
- Cấu hình `GMO_*`, `SUBSCRIPTION_*` — biến môi trường và Customize, không có form trên màn này.

## 3. Cấu trúc màn hình & chức năng

- Thanh **header** EC-CUBE (tên shop, tài khoản admin).
- **Sidebar:** khu **Đơn hàng** (order) → mục **Subscription（定期購読）** (`menus`: `order`, `subscription_list`).
- **Vùng nội dung chính:**
  - (Tùy điều kiện) cảnh báo **SUBSCRIPTION_ENABLED=0**.
  - (Tùy query) banner lọc theo **customer_id** + link mở chỉnh sửa khách.
  - Card **bộ lọc** (nhãn khối dùng nhãn « trạng thái » trong bản dịch UI).
  - Card **bảng** kết quả + **pager** Knp (khi nhiều hơn một trang).

## 4. Tham số & đường dẫn

| Tham số / route | Kiểu | Bắt buộc | Mô tả |
| --- | --- | --- | --- |
| Route chính | — | — | `admin_subscription_index`: `GET /admin/subscription` |
| Phân trang | — | Không | `admin_subscription_index_page`: `GET /admin/subscription/page/{page_no}` |
| Query `status` | chuỗi | Không | Một trong: rỗng (tất cả), hoặc `pending_activation`, `active`, `paused`, `past_due`, `cancelled`, `expired` |
| Query `next_from` | ngày | Không | Đầu ngày theo timezone cấu hình shop (`ECCUBE_TIMEZONE`) — chỉ ngày, client gửi `YYYY-MM-DD` |
| Query `next_to` | ngày | Không | Cuối ngày 23:59:59 (cùng timezone) |
| Query `retry_count` | số | Không | Khớp chính xác `dtb_subscription.retry_count` |
| Query `customer_id` | số | Không | Chỉ subscription của `dtb_customer.id` tương ứng; giữ qua `<input type="hidden">` khi submit lọc; pager kế thừa query hiện tại |

**Điều kiện truy cập**

| Mục | Giá trị |
| --- | --- |
| URL ví dụ (local) | `http://localhost:8080/index.php/admin/subscription` |
| Quyền | Đăng nhập **Admin** EC-CUBE (cùng chuẩn truy cập các màn order admin). |

**Điều hướng menu (sidebar)**

- **Đơn hàng** → **Subscription（定期購読）**

Tiêu đề trang (block): bản dịch `admin.subscription.title_list`; sub-title: `admin.subscription.sub_title` (Đơn hàng).

## 5. Nguồn dữ liệu

| Nguồn | Mô tả |
| --- | --- |
| `dtb_subscription` | Các trường hiển thị: id, status, plan_type, interval_count, next_billing_at, retry_count, max_retry, total_amount, liên kết `customer_id`. |
| `dtb_customer` | Join để hiển thị ID + họ tên và link sang `admin_customer_edit`. |

**Query**

- Repository: `Customize\Repository\SubscriptionRepository::getAdminListQueryBuilder(...)` — `ORDER BY s.id DESC`.

**Số bản ghi / trang**

- Tham số `eccube_default_page_count` (cấu hình EC-CUBE).

## 6. Trạng thái màn hình

**6.1 Ban đầu / sau lọc**

- Bảng hiển thị trang hiện tại; form lọc phản ánh query (hoặc mặc định).

**6.2 Không có bản ghi**

- Một dòng placeholder « — » spans cột.

**6.3 SUBSCRIPTION_ENABLED = false**

- Thanh báo vàng (`admin.subscription.disabled`): nhắc rằng tính năng renewal/charge có thể bị tắt; **màn danh sách vẫn xem được**.

**6.4 Đang lọc theo khách**

- Thanh báo xanh nhạt (`customer_context` + `open_customer`): deep-link từ `/admin/customer` hoặc card subscription trên màn khách.

## 7. Bố cục giao diện

**7.1 Desktop (sơ đồ cây)**

```
Trang Admin EC-CUBE
 ├─ Header
 ├─ Sidebar: Đơn hàng → Subscription（定期購読）
 └─ Vùng chính
     ├─ (optional) Cảnh báo disabled subscription
     ├─ (optional) Banner filter theo khách → link chỉnh sửa khách
     ├─ Card filter
     │   ├─ Trạng thái [select]
     │   ├─ Next billing từ [date]
     │   ├─ Next billing đến [date]
     │   ├─ Retry count [number]
     │   ├─ [Lọc] [Xóa lọc]
     │   └─ (hidden) customer_id khi có
     └─ Card bảng
         ├─ Table: ID | Khách | Status | Plan | Next billing | Retry | Tổng | [Chi tiết]
         └─ Pager (nếu >1 trang)
```

**7.2 Mobile / màn hẹp**

- Bảng cuộn ngang (`table-responsive`); form lọc xếp cột Bootstrap.

## 8. Chi tiết thành phần giao diện

**8.0 Bảng tóm tắt**

| Thành phần | Nhãn (theo khóa dịch) | Mô tả |
| --- | --- | --- |
| Cảnh báo tắt | `disabled` | Khi `%env(bool:SUBSCRIPTION_ENABLED)%` = false |
| Banner khách | `filter.customer_context` + `filter.open_customer` | Khi `filters.customer_id` có giá trị |
| Tiêu đề card filter | Reuse `filter.status` (header card) |
| Select trạng thái | `filter.status` + `filter.status_all` + từng status |
| Next từ/đến | `next_from`, `next_to` | Input `type="date"` |
| Retry | `retry_count` | Số nguyên ≥ 0 |
| Nút | `search`, `reset` | Submit GET vs link về `/admin/subscription` không query |
| Cột ID | `table.id` | `Subscription.id` |
| Khách | `table.customer` | Link → `admin_customer_edit` hoặc « — » |
| Trạng thái | `table.status` | Badge + raw `status` |
| Chu kỳ | `table.plan` | `planType × intervalCount` |
| Next billing | `table.next_billing` | Filter `date_sec` |
| Retry | `table.retry` | `retryCount / maxRetry` |
| Tổng | `table.total` | Filter `price` |
| Chi tiết | `table.action` | Link → `admin_subscription_detail` |

**8.1 Ghi chú lọc ngày**

- `parseDateBoundary` trong controller: chuỗi `YYYY-MM-DD` không hợp lệ → bỏ qua điều kiện đó.

**8.2 Pager**

- Include `@admin/pager.twig` với route `admin_subscription_index_page`; tham số query hiện tại được merge vào URL trang.

## 9. Logic nghiệp vụ

- Danh sách là **đọc** + lọc; không thay đổi subscription trên màn này.
- Lọc `status` rỗng → không ép điều kiện status.
- Lọc theo khách chỉ áp `customer_id > 0` hợp lệ trong DB — khách không tồn tại vẫn có thể trả **0 dòng**.
- **`reset`** trỏ tới danh sách gốc: **không** kèm `customer_id` (xóa cả ngữ cảnh khách).

## 10. Sự kiện & xử lý

| Sự kiện | Hệ thống |
| --- | --- |
| Submit form lọc | GET → render lại bảng + query string |
| Click Xóa lọc | GET `/admin/subscription` không query |
| Click Chi tiết | GET `/admin/subscription/{id}` |
| Click tên/link khách | GET `/admin/customer/{id}/edit` |
| Chọn trang pager | GET `/admin/subscription/page/N` + query giữ nguyên |

## 11. Điều hướng (luồng)

```
Menu Đơn hàng → Subscription
  ├─ (từ) Danh sách khách: cột Sub. → ?customer_id=X
  ├─ (từ) Chi tiết khách: card subscription → link đã filter
  ├─ Lọc / phân trang → cùng màn danh sách
  └─ Chi tiết → POST actions (retry / cancel…) ở màn khác
```

Tham chiếu tổng thể: `README_SUBSCRIPTION_GMO.md`.

## 12. Quyền & phân quyền

- Chỉ user đã vào được khu Admin (cùng cách tiếp cận màn `/admin/order`); chi tiết phân quyền Membership theo triển khai shop (Customize có thể thu hẹp route sau).

## 13. Tích hợp bên ngoài

- **GMO / billing:** không gọi API trên màn danh sách.
- Renewal thực tế: **`subscription:run`**, và trên UI — **màn chi tiết** hoặc tài liệu `README_SUBSCRIPTION_GMO.md`, `app/Customize/README_GMO_DIRECT.md`.

## 14. Môi trường

| Môi trường | Ghi chú |
| --- | --- |
| Local | URL có thể có `index.php`; port (vd. 8080) tùy Docker. |
| Staging / Production | Path cố định `/admin/subscription`; đổi domain và TLS. |

## 15. Trường hợp biên

- Không có subscription nào thỏa lọc → bảng trống dòng một hàng placeholder.
- `customer_id` trỏ khách không tồn tại → không lỗi HTTP đặc biệt, có thể 0 kết quả.
- Sai định dạng ngày trên manual URL → có thể bị bỏ qua trong code (không lọc theo boundary đó).
- **Reset** xóa mọi filter kể cả theo khách.

## 16. Log & theo dõi

- Truy vết vận hành chủ yếu ở màn chi tiết + cron + log ứng dụng `[subscription]` (charge).
- Màn danh sách chỉ đọc; có thể bật log HTTP admin theo policy shop.

## 17. Quan điểm kiểm thử

**Luồng chính**

- GET `/admin/subscription` → có/không có dữ liệu hiển thị đúng phân trang.
- Submit từng điều kiện lọc và kết hợp; `customer_id` kèm lọc vẫn giữ được qua paging.

**Lỗi / UX**

- Reset đưa về full list không query.
- Khi SUBSCRIPTION_DISABLED: vẫn thấy cảnh báo và có thể đọc danh sách.

**Biên**

- `page_no` không hợp lệ: hành vi KnpPaginator mặc định của EC-CUBE.
- Nhiều subscription cùng khách → mỗi dòng một bản ghi, link chi tiết đúng id.
