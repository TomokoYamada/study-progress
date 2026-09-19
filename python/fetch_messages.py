import os
from pathlib import Path

from dotenv import load_dotenv
from slack_sdk import WebClient
from slack_sdk.errors import SlackApiError

from extract_time import calculate_total_hours
from week_range import get_week_range
# .env を読み込む
env_path = ".env"
load_dotenv(env_path)

# トークンと日報チャンネルのIDを取得する
token = os.getenv("SLACK_BOT_TOKEN")
channel_id = os.getenv("SLACK_REPORT_CHANNEL_ID")

# Slackに接続するためのクライアントを作る
client = WebClient(token=token)

def fetch_total_hours():
    start, end = get_week_range()

    result = client.conversations_history(
        channel=channel_id,
        oldest=str(start.timestamp()),
        latest=str(end.timestamp()),
        inclusive=True,
        limit=100,
    )
    messages = []
    for message in result["messages"]:
        if float(message["ts"]) < end.timestamp():
            messages.append(message)

    total_hours = calculate_total_hours(messages)
    print(f"取得した日報の合計：{total_hours}時間")
    return total_hours

if __name__ == "__main__":
    try:
        total_hours = fetch_total_hours()
        print(f"今週の日報の合計：{total_hours}時間")

    except SlackApiError as e:
        print("取得に失敗しました：", e.response["error"])

    except ValueError as e:
        print("集計を中止しました：", e)
