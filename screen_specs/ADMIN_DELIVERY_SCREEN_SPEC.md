## 1. Tổng quan

**Mục đích**

- Cho phép quản trị viên chỉnh sửa **một phương thức vận chuyển** đã có: thông tin cơ bản (tên hiển thị, loại bán hàng), **liên kết với phương thức thanh toán**, **khung giờ giao hàng**, **phí theo tỉnh/thành**, ghi chú nội bộ và trạng thái hiển thị.

**Vai trò trong hệ thống**

- Quyết định **phương thức giao hàng** nào khách thấy tại checkout, **phí ship theo địa chỉ** (theo tỉnh), và **thanh toán nào được phép** khi chọn phương thức giao này.

**Đối tượng**

- Quản trị viên đã đăng nhập EC-CUBE Admin.

## 2. Phạm vi

**Trong phạm vi**

- Mở màn hình chỉnh sửa một `Delivery` theo `id` trong URL.
- Sửa các khối: **基本情報**, **取り扱う支払方法**, **お届け時間設定**, **都道府県別送料設定**, **店舗メモ** (nếu dùng).
- Lưu qua **登録**; chọn **表示** (hiển thị/ẩn) trước khi lưu; quay về danh sách bằng **配送方法一覧**.

**Ngoài phạm vi**

- Cấu hình **cổng thanh toán thật** (GMO, v.v.) — nằm ở màn / env phương thức thanh toán và Customize, không phải form này.
- Vận hành vận chuyển thực tế (API hãng vận chuyển) nếu shop chưa tích hợp.

## 3. Cấu trúc màn hình & chức năng

- Thanh **header** EC-CUBE (tên shop, tài khoản admin).
- **Menu trái:** khu **設定** → **店舗設定** → **配送方法設定**.
- **Vùng nội dung chính:** các **card** theo thứ tự: 基本情報 → 支払方法 → お届け時間 → 都道府県別送料 → 店舗メモ.
- **Thanh hành động cuối trang (conversion):** **配送方法一覧**, dropdown **表示**, nút **登録**.

## 4. Tham số & đường dẫn

| Tham số | Kiểu | Bắt buộc | Mô tả |
| --- | --- | --- | --- |
| `id` (URL) | số | Có | Id bản ghi `dtb_delivery`. Ví dụ: `/admin/setting/shop/delivery/3/edit` → `id = 3`. |

**Điều kiện truy cập**

| Mục | Giá trị |
| --- | --- |
| URL ví dụ (local) | `http://localhost:8080/index.php/admin/setting/shop/delivery/3/edit` |
| Quyền | Đăng nhập **Admin** EC-CUBE (đủ quyền khu **店舗設定**). |

**Điều hướng menu (sidebar)**

- **設定** → **店舗設定** → **配送方法設定**
- Tiêu đề trang (block title / sub): **配送方法設定** · **店舗設定**

## 5. Nguồn dữ liệu

| Nguồn | Mô tả |
| --- | --- |
| `dtb_delivery` | Tên hiển thị, tên dịch vụ, URL tra cứu, loại bán, trạng thái hiển thị, mô tả/memo, v.v. |
| `dtb_delivery_fee` | Phí theo từng tỉnh (Pref) gắn với delivery. |
| `dtb_delivery_time` | Các lựa chọn khung giờ giao (nếu có). |
| Liên kết **Payment** | Danh sách phương thức thanh toán được phép với delivery này (many-to-many trong core). |
| `ProductType` (販売種別) | Phân loại bán hàng gắn với delivery (vd. 販売種別A). |

## 6. Trạng thái màn hình

**6.1 Ban đầu**

- Form đã điền theo bản ghi delivery; phí từng tỉnh, checkbox thanh toán, danh sáchお届け時間 phản ánh DB.

**6.2 Lưu thành công**

- Sau **登録**, thông báo thành công theo chuẩn EC-CUBE (và có thể giữ tại màn edit hoặc chuyển — tùy phiên bản / cấu hình).

**6.3 Lỗi validation**

- Trường **必須** thiếu / phí không hợp lệ / chưa chọn đủ phương thức thanh toán (theo quy tắc form): hiển thị lỗi từng field.

**6.4 Đặc thù**

- Nút **全国一律** + **各都道府県に適用**: áp một mức phí nhập sẵn vào **tất cả** ô phí tỉnh (client-side trong template mặc định); giá trị chỉ ghi DB khi **登録**.

## 7. Bố cục giao diện

**7.1 Desktop (sơ đồ cây)**

```
Trang Admin EC-CUBE
 ├─ Header
 ├─ Sidebar: 設定 → 店舗設定 → 配送方法設定
 └─ Vùng chính
     ├─ Card 基本情報
     │   ├─ 配送業者名（必須）     [form.name]
     │   ├─ 配送方法名称（必須）   [form.service_name]
     │   ├─ お問い合わせ番号URL   [form.confirm_url]（？tooltip）
     │   └─ 販売種別（必須）       [form.sale_type]（？tooltip）
     ├─ Card 取り扱う支払方法（必須）[form.payments]
     ├─ Card お届け時間設定
     │   └─ ô nhập + 新規作成 + danh sách kéo thả (↑↓）
     ├─ Gợi ý: 項目の順番はドラッグ＆ドロップでも変更可能
     ├─ Card 都道府県別送料設定（必須）
     │   ├─ Hàng áp giá đồng loạt: [free_all] + 各都道府県に適用
     │   └─ Lưới 47 pref + ô phí ¥ (#set_fee_all)
     ├─ Card 店舗メモ（collapse）
     └─ Conversion: [配送方法一覧] ... [表示▼] [登録]
```

**7.2 Ghi chú nhãn vs entity**

- Trong locale tiếng Nhật mặc định của core: **`admin.setting.shop.delivery.delivery_name`** = **配送業者名** (map vào property **`name`** của `Delivery`), **`delivery_sevice_name`** = **配送方法名称** (map vào **`service_name`**).

**7.3 Mobile / màn hẹp**

- Cùng nội dung; layout thu gọn theo Bootstrap; sidebar có thể ẩn.

## 8. Chi tiết thành phần giao diện

**8.0 Bảng tóm tắt (ví dụ môi trường chụp màn)**

| Thành phần | Nhãn (JA) | Mô tả | Ví dụ |
| --- | --- | --- | --- |
| Tên (DB `name`) | 配送業者名 | Tên hãng / đơn vị vận chuyển | Cty ABC for GMO |
| Tên dịch vụ (`service_name`) | 配送方法名称 | Tên phương thức giao khách thấy | ABC for GMO |
| URL tra cứu | お問い合わせ番号URL | Link mẫu tra mã vận đơn (tùy shop) | Trống |
| Loại bán | 販売種別 | Dropdown `ProductType` | 販売種別A |
| Thanh toán | 取り扱う支払方法 | Checkbox từng payment | GMO Direct, 郵便振替, … |
| Giờ giao | お届け時間設定 | Thêm dòng + sắp xếp | Tùy cấu hình |
| Phí tỉnh | 都道府県別送料設定 | ¥ / tỉnh; áp đồng loạt | Hokkaido 404, … |
| Memo | 店舗メモ | Ghi chú nội bộ | Tùy |
| Về list | 配送方法一覧 | Link danh sách | — |
| Hiển thị | 表示 | Ẩn/hiện | — |
| Lưu | 登録 | POST form | — |

**8.1 Khối 基本情報**

*配送業者名（必須） — `name`*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Tên đơn vị / nhãn “carrier” theo bundle JA |
| Bắt buộc | Có |

*配送方法名称（必須） — `service_name`*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Tên phương thức giao (dịch vụ) |
| Bắt buộc | Có |

*お問い合わせ番号URL — `confirm_url`*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | URL hỗ trợ tra cứu mã vận chuyển (nếu shop dùng) |
| Bắt buộc | Không（có icon trợ giúp） |

*販売種別（必須） — `sale_type`*

| Thuộc tính | Nội dung |
| --- | --- |
| Ý nghĩa | Gắn delivery với loại sản phẩm / kênh bán trong EC-CUBE |
| Bắt buộc | Có（tooltip） |

**8.2 取り扱う支払方法**

- Danh sách **checkbox** theo các `Payment` đang có trong shop.
- **必須** trên card: phải chọn hợp lệ theo validation form (thường ít nhất một tùy cấu hình).
- Không chọn một phương thức → khách **không thể** dùng phương thức đó khi chọn delivery này ở checkout (nếu luồng core áp constraint này).

**8.3 お届け時間設定**

- Ô nhập tên khung giờ + nút **新規作成**: thêm một dòng trong collection `delivery_times`.
- Mỗi dòng có thể **↑↓**, **xoá**, **ẩn/hiện** (toggle) theo prototype admin.
- Thứ tự hiển thị có thể đổi bằng kéo thả / nút (cập nhật `sort_no` khi submit).

**8.4 都道府県別送料設定**

- **全国一律**: nhập một số tiền (form `free_all`) → **各都道府県に適用** copy vào mọi ô `fee` của từng tỉnh trong form (JavaScript `#set_fee_all`); nếu không phải số → `alert` theo message `admin.setting.shop.delivery.fee.invalid`.
- Dưới đó là **47 tỉnh** (nhãn pref + input phí).

**8.5 店舗メモ**

- Vùng **description** có thể thu gọn; tooltip giải thích mục đích memo nội bộ.

**8.6 Thanh conversion**

| Vị trí | Thành phần | Chức năng |
| --- | --- | --- |
| Trái | 配送方法一覧 | Về `admin_setting_shop_delivery` |
| Phải | 表示 | Trạng thái hiển thị delivery |
| Phải | 登録 | Lưu toàn form |

## 9. Logic nghiệp vụ

- **販売種別** lọc / gắn delivery với loại sản phẩm trong đơn (theo quy tắc core EC-CUBE khi tính phương thức giao khả dụng).
- **支払方法** ràng buộc tổ hợp **giao hàng + thanh toán** hiển thị cho khách.
- **Phí theo tỉnh** lấy từ địa chỉ giao (prefecture) khi tính ship.
- **表示** = ẩn → delivery không được chọn trên storefront (theo cách core dùng `visible`).

## 10. Sự kiện & xử lý

| Sự kiện | Hệ thống |
| --- | --- |
| Click 配送方法一覧 | GET danh sách delivery |
| 各都道府県に適用 | Set JS tất cả ô phí (validate số trước) |
| 新規作成 (お届け時間) | Thêm prototype vào DOM, cập nhật sort |
| Kéo thả / ↑↓ khung giờ | `updateSortNo` trước submit |
| 登録 | POST → validate → lưu `Delivery`, fees, times, payments |
| Đổi 表示 | Gửi kèm form khi đăng ký |

## 11. Điều hướng (luồng)

```
Danh sách 配送方法設定
  → Mở chỉnh sửa một dòng
    → Màn edit (tài liệu này)
      → 配送方法一覧 → Danh sách
      → 登録 → Lưu
```

Route tham khảo core: `admin_setting_shop_delivery` (list), `admin_setting_shop_delivery_edit` (edit).

## 12. Quyền & phân quyền

- Cần **Admin** và quyền chỉnh sửa **店舗設定** / delivery.
- Người không đăng nhập admin không truy cập URL.

## 13. Tích hợp bên ngoài

- **Không** có ô cấu hình API hãng vận chuyển chuẩn trên form này; URL tra cứu là trường tĩnh do shop điền.
- **GMO / cổng thanh toán:** chỉ xuất hiện gián tiếp qua checkbox **取り扱う支払方法** (vd. chọn **GMO Direct** nếu payment đó tồn tại). Chi tiết kết nối GMO xem `app/Customize/README_GMO_DIRECT.md` và màn **支払方法**.

## 14. Môi trường

| Môi trường | Ghi chú |
| --- | --- |
| Local | URL có thể có `index.php`; port (vd. 8080) tùy Docker. |
| Staging / Production | Cùng path `/admin/setting/shop/delivery/{id}/edit`; đổi domain và TLS. |

## 15. Trường hợp biên

- **Id** URL không tồn tại: 404 hoặc lỗi tương ứng.
- **Áp phí đồng loạt** với giá trị không phải số: cảnh báo, không set ô.
- **Xoá hết khung giờ** hoặc để trống: tùy validation / luồng checkout (có thể không bắt buộc có ít nhất một slot).
- **Không chọn payment nào** (nếu form cho phép): có thể gây lỗi khi lưu hoặc đơn không thanh toán được với delivery này.
- **Phí tỉnh** = 0 vs trống: làm rõ theo quy tắc EC-CUBE (thường nhập số cụ thể).

## 16. Log & theo dõi

- Log admin tùy cấu hình EC-CUBE.
- Sau khi đổi **phí tỉnh** hoặc **支払方法**, nên test checkout: vài tỉnh, vài phương thức thanh toán, và **代金引換** nếu dùng.

## 17. Quan điểm kiểm thử

**Luồng chính**

- URL + `id` hợp lệ → form khớp DB (vd. 配送業者名 / 配送方法名称, phí mẫu).
- Sửa phí một tỉnh + **登録** → vào lại → giá trị giữ.
- **各都道府県に適用** → tất cả ô cùng giá trị → **登録** → DB đồng bộ.

**Lỗi**

- Trường bắt buộc trống → báo lỗi form.
- Áp phí đồng loạt với ký tự không phải số → alert / không ghi đè ô.

**Biên**

- Thêm / xoá / sắp xếp **お届け時間** → lưu → thứ tự trên storefront (nếu hiển thị) đúng.
- Bỏ chọn một payment → checkout không còn tổ hợp delivery + payment đó.
- **表示** ẩn → storefront không chọn được phương thức giao này.
