# intern-attendance (Laravel & TypeScript Version)

Googleカレンダーの予定を自動取得し、その日の出勤予定者をSlackへ通知する管理ツールです。
GAS版から移行し、データベースによる多角的な管理とセキュリティ強化を実現しています。

---

## 📝 概要
インターン生の出勤予定をGoogleカレンダーから抽出し、Slackへ自動投稿します。
カレンダーIDをデータベースで管理することで、将来的な複数チームへの対応や、詳細なユーザー認可（ドメイン制限）を可能にしています。

## 🚀 ユーザーフロー
1. **認証**: 管理者（`@ing`ドメイン保持者）がGoogle OAuthでログイン。
2. **設定**: 管理画面から「カレンダーID」をDBに登録・更新。
3. **データ抽出**: Laravelのスケジュール機能（Cron）がバックグラウンドで動作し、API経由でイベントを取得。
4. **Slack通知**: 整形されたリストが指定チャンネルへ自動投稿。

## 🛠️ 技術選定
| カテゴリ | 選定技術 | 選定理由 |
| :--- | :--- | :--- |
| **Backend** | Laravel (PHP) | 認証・DB操作・API連携を迅速に開発でき、長期保守に適しているため。 |
| **Frontend** | React / TypeScript | 管理画面の型安全性を担保し、リッチなUIを提供するため。 |
| **Database** | MySQL | カレンダーID、通知設定、および将来的な勤怠ログの保存。 |
| **Auth** | Laravel Socialite | `@ing` ドメインに限定したGoogleログインを容易に実装するため。 |

---

## 🧪 実装前の重要確認事項 (PoC)
開発に着手する前に、**Postman** を使用して以下の疎通確認を必ず行います。

### 1. カレンダーIDによる取得検証
カレンダーIDだけで取得可能か、アクセストークンが必要かを以下の手順で確認します。
* **Endpoint**: `GET https://www.googleapis.com/calendar/v3/calendars/{calendar_id}/events`
* **Auth**: Postmanの `Auth` タブから `OAuth 2.0` を設定。
* **検証ポイント**: 
    * 非公開カレンダーの場合、アクセストークンが必須であることを確認。
    * `401 Unauthorized` が出る場合、トークン取得フロー（Client ID/Secret）の不備を確認。
    * `403 Forbidden` の場合、対象カレンダーの共有設定に「閲覧権限」があるか確認。

### 2. アクセストークンの要件
* 定期実行（バックグラウンド）を行うため、**アクセストークン**の保持と、期限切れ時に自動更新するための **リフレッシュトークン** の保存が必要であることを確認します。

---

## 🏗️ システム構成
1. **Google Calendar API**: 予定データの取得。
2. **Laravel Backend**: 
    - **Schedule/Command**: 定期実行ロジック。
    - **Database**: `calendars` テーブル（`calendar_id` を保持）。
    - **Security Middleware**: `@ing` ドメイン以外のアクセスを遮断。
3. **Slack API**: Incoming Webhookによる通知実行。

---

## ⚙️ セットアップ（導入手順）

### 1. Google Cloud プロジェクトの準備
- Google Calendar API を有効化。
- **OAuth 2.0 クライアントID** を作成（`@ing` ドメインからのログインを許可設定）。
- 取得した `CLIENT_ID`, `CLIENT_SECRET` を `.env` に設定。

### 2. データベースの準備
- カレンダー情報を管理するテーブルを作成します。
```sql
CREATE TABLE calendars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    calendar_id VARCHAR(255) NOT NULL, -- xxx@group.calendar.google.com
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
