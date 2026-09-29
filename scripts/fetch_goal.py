import os

from dotenv import load_dotenv
from slack_sdk import WebClient
from slack_sdk.errors import SlackApiError

load_dotenv(".env")

token = os.getenv("SLACK_BOT_TOKEN")
channel_id = os.getenv("SLACK_STUDY_GOAL_CHANNEL_ID")
client = WebClient(token=token)

# 関数の定義
def fetch_goal():
    result = client.conversations_history(
        channel=channel_id,
        limit=100,
    )

    for message in result["messages"]:
        if message.get("subtype") or message.get("bot_id"):
            continue

        text = message.get("text", "").strip()

        if text:
            return text

    return None

if __name__ == "__main__":
  try:
      goal = fetch_goal()
      print(goal)

  except SlackApiError as e:
      print("取得に失敗しました：", e.response["error"])
