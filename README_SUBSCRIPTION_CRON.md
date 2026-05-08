# Hướng dẫn Cron — Gia hạn Subscription (`subscription:run`)

Tài liệu này mô tả cách lên lịch lệnh Symfony `subscription:run` trên máy dev (macOS) hoặc server production, khi EC-CUBE chạy trong Docker Compose.

## Lệnh thực thi

Trong project, lệnh xử lý các subscription đến hạn là:

```bash
php bin/console subscription:run [--limit=N] [--dry-run]
```

- **`--limit`**: tối đa bao nhiêu subscription xử lý mỗi lần chạy (mặc định trong code console là 50).
- **`--dry-run`**: chỉ kiểm tra / log, không charge GMO.

Khi `SUBSCRIPTION_ENABLED=0` trong môi trường, lệnh sẽ in thông báo và thoát thành công mà không xử lý.

## Chạy qua Docker Compose (đúng với repo này)

Dự án dùng **service tên `ec-cube`** (không phải `php`). Chỉ gọi **một** lần `compose` trong dòng lệnh.

Đúng:

```bash
docker compose \
  -f docker-compose.yml \
  -f docker-compose.dev.yml \
  -f docker-compose.pgsql.yml \
  exec -T ec-cube php bin/console subscription:run --limit=20
```

Sai thường gặp:

| Sai | Hậu quả |
|-----|--------|
| `docker compose compose ...` | Lỗi `unknown docker command: "compose compose"` |
| `exec php ...` thay vì `exec ec-cube ...` | Service không tồn tại hoặc sai container |
| Thiếu `-T` khi gọi từ cron | Có thể lỗi TTY / hành vi không ổn định |

Tham số **`-T`** cho `docker compose exec` nên dùng khi chạy từ **cron** (không có terminal tương tác).

## Script có sẵn trong repo

File `scripts/subscription-cron.sh` đã gói đủ compose files và `exec -T ec-cube`. Có thể chỉnh biến môi trường:

| Biến | Ý nghĩa | Mặc định |
|------|---------|----------|
| `EC_CUBE_ROOT` | Đường dẫn thư mục gốc project | Thư mục cha của `scripts/` |
| `SUBSCRIPTION_RUN_LIMIT` | Giá trị `--limit` | `50` |
| `SUBSCRIPTION_CRON_LOG` | File log append | `/tmp/subscription-cron.log` |

Cấp quyền thực thi (một lần):

```bash
chmod +x scripts/subscription-cron.sh
```

Chạy thử tay:

```bash
/path/to/ec-cube-4.2/scripts/subscription-cron.sh
tail -n 80 /tmp/subscription-cron.log
```

## Crontab trên macOS (localhost)

1. Mở editor crontab: `crontab -e`
2. Đặt **PATH** đủ để tìm `docker` (tuỳ máy):

   - Apple Silicon (Homebrew): thường là `/opt/homebrew/bin`
   - Intel Mac: thường là `/usr/local/bin`

3. Thêm dòng lịch. Ví dụ **mỗi phút** (phù hợp test chu kỳ 10 phút; tránh overlap quá nhiều job):

   ```cron
   * * * * * PATH=/usr/local/bin:/usr/bin:/bin /path/to/ec-cube-4.2/scripts/subscription-cron.sh
   ```

   Hoặc **mỗi 5 phút**:

   ```cron
   */5 * * * * PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin /path/to/ec-cube-4.2/scripts/subscription-cron.sh
   ```

Thay `/path/to/ec-cube-4.2` bằng đường dẫn thật trên máy bạn.

**Lưu ý:** Cron chỉ chạy khi máy bật và đồng hồ hệ thống chạy. Nếy tắt máy hoặc sleep, các lần chạy sẽ bị bỏ qua cho tới lần tới.

## Điều kiện để cron thực sự “có việc”

1. **Stack Docker đang chạy**: `docker compose ... up -d` (ít nhất `ec-cube` và `postgres` khi dùng `docker-compose.pgsql.yml`).
2. **File `.env.local`** tồn tại (Compose dev đọc qua `env_file`) và có `SUBSCRIPTION_ENABLED=1` nếu muốn xử lý thật.
3. **Đường dẫn tuyệt đối** trong crontab tới script hoặc tới project nếu dùng biến `EC_CUBE_ROOT`.

## Kiểm tra sau khi cài cron

```bash
# Xem log do script ghi
tail -f /tmp/subscription-cron.log

# Chạy một shot trong container (không qua cron)
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.pgsql.yml exec -T ec-cube php bin/console subscription:run --limit=5 --dry-run
```

## Production (gợi ý)

- Chạy cron **trên server** nơi Docker Compose (hoặc orchestrator) luôn sống; interval khuyến nghị: mỗi 1–5 phút hoặc theo SLA nghiệp vụ.
- Giới hạn tải GMO/API: dùng `--limit` hợp lý, tránh nhiều máy cùng chạy song song một DB nếu không có khóa phân tán.
- Phân tách log (rotate), cảnh báo khi `dtb_subscription_order.billing_status = failed` tăng đột biến hoặc timeout GMO.

## Tài liệu liên quan

- Đặc tả tính năng subscription: `README_SUBSCRIPTION_GMO.md`
