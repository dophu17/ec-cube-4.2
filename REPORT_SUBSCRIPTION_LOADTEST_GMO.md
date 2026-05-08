# Báo cáo load test subscription — so sánh GMO Mock vs GMO thật (sandbox)

Tài liệu này ghi lại kết quả đo trên môi trường local (Docker + PostgreSQL), dữ liệu seed `loadtest_member_%`.  
**Phase 1** đã điền (mock). **Phase 2** bạn bổ sung sau khi chạy `GMO_MOCK_MODE=0` để so sánh.

---

## Thông tin chung (áp dụng cả hai phase)

| Mục | Giá trị |
|-----|---------|
| Cron | `*/5 * * * *` (mỗi 5 phút) |
| Batch | `subscription:run --limit=5` |
| Dữ liệu test | `dtb_subscription.gmo_member_id LIKE 'loadtest_member_%'` |
| Thông lượng lý thuyết tối đa | ~60 gia hạn thành công/giờ (nếu mỗi lần cron luôn đủ 5 đến hạn và không lỗi) |
| Script seed / SQL thống kê | `README_SUBSCRIPTION_LOAD_TEST.md` (mục 7) |

---

## Phase 1 — `GMO_MOCK_MODE=1` (đã ghi nhận)

### 1.1 Môi trường

| Hạng mục | Chi tiết |
|----------|----------|
| GMO | Mock trong `GmoApiClient` (không gọi `mul-pay.jp`) |
| Ghi chú vận hành | Trước khi ổn định có giai đoạn lỗi parse `datetimetz` do timestamp có microsecond từ seed; đã chuẩn hóa `date_trunc('second', ...)` trong script seed và dữ liệu loadtest |

### 1.2 Thống kê `dtb_subscription_order` (chỉ loadtest)

| Chỉ số | Giá trị (snapshot đã truy vấn DB) |
|--------|-----------------------------------|
| `billing_status = success` | **53** |
| `billing_status = failed` | **12** |
| Thời điểm bản ghi renewal đầu → hoạt động cuối (theo `create_date` / `billing_executed_at`) | Khoảng **12:30 → 13:30** (múi giờ session PostgreSQL hiển thị **+09**) |
| Ghi chú đo “latency từng renewal” | Chênh `billing_executed_at - create_date` thường **~0 giây** vì cột lưu **đến giây**; **không** dùng để kết luận thời gian xử lý chi tiết. Muốn đo ms nên xem `site.log` hoặc thêm log/metric |

### 1.3 Hàng đợi subscription loadtest (snapshot)

| Chỉ số | Giá trị |
|--------|---------|
| Subscription loadtest còn `active` và `next_billing_at <= now()` | **990** (tại thời điểm truy vấn) |
| Subscription loadtest đã có ít nhất một dòng `dtb_subscription_order` | **65** distinct (`53 + 12` theo từng lần thử renewal) |

### 1.4 Log cron (`/tmp/subscription-cron.log`)

| Hiện tượng | Ghi nhận |
|------------|----------|
| Lỗi `ConversionException` / `datetimetz` (giai đoạn seed cũ) | **~55** lần (batch không chạy xong `subscription:run`) |
| Dòng `Tìm thấy 5 subscription đến hạn` | **13** lần (minh chứng có chạy đủ batch 5) |

### 1.5 Phân tích lỗi `failed` (mock)

| Nội dung | Số lượng |
|----------|----------|
| Thiếu tồn kho: `「チェリーアイスサンド」の在庫が足りません。` | **12** (toàn bộ `failed` loadtest tại thời điểm thống kê) |

**Kết luận:** Giai đoạn mock cho thấy bottleneck chính **không phải GMO** mà là **PurchaseFlow / tồn kho** trên `base_order_id` dùng chung cho seed. Trước khi so sánh GMO thật, nên **tăng stock** hoặc đổi template order sang SKU đủ hàng để KPI không bị “dính” lỗi kho.

### 1.6 Nhận xét ngắn (mock)

- Throughput quan sát được (~53 success trong ~1 cửa sổ 1 giờ) **phù hợp đơn đặt cron** (`limit=5` mỗi 5 phút), không chứng minh đỉnh 60/h vì xen lẫn lỗi và hàng đợi đến hạn không đều.
- Mock **không** phản ánh độ trễ mạng hay timeout của sandbox/production.

---

## Phase 2 — `GMO_MOCK_MODE=0` (sandbox) — **đã ghi nhận**

> Lưu ý vận hành: sau khi sửa `.env.local`, cần **recreate** container `ec-cube` (ví dụ `docker compose ... up -d ec-cube`) để `env_file` được nạp lại. `restart` có thể không đổi biến môi trường trong container đang chạy.

### 2.1 Môi trường

| Hạng mục | Giá trị |
|----------|---------|
| `GMO_API_BASE_URL` | `https://pt01.mul-pay.jp/payment` |
| `GMO_SHOP_ID` (che bớt) | `tshop0*****` |
| Cron log (`/tmp/subscription-cron.log`) cập nhật tới | `2026-05-08 13:10` (local) |
| Cửa sổ “GMO thật” (theo các dòng có `gmo_tran_id` không phải `MOCK-*`) | **13:39 → 15:15** (session PostgreSQL hiển thị **+09**) |

### 2.2 Thống kê `dtb_subscription_order` (loadtest)

| Chỉ số | Giá trị |
|--------|---------|
| `success` (tổng) | **160** |
| `failed` (tổng) | **12** |
| `pending` / khác | **0** (theo snapshot nhóm status) |
| `success` có `gmo_tran_id` dạng `MOCK-*` | **59** *(đã chạy mock trước khi chuyển sandbox)* |
| `success` có `gmo_tran_id` “real” (không phải `MOCK-*`) | **101** *(GMO sandbox thật)* |
| Khung giờ tổng `first_created` → `last_activity` | **12:30 → 15:15** (+09) |

### 2.3 Top lỗi `failed` (theo `error_message`)

| `error_message` (rút gọn) | `count` |
|---------------------------|---------|
| `「チェリーアイスサンド」の在庫が足りません。` | **12** |

### 2.4 So sánh nhanh Mock vs Sandbox

| KPI | Phase 1 mock | Phase 2 sandbox |
|-----|---------------|----------------|
| Success | 53 *(snapshot)* | **101** *(real TranID; tổng `success`=160 gồm 59 dòng mock lịch sử)* |
| Failed | 12 *(snapshot)* | **12** |
| Nguyên nhân fail chủ đạo | Thiếu hàng (`チェリーアイスサンド`) | Thiếu hàng (`チェリーアイスサンド`) |
| Timeout / GMO | Không có (mock) | Chưa thấy timeout trong snapshot hiện tại *(để tiếp tục theo dõi khi chạy lâu hơn / tăng throughput)* |

### 2.5 Ghi chú bổ sung

- Ramp-up sandbox: ví dụ `limit=1` → `limit=5` để giảm rủi ro timeout hàng loạt.
- Nếu cần “sạch KPI”, chạy lại seed hoặc reset data loadtest sau khi đã xử lý tồn kho.

---

## Lịch sử chỉnh sửa báo cáo

| Ngày | Nội dung |
|------|----------|
| 2026-05-08 | Khởi tạo: ghi nhận Phase 1 `GMO_MOCK_MODE=1` |
| 2026-05-08 | Cập nhật Phase 2 `GMO_MOCK_MODE=0` (sandbox) + so sánh mock vs sandbox |
