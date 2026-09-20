# Study Progress

Slackの日報から今週の学習時間を集計し、目標と一緒にWordPressへ送信する個人用ツールです。WordPressプラグインがブログの右下に学習状況カードを表示します。

## 公開する設定と手元に残す設定

| 手元だけに残すファイル（Gitで除外） | GitHubに公開する見本 |
| --- | --- |
| `.env` | `.env.example` |
| `macos/*.plist`（見本を除く） | `macos/study-progress.example.plist` |

本物の設定をダミーに書き換える必要はありません。プッシュ後に設定を戻す作業も不要です。認証情報は見本やソースコードに書かず、`.env` に設定してください。

## セットアップ

ローカルではPython 3.11を使用しています。プロジェクトのルートで実行してください。

```sh
python3 -m venv .venv
source .venv/bin/activate
python -m pip install -r requirements.txt
```

初回のみ `.env.example` を `.env` にコピーし、値を入力します。既存の `.env` がある場合は、そのまま使用してください。

- `SLACK_BOT_TOKEN`：対象チャンネルの履歴を読み取れるBotトークン
- `SLACK_APP_TOKEN`：常駐監視のSocket Mode用Appトークン
- `SLACK_REPORT_CHANNEL_ID`：日報チャンネルID
- `SLACK_STUDY_GOAL_CHANNEL_ID`：目標チャンネルID
- `WP_SITE_URL`：WordPressの正式なHTTPS URL（リダイレクトしないもの）
- `WP_USERNAME`：設定管理権限のあるWordPressユーザー名
- `WP_APP_PASSWORD`：そのユーザーのWordPressアプリケーションパスワード

Slack側でBotが対象チャンネルに参加し、履歴を読み取れるように設定してください。常駐監視にはSocket Modeと対象チャンネルのメッセージイベント受信の設定も必要です。

WordPressには `wordpress/study-progress/` をプラグインとして配置して有効化します。目標チャンネルの最新の通常投稿はブログに公開されるため、公開してよい内容だけを投稿してください。

## 実行

1回同期する場合：

```sh
.venv/bin/python python/sync_progress.py
```

新規投稿を監視して同期する場合：

```sh
.venv/bin/python python/watch_slack.py
```

監視中の投稿編集・削除は同期のきっかけになりません。時間集計はローカルのタイムゾーンで月曜午前8時から翌月曜午前8時までを対象にし、日報の取得は最大100件です。

## Macの自動起動設定

`macos/study-progress.example.plist` は設定の見本です。新規設定時は別名の `.plist` にコピーし、`/ABSOLUTE/PATH/TO/study-progress` を実際のプロジェクトの絶対パスに置き換えてください。ログ出力先の `logs` ディレクトリも作成してください。

すでに実設定がある場合は、そのファイルを使用します。見本を上書きしたり、動作中の設定を置き換えたりする必要はありません。

## 公開前の確認

```sh
git status --short
git diff --cached
```

ステージした内容に本物のトークン、パスワード、ログ、個人用の設定が含まれていないことを確認してください。既存のコミットには作者名とメールアドレスが記録されており、履歴ごと公開するとそれらも公開されます。
