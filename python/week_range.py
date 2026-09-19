from datetime import datetime, time, timedelta

from tzlocal import get_localzone


def get_week_range():
    # Macのタイムゾーンで現在日時を取得
    timezone = get_localzone()
    now = datetime.now(timezone)

    # 朝8時より前なら、前日の分とする
    study_date = now.date()

    if now.hour < 8:
        study_date -= timedelta(days=1)

    # その週の月曜日を求める
    monday = study_date - timedelta(days=study_date.weekday())

    # 月曜朝8時から、翌月曜朝8時まで
    start = datetime.combine(monday, time(hour=8), tzinfo=timezone)
    end = datetime.combine(
        monday + timedelta(days=7),
        time(hour=8),
        tzinfo=timezone,
    )

    return start, end


# このファイルを直接実行したときだけ表示する
if __name__ == "__main__":
    start, end = get_week_range()

    print("タイムゾーン：", start.tzinfo)
    print("集計開始：", start)
    print("集計終了（この時刻は含まない）：", end)
