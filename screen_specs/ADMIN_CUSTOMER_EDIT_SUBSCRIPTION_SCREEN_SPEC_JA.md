## 1. 概要

**目的**

- EC-CUBE管理画面の**会員編集・会員登録**画面（`会員登録`）。本Customizeリポジトリでは、顧客に**ID**（保存済みレコード）がある場合、**Subscription（定期購読）**ブロックを追加表示し、管理者がその顧客に紐づくすべてのsubscriptionを**一覧で確認**し、フィルタ済みsubscription一覧画面または各subscriptionの詳細画面へ**遷移**できるようにする。

**システム上の役割**

- 会員情報・配送先・注文履歴の横に**顧客 ↔ subscription**のコンテキストを付加する。このカードでは新規subscriptionの作成は**行わず**、課金・解約も**行わない**（課金・解約は `/admin/subscription/{id}` または cronで実施）。

**対象ユーザー**

- EC-CUBE管理画面にログイン済みの管理者。

## 2. スコープ

**スコープ内（Subscription Customize部分）**

- データがある場合は概要テーブル、ない場合は空メッセージを含む**Subscription（定期購読）**カードを表示する。
- **この顧客のsubscription一覧**ボタン → `GET /admin/subscription?customer_id={Customer.id}`。
- 各行の**詳細**ボタン → `GET /admin/subscription/{id}`。

**スコープ内（EC-CUBEコア — 参照のみ）**

- 顧客情報フォーム、住所、注文履歴、メモ、**登録**保存など — テンプレート元 `src/Eccube/Resource/template/admin/Customer/edit.twig` およびコントローラ `CustomerEditController` を参照。

**スコープ外**

- subscription作成（チェックアウト / 初回決済後に実施）。
- GMO設定、`SUBSCRIPTION_ENABLED`、リトライポリシー — `README_SUBSCRIPTION_GMO.md` を参照。

## 3. 画面構成・機能

- **ヘッダー** + **サイドバー：** **会員管理** → **会員登録** (`menus`: `customer`, `customer_edit`)。
- **メインエリア：** 顧客フォーム（コア） + 各カード（住所等）。
- **Subscriptionカード（Customize）：** **お届け先住所**カードの**後**、**注文履歴**カードの**前**に配置。`id="ex-customer-subscriptions"` でテスト・ドキュメント参照用にアンカーを設定。
- **コンバージョンバー**（ページ下部）：**会員一覧**、会員ステータスドロップダウン、**登録**（コア）。

## 4. パラメータ・パス

| パラメータ | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `id`（URL） | 数値 | あり（編集時） | `dtb_customer` のID。例：`/admin/customer/50000/edit` → `id = 50000`。 |
| `page_no`, `page_count`（クエリ） | 数値 | なし | 同一URL上の**注文履歴**ページネーション（コア）。subscriptionテーブルには影響なし。 |

**アクセス条件**

| 項目 | 値 |
| --- | --- |
| URLの例（ローカル） | `http://localhost:8080/index.php/admin/customer/50000/edit` |
| 権限 | 管理画面ログイン済み、**会員管理**エリア（顧客編集）。 |

**メニュー遷移**

- **会員管理** → **会員登録**（編集時：新規作成と同じメニュー。コンテキストは特定の顧客 `id`）。

## 5. データソース

| ソース | 説明 |
| --- | --- |
| `dtb_customer` | 顧客フォーム情報（コア）。 |
| `dtb_subscription` | 現在の顧客の `customer_id` に一致するsubscription。クエリ：`Customize\Repository\SubscriptionRepository::findByCustomerIdForAdmin($customerId)` — `ORDER BY id DESC`。 |
| Twig変数 `CustomerSubscriptions` | **Customize**が `kernel.view` 経由で注入（コアコントローラは変更しない）：`Customize\EventSubscriber\Admin\CustomerSubscriptionViewSubscriber`、ルート `admin_customer_edit` / `admin_customer_new` 対象。 |

**備考**

- **会員新規追加**（`admin_customer_new`、`Customer.id` 未設定）：Subscriptionカードは**レンダリングしない**（`{% if Customer.id %}`）。

## 6. 画面状態

**6.1 保存済み顧客、subscriptionあり**

- テーブルに1行以上表示。ステータスバッジ（例：`active`）。

**6.2 保存済み顧客、subscriptionなし**

- カードは表示。内容：`admin.customer.subscription_empty`（翻訳テキスト、例：「この顧客にはsubscriptionがありません。」）。

**6.3 新規顧客（未保存）**

- Subscriptionカードなし。

**6.4 登録ボタン押下後（コア）**

- 同じ `admin_customer_edit`（`id` 付き）にリダイレクト。subscriptionデータはDBから再読み込み。

## 7. レイアウト

**7.1 Subscriptionカード（構成図）**

```
Card #ex-customer-subscriptions
 ├─ Header
 │   ├─ タイトル: admin.customer.subscription_heading (Subscription（定期購読）)
 │   └─ [この顧客のsubscription一覧] → admin_subscription_index?customer_id=
 └─ Body
     ├─ (データあり) table-responsive
     │   └─ テーブル: ID | ステータス | サイクル | 次回課金日 | 合計金額 | [詳細]
     └─ (データなし) text-muted テキスト (subscription_empty)
```

**7.2 テーブル列（テンプレート準拠）**

| 列 | データソース（エンティティ） |
| --- | --- |
| ID | `Subscription.id` |
| ステータス | `Subscription.status`（バッジ） |
| サイクル | `planType` × `intervalCount` |
| 次回課金日 | `nextBillingAt`（フィルタ `date_sec`） |
| 合計金額 | `totalAmount`（フィルタ `price`） |
| アクション | **詳細**リンク → `admin_subscription_detail` |

## 8. UI部品の詳細（Subscription）

**8.0 サマリーテーブル**

| 部品 | 翻訳キー / ラベル | 機能 |
| --- | --- | --- |
| カードタイトル | `admin.customer.subscription_heading` | ブロック識別 |
| 一覧ボタン | `admin.customer.subscription_list_link` | `customer_id` でフィルタ済みの管理subscription一覧を開く |
| ID列 | `admin.customer.subscription_col_id` | subscription識別子 |
| ステータス列 | `admin.subscription.table.status` | 業務ステータス |
| サイクル | `admin.subscription.table.plan` | プラン + 間隔倍数 |
| 次回課金日 | `admin.subscription.table.next_billing` | 次回予定課金日 |
| 合計 | `admin.subscription.table.total` | 金額スナップショット（¥） |
| 詳細 | `admin.customer.subscription_open_detail` | subscription管理詳細画面へ遷移 |
| 空メッセージ | `admin.customer.subscription_empty` | レコードなし |

**8.1 インライン編集なし**

- カードは**読み取り専用**。課金・解約などの操作は `/admin/subscription/{id}` 画面で実施する。

## 9. ビジネスロジック

- カード上のsubscription一覧は、顧客の注文ページネーションとは**独立**している。
- subscription表示順：`id` DESC（リポジトリに準拠、最新が先頭）。
- ステータスは文字列そのまま表示（`active`、`past_due` 等）— 管理subscription一覧画面と統一。

## 10. イベント・処理

| イベント | システム動作 |
| --- | --- |
| 「この顧客のsubscription一覧」クリック | GET `/admin/subscription?customer_id={id}` |
| 「詳細」クリック | GET `/admin/subscription/{subId}` |
| 顧客フォーム送信 + 登録 | POSTコア → リダイレクト（詳細はここでは省略） |

## 11. 画面遷移（フロー）

```
会員一覧 / 他画面
  → 会員編集 (customer/{id}/edit)
      ├─ Subscriptionカード: テーブル閲覧
      ├─ → subscription一覧 (?customer_id=)
      └─ → subscription詳細 / 会員編集に戻る
```

参照：`screen_specs/ADMIN_SUBSCRIPTION_LIST_SCREEN_SPEC.md`。

## 12. 権限・アクセス制御

- 標準の会員編集画面と同じ**顧客編集権限**を使用。subscriptionブロック専用のルートはなし。

## 13. 外部連携

- **GMO / 課金：** このカードからは呼び出さない。
- **データ同期：** subscriptionはチェックアウトフロー + `subscription:run` / 管理subscription詳細画面で更新。顧客画面のF5でDBの最新状態を反映。

## 14. 環境

| 環境 | 備考 |
| --- | --- |
| ローカル | URLに `index.php` が含まれる場合あり。ポートはDocker設定に依存。 |
| ステージング / 本番 | パス `/admin/customer/{id}/edit`、ドメインおよびTLSを変更。 |

## 15. エッジケース

- 顧客は存在するがsubscriptionなし → カード + 空メッセージを表示。
- 複数subscription → 複数行、ヘッダーの一覧ボタンは共通。
- テンプレートオーバーライド：ファイルは `app/template/admin/Customer/edit.twig` に配置。EC-CUBEアップグレード時、コアが同ファイルを変更した場合は**マージ**が必要。

## 16. ログ・監視

- subscriptionブロック専用のログはなし。subscription運用ログはアプリログ / subscription詳細画面で確認。

## 17. テスト観点

**主要フロー**

- subscriptionがある顧客 → カードに正しい行数が表示され、`customer_id` リンクと `detail` IDリンクが正しいこと。

**空状態**

- subscriptionがない顧客 → `subscription_empty` メッセージが表示されること。

**エッジ**

- 新規顧客（ID未設定） → カードが表示されないこと。
- subscription作成後（チェックアウト経由） → 顧客編集画面を再度開く → 新しい行が表示されること。

**実装ファイル**

- Twig: `app/template/admin/Customer/edit.twig`（ブロック `ex-customer-subscriptions`）。
- Subscriber: `app/Customize/EventSubscriber/Admin/CustomerSubscriptionViewSubscriber.php`。
- ロケール: `app/Customize/Resource/locale/messages.ja.yaml` / `messages.en.yaml` — キー `admin.customer.subscription_*`。
