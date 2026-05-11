## 1. 概要

**目的**

- 管理者が**subscription（定期購読）の一覧を閲覧**し、ステータス / 次回課金日の範囲 / リトライ回数 / 特定顧客で**絞り込み**、各subscriptionの**詳細画面へ遷移**できるようにする。

**システム上の役割**

- 運用・サポート向け：`dtb_subscription` の全件（または顧客別）を俯瞰する。新規subscription作成は**行わない**（subscriptionはチェックアウト成功後に生成）。GMO課金も一覧画面上では**直接実行しない**（課金・解約は**詳細画面** `/admin/subscription/{id}` またはcron `subscription:run` で実施）。

**対象ユーザー**

- EC-CUBE管理画面にログイン済みの管理者。

## 2. スコープ

**スコープ内**

- ページネーション付きのsubscriptionテーブルを表示。
- **GETフィルタフォーム**の送信（status、次回課金日の開始/終了、retry_count、顧客別フィルタ時の非表示customer_id）。
- 画面遷移：subscription**詳細**、**顧客プロフィール**、**フィルタ解除**（queryなしで全ショップ一覧へ戻る）。

**スコープ外**

- subscriptionスナップショットの作成/編集、GMO課金、subscription解約（詳細画面 / コマンドを参照）。
- `GMO_*`、`SUBSCRIPTION_*` の設定 — 環境変数およびCustomizeで管理。本画面にフォームはない。

## 3. 画面構成・機能

- EC-CUBE **ヘッダー**（ショップ名、管理者アカウント）。
- **サイドバー：** **受注管理**（order）→ **Subscription（定期購読）**（`menus`: `order`, `subscription_list`）。
- **メインコンテンツ：**
  - （条件により）**SUBSCRIPTION_ENABLED=0** の警告。
  - （queryにより）**customer_id** でフィルタ中のバナー + 顧客編集リンク。
  - **フィルタカード**（ブロックラベルはUI翻訳の「ステータス」を使用）。
  - **テーブルカード**（結果） + **Knpページャー**（2ページ以上の場合）。

## 4. パラメータ・パス

| パラメータ / ルート | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| メインルート | — | — | `admin_subscription_index`: `GET /admin/subscription` |
| ページネーション | — | なし | `admin_subscription_index_page`: `GET /admin/subscription/page/{page_no}` |
| Query `status` | 文字列 | なし | 空（全件）、または `pending_activation`, `active`, `paused`, `past_due`, `cancelled`, `expired` のいずれか |
| Query `next_from` | 日付 | なし | ショップ設定タイムゾーン（`ECCUBE_TIMEZONE`）の日の開始。クライアントは `YYYY-MM-DD` を送信 |
| Query `next_to` | 日付 | なし | 同タイムゾーンの 23:59:59 |
| Query `retry_count` | 数値 | なし | `dtb_subscription.retry_count` と完全一致 |
| Query `customer_id` | 数値 | なし | 該当する `dtb_customer.id` のsubscriptionのみ表示。フィルタ送信時は `<input type="hidden">` で保持。ページャーは現在のqueryを継承 |

**アクセス条件**

| 項目 | 値 |
| --- | --- |
| URLの例（ローカル） | `http://localhost:8080/index.php/admin/subscription` |
| 権限 | EC-CUBE **管理画面**ログイン済み（受注管理画面と同等のアクセス権限）。 |

**メニュー遷移（サイドバー）**

- **受注管理** → **Subscription（定期購読）**

ページタイトル（ブロック）：翻訳キー `admin.subscription.title_list`。サブタイトル：`admin.subscription.sub_title`（受注管理）。

## 5. データソース

| ソース | 説明 |
| --- | --- |
| `dtb_subscription` | 表示フィールド：id, status, plan_type, interval_count, next_billing_at, retry_count, max_retry, total_amount, `customer_id` 紐付け。 |
| `dtb_customer` | ID + 氏名を表示するためにJOIN。`admin_customer_edit` へのリンク。 |

**クエリ**

- リポジトリ：`Customize\Repository\SubscriptionRepository::getAdminListQueryBuilder(...)` — `ORDER BY s.id DESC`。

**1ページあたりの件数**

- パラメータ `eccube_default_page_count`（EC-CUBE設定）。

## 6. 画面状態

**6.1 初期表示 / フィルタ後**

- テーブルに現在のページを表示。フィルタフォームはqueryを反映（またはデフォルト）。

**6.2 レコードなし**

- プレースホルダー行「—」が全列にまたがって表示。

**6.3 SUBSCRIPTION_ENABLED = false**

- 黄色の警告バー（`admin.subscription.disabled`）：更新/課金機能が無効である可能性を通知。**一覧画面自体は閲覧可能**。

**6.4 顧客でフィルタ中**

- 水色の通知バー（`customer_context` + `open_customer`）：`/admin/customer` または顧客画面のsubscriptionカードからのディープリンク。

## 7. レイアウト

**7.1 デスクトップ（ツリー構成図）**

```
Admin EC-CUBE ページ
 ├─ Header
 ├─ Sidebar: 受注管理 → Subscription（定期購読）
 └─ メインエリア
     ├─ (任意) Subscription無効警告
     ├─ (任意) 顧客フィルタバナー → 顧客編集リンク
     ├─ フィルタカード
     │   ├─ ステータス [select]
     │   ├─ 次回課金日 開始 [date]
     │   ├─ 次回課金日 終了 [date]
     │   ├─ リトライ回数 [number]
     │   ├─ [検索] [フィルタ解除]
     │   └─ (hidden) customer_id（存在する場合）
     └─ テーブルカード
         ├─ テーブル: ID | 顧客 | ステータス | プラン | 次回課金日 | リトライ | 合計 | [詳細]
         └─ ページャー（2ページ以上の場合）
```

**7.2 モバイル / 狭い画面**

- テーブルは横スクロール（`table-responsive`）。フィルタフォームはBootstrapカラムで縦並び。

## 8. UI部品の詳細

**8.0 サマリーテーブル**

| 部品 | ラベル（翻訳キー） | 説明 |
| --- | --- | --- |
| 無効警告 | `disabled` | `%env(bool:SUBSCRIPTION_ENABLED)%` = false の場合 |
| 顧客バナー | `filter.customer_context` + `filter.open_customer` | `filters.customer_id` に値がある場合 |
| フィルタカードタイトル | `filter.status` を再利用（カードヘッダー） |
| ステータスセレクト | `filter.status` + `filter.status_all` + 各ステータス |
| 次回課金日 開始/終了 | `next_from`, `next_to` | `type="date"` の入力欄 |
| リトライ | `retry_count` | 0以上の整数 |
| ボタン | `search`, `reset` | GETで送信 / queryなしで `/admin/subscription` へリンク |
| ID列 | `table.id` | `Subscription.id` |
| 顧客 | `table.customer` | `admin_customer_edit` へのリンク、または「—」 |
| ステータス | `table.status` | バッジ + `status` 文字列 |
| サイクル | `table.plan` | `planType × intervalCount` |
| 次回課金日 | `table.next_billing` | フィルタ `date_sec` |
| リトライ | `table.retry` | `retryCount / maxRetry` |
| 合計 | `table.total` | フィルタ `price` |
| 詳細 | `table.action` | `admin_subscription_detail` へのリンク |

**8.1 日付フィルタに関する注記**

- コントローラの `parseDateBoundary`：`YYYY-MM-DD` 文字列が不正な場合 → その条件を無視。

**8.2 ページャー**

- `@admin/pager.twig` をインクルード（ルート `admin_subscription_index_page`）。現在のqueryパラメータがページURLにマージされる。

## 9. ビジネスロジック

- 一覧は**読み取り** + フィルタのみ。本画面上でsubscriptionを変更しない。
- `status` フィルタが空 → ステータス条件を適用しない。
- 顧客フィルタはDBで有効な `customer_id > 0` のみ適用 — 存在しない顧客IDでもHTTPエラーにはならず、**0件**が返る可能性あり。
- **フィルタ解除**は一覧の初期状態へ遷移：`customer_id` を含む**すべてのフィルタ**を解除。

## 10. イベント・処理

| イベント | システム動作 |
| --- | --- |
| フィルタフォーム送信 | GET → テーブルを再描画 + queryストリング反映 |
| フィルタ解除をクリック | GET `/admin/subscription`（queryなし） |
| 詳細をクリック | GET `/admin/subscription/{id}` |
| 顧客名/リンクをクリック | GET `/admin/customer/{id}/edit` |
| ページャーでページ選択 | GET `/admin/subscription/page/N` + 現在のqueryを保持 |

## 11. 画面遷移（フロー）

```
受注管理メニュー → Subscription
  ├─ (経由) 顧客一覧: Sub.列 → ?customer_id=X
  ├─ (経由) 顧客詳細: subscriptionカード → フィルタ済みリンク
  ├─ フィルタ / ページネーション → 同一一覧画面
  └─ 詳細 → POSTアクション（リトライ / 解約…）は別画面
```

総合参照：`README_SUBSCRIPTION_GMO.md`。

## 12. 権限・アクセス制御

- 管理画面（`/admin/order` と同等のアクセス方式）にログイン済みのユーザーのみ。詳細な会員権限はショップの実装（Customizeで後からルートを制限可能）に依存。

## 13. 外部連携

- **GMO / 課金：** 一覧画面ではAPIを呼び出さない。
- 実際の更新処理：**`subscription:run`** およびUI上では**詳細画面**。関連ドキュメント：`README_SUBSCRIPTION_GMO.md`、`app/Customize/README_GMO_DIRECT.md`。

## 14. 環境

| 環境 | 備考 |
| --- | --- |
| ローカル | URLに `index.php` が含まれる場合あり。ポート（例：8080）はDocker設定に依存。 |
| ステージング / 本番 | 固定パス `/admin/subscription`、ドメインおよびTLSを変更。 |

## 15. エッジケース

- フィルタ条件に一致するsubscriptionなし → テーブルにプレースホルダー1行を表示。
- `customer_id` が存在しない顧客を指定 → 特別なHTTPエラーなし、0件の結果となる可能性。
- 手動URLで日付形式が不正 → コード上で無視される可能性あり（その条件でフィルタしない）。
- **フィルタ解除**は顧客フィルタを含むすべてのフィルタを解除。

## 16. ログ・監視

- 運用追跡は主に詳細画面 + cron + アプリケーションログ `[subscription]`（課金処理）。
- 一覧画面は読み取り専用。ショップのポリシーに応じてHTTP管理ログを有効化可能。

## 17. テスト観点

**主要フロー**

- GET `/admin/subscription` → データの有無に応じてページネーションが正しく表示されること。
- 各フィルタ条件を単独・組み合わせで送信。`customer_id` がページング時にも保持されること。

**エラー / UX**

- フィルタ解除でqueryなしの全件一覧に戻ること。
- SUBSCRIPTION_DISABLED時：警告が表示され、一覧は閲覧可能であること。

**エッジ**

- 不正な `page_no`：EC-CUBEの KnpPaginator デフォルト動作に従う。
- 同一顧客に複数subscription → 各行が1レコードで、詳細リンクのIDが正しいこと。
