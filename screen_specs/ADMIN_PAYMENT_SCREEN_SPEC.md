## 1. Tổng quan

**Mục đích**

- Cho phép quản trị viên cấu hình **tên hiển thị**, **phí**, **điều kiện áp dụng theo giá trị đơn**, và **logo** của một phương thức thanh toán đã đăng ký trong cửa hàng.

**Vai trò trong hệ thống**

- Quản lý **cách hiển thị** và **điều kiện chọn** phương thức trên storefront; **không** thay thế cấu hình kết nối cổng thanh toán thật (GMO API, thông tin shop).

**Đối tượng**

- Quản trị viên đã đăng nhập EC-CUBE Admin.

## 2. Phạm vi

**Trong phạm vi**

- Mở màn hình chỉnh sửa một payment đã tồn tại (theo `id` trong URL).
- Sửa tên phương thức, phí, khoảng giá trị đơn được phép chọn phương thức, upload logo.
- Lưu trạng thái hiển thị (dropdown **表示**) và quay lại danh sách.

**Ngoài phạm vi**

- Cấu hình **Shop ID / Shop Pass / endpoint GMO** — thuộc biến môi trường và code Customize (`GmoApiClient`), không có trên form này.
- Luồng thanh toán thực tế (EntryTran / ExecTran) và xử lý thẻ trên front.

## 3. Cấu trúc màn hình & chức năng

- Thanh **header** EC-CUBE (tên shop, tài khoản admin).
- **Menu trái** khu vực cài đặt cửa hàng.
- **Vùng nội dung chính:** form **支払方法** (một phương thức thanh toán).
- **Thanh hành động cuối trang:** quay lại danh sách, chọn trạng thái hiển thị, lưu.

## 4. Tham số & đường dẫn

| Tham số | Kiểu | Bắt buộc | Mô tả |
| --- | --- | --- | --- |
| `id` (URL) | số | Có | Id bản ghi `dtb_payment`. Ví dụ path: `/admin/setting/shop/payment/5/edit` → `id = 5`. |

**Điều kiện truy cập**

| Mục | Giá trị |
| --- | --- |
| URL ví dụ (local) | `http://localhost:8080/index.php/admin/setting/shop/payment/5/edit` |
| Quyền | Đăng nhập **Admin** EC-CUBE (đủ quyền khu 店舗設定). |

**Điều hướng menu (sidebar)**

- **設定** → **店舗設定** → **支払方法設定**
- Tiêu đề trang: **支払方法設定** **店舗設定**

## 5. Nguồn dữ liệu

| Nguồn | Mô tả |
| --- | --- |
| `dtb_payment` | Tên, phí, điều kiện, trạng thái, liên kết logo, v.v. |
| BaseInfo (shop) | Ngữ cảnh chung cửa hàng (không thay cấu hình GMO API). |

Ghi chú: Dữ liệu form phụ thuộc `id` trong URL; ví dụ cấu hình **GMO Direct** xem mục 8.

## 6. Trạng thái màn hình

**6.1 Ban đầu**

- Form đã điền theo dữ liệu payment hiện tại.
- Khu vực logo có thể trống nếu chưa upload.

**6.2 Lưu thành công**

- Sau nút **登録**, thường có thông báo thành công theo chuẩn EC-CUBE.

**6.3 Lỗi validation**

- Trường **必須** thiếu hoặc không hợp lệ: hiển thị lỗi; người dùng sửa rồi lưu lại.

**6.4 Trạng thái không áp dụng**

- Màn **edit** luôn có một bản ghi; không dùng “empty list” như màn danh sách.

## 7. Bố cục giao diện

**7.1 Desktop (sơ đồ cây)**

```
Trang Admin EC-CUBE
 ├─ Header (logo shop, menu user)
 ├─ Sidebar trái
 │   └─ 設定 → 店舗設定 → 支払方法設定
 └─ Vùng chính
     ├─ Tiêu đề: 支払方法設定 店舗設定
     ├─ Khối form: 支払方法
     │   ├─ 支払方法名（必須）
     │   ├─ 手数料（必須）
     │   ├─ 利用条件（下限〜上限）
     │   └─ ロゴ画像（推奨 500×100）
     └─ Thanh dưới: [支払方法一覧] ... [表示 ▼] [登録]
```

**7.2 Mobile / màn hẹp**

- Cùng nội dung; sidebar có thể thu gọn; form xếp dọc.

## 8. Chi tiết thành phần giao diện

**8.0 Bảng tóm tắt (ví dụ GMO Direct)**

| Thành phần | Nhãn | Mô tả | Ví dụ |
| --- | --- | --- | --- |
| Tên | 支払方法名（必須） | Tên hiển thị | GMO Direct |
| Phí | 手数料（必須） | Phí (¥) | 300 |
| Điều kiện | 利用条件 | Min–max tổng đơn (¥) | Min 0, max trống |
| Logo | ロゴ画像 | Upload, gợi ý 500×100 | Kéo thả; có thể thấy Powered by PQINA |
| Nút về | 支払方法一覧 | Về danh sách | — |
| Hiển thị | 表示 | Ẩn/hiện trên site | Tùy cấu hình |
| Lưu | 登録 | Ghi DB | — |

**8.1 Chi tiết từng trường (snapshot môi trường chụp màn hình — có thể khác theo shop)**

*支払方法名（必須）*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Tên phương thức hiển thị cho khách |
| Bắt buộc | Có（必須） |
| Ví dụ | GMO Direct |

*手数料（必須）*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Phí (kèm ký hiệu ¥) |
| Bắt buộc | Có（必須） |
| Ví dụ | 300 |

*利用条件*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Giới hạn tổng giá trị đơn (min ~ max) |
| Trợ giúp | Biểu tượng（？）cạnh nhãn |
| Ví dụ | Min 0 ¥; max để trống → không giới hạn trên |

*ロゴ画像*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Logo cạnh tên phương thức |
| Khuyến nghị | 500px × 100px（推奨サイズ） |
| Cách tải | Kéo thả hoặc chọn file — 「画像をドラッグ＆ドロップまたはファイルを選択」 |
| Giao diện | Có thể hiện Powered by PQINA |

**8.2 Thanh hành động cuối trang**

| Vị trí | Thành phần | Chức năng |
| --- | --- | --- |
| Trái | 支払方法一覧 | Về danh sách |
| Phải | 表示 | Hiển thị / ẩn trên storefront |
| Phải | 登録 | Lưu |

## 9. Logic nghiệp vụ

- Tổng đơn nằm trong **利用条件** → phương thức xuất hiện ở bước chọn thanh toán (kèm phí).
- Max để trống → chỉ giới hạn từ min trở lên.
- **表示** quyết định có offer hay không dù bản ghi còn.
- **Phí** theo quy tắc EC-CUBE (chi tiết công thức xem tài liệu core EC-CUBE).

## 10. Sự kiện & xử lý

| Sự kiện | Hệ thống |
| --- | --- |
| Click 支払方法一覧 | Về danh sách |
| Đổi 表示 | Cập nhật khi lưu (theo thiết kế form) |
| Click 登録 | Validate → lưu `dtb_payment` |
| Upload logo | Lưu file / media theo EC-CUBE |

## 11. Điều hướng (luồng)

```
Danh sách 支払方法設定
  → Mở chỉnh sửa một dòng
    → Màn edit (tài liệu này)
      → 支払方法一覧 → Danh sách
      → 登録 → Lưu (Ở lại hoặc redirect tùy phiên bản)
```

## 12. Quyền & phân quyền

- Cần **Admin** và quyền khu **店舗設定** / chỉnh sửa phương thức thanh toán.
- Khách không đăng nhập admin không truy cập được URL.

## 13. Tích hợp bên ngoài

- **GMO:** không cấu hình trên màn này; API và credential trong **Customize** (`GmoApiClient`) và biến `GMO_*`.
- Màn admin giúp khách chọn đúng **GMO Direct** trên checkout và thấy đúng phí / điều kiện.

**Ghi chú triển khai GMO Direct**

- Shop ID, endpoint, mock mode, v.v. → **biến môi trường** + code; **không** nằm trên form admin.
- Form **支払方法** chỉ quản **tên, phí, điều kiện đơn, logo, hiển thị**.

Tham chiếu kỹ thuật: `app/Customize/README_GMO_DIRECT.md`.

## 14. Môi trường

| Môi trường | Ghi chú |
| --- | --- |
| Local | URL có thể có `index.php`; port (vd. 8080) tùy Docker. |
| Staging / Production | Cùng path `/admin/setting/shop/payment/{id}/edit`; đổi domain và TLS. |

## 15. Trường hợp biên

- **Min > max** (nếu nhập cả hai): có thể báo lỗi validation.
- **Logo** sai dung lượng / định dạng: lỗi upload theo cấu hình server.
- **Id** URL không tồn tại: 404 hoặc lỗi tương ứng.
- **Đổi tên** nhầm: chỉ ảnh hưởng hiển thị; class thanh toán do cấu hình payment EC-CUBE.

## 16. Log & theo dõi

- Log admin tùy cấu hình EC-CUBE.
- Sau khi đổi **phí** hoặc **điều kiện**, nên test checkout với tổng đơn biên (0, gần ngưỡng, rất lớn).

## 17. Quan điểm kiểm thử

**Luồng chính**

- URL + id hợp lệ → form đúng dữ liệu (vd. GMO Direct, phí 300).
- Sửa và **登録** → reload / vào lại → giá trị đã lưu.

**Lỗi**

- Trường bắt buộc trống → có báo lỗi.
- Logo không hợp lệ → báo lỗi rõ.

**Biên**

- Max trống → đơn lớn vẫn thấy phương thức nếu min thỏa.
- **表示** ẩn → storefront không chọn được.
