import os
from pathlib import Path

from dotenv import load_dotenv
from slack_sdk import WebClient
from slack_sdk.errors import SlackApiError

# .env を読み込む
env_path = ".env"
load_dotenv(env_path)

# トークンを取得する
token = os.getenv("SLACK_BOT_TOKEN")

# Slackに接続するためのクライアントを作る
client = WebClient(token=token)

try:
    # 認証情報が有効かを確認する
    result = client.auth_test()
    print("Slackへの接続に成功しました！")
    print("ワークスペース：", result["team"])

except SlackApiError as e:
    print("接続に失敗しました：", e.response["error"])
