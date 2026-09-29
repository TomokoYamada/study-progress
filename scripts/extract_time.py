import re
import unicodedata

# 日報から学習時間を抽出する関数
def extract_hours(text):
    normalized_text = unicodedata.normalize("NFKC", text)

    match = re.search(
        r"今日行ったこと[\s\-]*\(\s*(\d+(?:\.\d+)?)\s*h\s*\)",
        normalized_text,
        flags=re.IGNORECASE,
    )

    if match:
        return float(match.group(1))

    return None

# 日報から１週間の学習時間の合計を計算する関数
def calculate_total_hours(messages):
    total_hours = 0

    for message in messages:
        text = message.get("text", "")

        # 日報ではない投稿は対象外
        if "今日行ったこと" not in text:
            continue

        hours = extract_hours(text)

        # 日報なのに時間を読めなければ、集計を止める
        if hours is None:
            raise ValueError("時間を読み取れない日報があります。")

        total_hours += hours

    return total_hours
