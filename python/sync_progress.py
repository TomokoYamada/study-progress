import os

import requests
from dotenv import load_dotenv

from slack_sdk.errors import SlackApiError

from fetch_goal import fetch_goal
from fetch_messages import fetch_total_hours

load_dotenv(".env")

# WordPressの接続情報を取得する
site_url = os.getenv("WP_SITE_URL")
username = os.getenv("WP_USERNAME")
password = os.getenv("WP_APP_PASSWORD")

# プラグインに用意した受付先
url = site_url.rstrip("/") + "/wp-json/study-progress/v1/progress"

try:
    # 関数を実行して、結果を変数に入れる
    goal = fetch_goal()
    total_hours = fetch_total_hours()

    # Wordpressに送るデータ
    data = {
        "goal": goal,
        "total_hours": total_hours,
    }

    response = requests.post(
        url,
        json=data,
        auth=(username, password),
        timeout=20,
        allow_redirects=False,
    )

    print("HTTPステータス：", response.status_code)

    # 400番台・500番台の応答ならエラーにする
    response.raise_for_status()

    if 300 <= response.status_code < 400:
        raise ValueError("転送が発生しました。サイトの正式なURLを確認してください。")

    result = response.json()

    if isinstance(result, dict) and result.get("success") is True:
        print("WordPressへの保存に成功しました！")
        print(result["data"])
    else:
        print("保存成功を確認できませんでした。")
except SlackApiError as e:
    print("Slackからの取得に失敗しました：", e.response["error"])

except requests.exceptions.JSONDecodeError:
    print("WordPressから想定したJSON形式の応答が返りませんでした。")

except requests.exceptions.RequestException as e:
    print("通信または送信に失敗しました：", e)

except ValueError as e:
    print(e)
