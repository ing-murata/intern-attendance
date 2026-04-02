# intern-attendance

Googleカレンダーの予定を自動で取得し、その日の出勤予定者をSlackへ通知する管理ツールです。

## 📝 概要
インターン生の出勤・退勤予定をGoogleカレンダーから抽出し、指定したSlackチャンネルへ自動投稿します。
データベースを構築せず、Googleカレンダーを「情報の真実（Single Source of Truth）」として活用することで、運用・保守コストを最小限に抑えています。

## 🚀 ユーザーフロー
1. **予定入力**: インターン生が共通のGoogleカレンダーに出勤予定を入力。
2. **データ抽出**: GAS（Google Apps Script）が定期実行され、当日のイベントを取得。
3. **Slack通知**: 整形された出勤予定者リストがSlackチャンネルへ投稿。

## 🛠️ 技術選定
| カテゴリ | 選定技術 | 選定理由 |
| :--- | :--- | :--- |
| **Runtime** | Google Apps Script (GAS) | Googleサービスとの親和性が高く、サーバーレス（無料）で運用可能。 |
| **Trigger** | GAS Time-driven Triggers | 毎朝の定期実行をノーコードで設定でき、メンテナンスが容易。 |
| **Notification** | Slack Incoming Webhook | シンプルな設定でリッチなメッセージ投稿が可能。 |

## 🏗️ システム構成
1. **Google Calendar API**: 共有カレンダーから当日のイベントデータを取得。
2. **GAS Logic**: 取得したイベントから「氏名」「開始時間」「終了時間」を抽出・パース。
3. **Incoming Webhook**: 定型文に整形し、Slack API経由で通知を実行。

## ⚙️ セットアップ（導入手順）

### 1. Googleカレンダーの準備
- 勤怠管理用の「共有カレンダー」を作成します。
- カレンダー設定から **カレンダーID**（`xxx@group.calendar.google.com`）をコピーしておきます。

### 2. Slack Webhookの作成
- Slack Appを作成し、`Incoming Webhooks` を有効化します。
- 通知先のチャンネルを選択し、発行された **Webhook URL** をコピーします。

### 3. GASの実装
- [Google Apps Script](https://script.google.com/) で新しいプロジェクトを作成します。
- `main.gs` にコードを貼り付け、上記で取得した「カレンダーID」と「Webhook URL」をスクリプトプロパティ（または定数）に設定します。

### 4. トリガーの設定
- GASエディタ左側の時計アイコン（トリガー）をクリック。
- `notifyDailyAttendance` 関数を「時間主導型」で、毎朝任意の時間（例：午前8時〜9時）に実行されるよう設定します。

## 💡 工夫・拡張のアイデア
- **名前の紐付け**: カレンダーのタイトルからSlackのメンバーIDを逆引きしてメンションを送る。
- **欠席・リモート対応**: 「欠席」「リモート」などのキーワードが含まれる場合にラベルを付けて表示。
