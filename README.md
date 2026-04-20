# intern-attendance (Status Monitor)

Googleカレンダーの「勤務場所」と「不在」設定を自動抽出し、その日の稼働状況（出社・リモート・休暇）をSlackへ通知する管理ツールです。
Laravelをベースとしたバックエンド特化型構成で、画面を介さずセキュアかつ軽量に動作します。

---

## 📝 概要
`@ing` ドメインのGoogleワークスペース機能を活用し、チームメンバーの「どこで働いているか」「休みではないか」を毎朝Slackへ自動投稿します。
通常の予定（会議等）やタスクはあえて取得せず、**「稼働状況の把握」**にフォーカスしています。

## 🚀 動作フロー
1. **認可**: 管理者がCLIコマンドを実行し、Google OAuth認可（`offline`アクセス）を実施。取得した `refresh_token` を `.env` に保存。
2. **スケジュール起動**: LaravelのTask Scheduling（Cron）が指定時刻に起動。
3. **データ抽出**: Google Calendar APIの `eventTypes`（`workingLocation`, `outOfOffice`）を使用して、その日のメタデータを取得。
4. **ステータス判定**:
    - **勤務場所 (Working Location)**: インターン生の勤務場所及び、勤務時間を特定。
    - **不在 (Out of Office)**: 社員の方の休暇や中抜けを特定。
5. **Slack通知**: Incoming Webhook経由で、整形されたリストを各チームのチャンネルへ送信。

## 🛠️ 技術選定
| カテゴリ | 選定技術 | 選定理由 |
| :--- | :--- | :--- |
| **Backend** | Laravel 10.x | スケジュール実行とAPI連携の堅牢性が高く、保守が容易なため。 |
| **Database** | MySQL | カレンダーIDと通知対象の有効/無効フラグの管理。 |
| **Auth** | Google OAuth 2.0 | `.env` 管理のリフレッシュトークンによるオフラインアクセス。 |
| **Notification** | Slack Incoming Webhook | 迅速かつ安定した通知を実現するため。 |

---

## 🏗️ データベース設計
管理画面を持たないため、以下のテーブルで設定を管理します。

### `calendars` (通知対象管理)
| Column | Type | Description |
| :--- | :--- | :--- |
| `team_name` | VARCHAR | 通知時の見出しに使用するチーム名 |
| `calendar_id` | VARCHAR | GoogleカレンダーID (xxx@group.calendar.google.com) |
| `is_active` | BOOLEAN | 通知の有効/無効フラグ |

## 環境変数

### `.env` 設定
認証情報は環境変数でセキュアに管理します。
```env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REFRESH_TOKEN=your_refresh_token
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXX/YYY/ZZZ
---

## 🧪 実装時の主要ロジック
Google Calendar APIより、特定の `eventTypes` を指定してデータを取得します。

- **Endpoint**: `GET https://www.googleapis.com/calendar/v3/calendars/{calendar_id}/events`
- **判定ロジック**:
    - `eventType === 'outOfOffice'` ➔ **💤 不在（休暇）**
    - `eventType === 'workingLocation'` ➔ `workingLocationProperties` の値を参照して勤務時間や勤務形態を特定

---

## ⚙️ セットアップ

### 1. Google Cloud コンソールの設定
1. [Google Cloud Console](https://console.cloud.google.com/) でプロジェクトを作成。
2. **Google Calendar API** を有効化。
3. **OAuth 2.0 クライアントID** を作成（デスクトップアプリ または Webアプリケーション）。
4. `CLIENT_ID`, `CLIENT_SECRET` を `.env` に設定。

### 2. Laravel側の準備
1. DBマイグレーションを実行。
2. 初回認可コマンドを実行し、認証を完了させる。
   ```bash
   php artisan app:google-auth-init
