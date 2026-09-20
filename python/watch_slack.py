import os

from dotenv import load_dotenv
from slack_bolt import App
from slack_bolt.adapter.socket_mode import SocketModeHandler

from threading import Lock
from sync_progress import sync_progress

load_dotenv(".env")

bot_token = os.getenv("SLACK_BOT_TOKEN")
app_token = os.getenv("SLACK_APP_TOKEN")
report_channel = os.getenv("SLACK_REPORT_CHANNEL_ID")
goal_channel = os.getenv("SLACK_STUDY_GOAL_CHANNEL_ID")

if not all([bot_token, app_token, report_channel, goal_channel]):
    raise SystemExit(".env のSlackトークンとチャンネルIDを確認してください。")

app = App(token=bot_token)
# 連続で投稿があっても同時に処理しない
sync_lock = Lock()

# メッセージのイベントが届くと、この関数が実行される
@app.event("message")
def handle_message(event):
    channel_id = event.get("channel")

    # 日報・目標チャンネルだけを対象にする
    if channel_id not in (report_channel, goal_channel):
        return

    # 今回は通常の新規投稿だけを確認する
    if event.get("subtype") or event.get("bot_id"):
        return

    if channel_id == report_channel:
        print("日報チャンネルの投稿を受信しました！", flush=True)
    else:
        print("目標チャンネルの投稿を受信しました！", flush=True)

    # 更新処理を１回ずつ順番に実行する
    with sync_lock:
        sync_progress()

if __name__ == "__main__":
    handler = SocketModeHandler(app, app_token)
    handler.start()
