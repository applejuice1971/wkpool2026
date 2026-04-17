from __future__ import annotations

from datetime import datetime
from zoneinfo import ZoneInfo
import re
import mysql.connector

DATA = {
    "Group A": [
        "June 11: Mexico vs South Africa - Estadio Azteca, Mexico City - 3pm ET",
        "June 11: South Korea vs UEFA playoff D - Estadio Akron, Guadalajara - 10pm ET",
        "June 18: UEFA playoff D vs South Africa - Mercedes-Benz Stadium, Atlanta - 12pm ET",
        "June 18: Mexico vs South Korea - Estadio Akron, Guadalajara - 9pm ET",
        "June 24: UEFA playoff D vs Mexico - Estadio Azteca, Mexico City - 9pm ET",
        "June 24: South Africa vs South Korea - Estadio BBVA, Monterrey - 9pm ET",
    ],
    "Group B": [
        "June 12: Canada vs UEFA playoff A - BMO Field, Toronto - 3pm ET",
        "June 13: Qatar vs Switzerland - Levi’s Stadium, San Francisco Bay Area - 3pm ET",
        "June 18: Switzerland vs UEFA playoff A - SoFi Stadium, Los Angeles - 3pm ET",
        "June 18: Canada vs Qatar - BC Place, Vancouver - 6pm ET",
        "June 24: Switzerland vs Canada - BC Place, Vancouver - 3pm ET",
        "June 24: UEFA playoff A vs Qatar - Lumen Field, Seattle - 3pm ET",
    ],
    "Group C": [
        "June 13: Brazil vs Morocco - MetLife Stadium, New York/New Jersey - 6pm ET",
        "June 13: Haiti vs Scotland - Gillette Stadium, Boston - 9pm ET",
        "June 19: Scotland vs Morocco - Gillette Stadium, Boston - 6pm ET",
        "June 19: Brazil vs Haiti - Lincoln Financial Field, Philadelphia - 9pm ET",
        "June 24: Scotland vs Brazil - Hard Rock Stadium, Miami - 6pm ET",
        "June 24: Morocco vs Haiti - Mercedes-Benz Stadium, Atlanta - 6pm ET",
    ],
    "Group D": [
        "June 12: USA vs Paraguay - SoFi Stadium, Los Angeles - 9pm ET",
        "June 13: Australia vs UEFA playoff C - BC Place, Vancouver - Midnight ET",
        "June 19: USA vs Australia - Lumen Field, Seattle - 3pm ET",
        "June 19: UEFA playoff C vs Paraguay - Levi’s Stadium, San Francisco Bay Area - Midnight ET",
        "June 25: UEFA playoff C vs USA - SoFi Stadium, Los Angeles - 10pm ET",
        "June 25: Paraguay vs Australia - Levi’s Stadium, San Francisco Bay Area - 10pm ET",
    ],
    "Group E": [
        "June 14: Germany vs Curacao - NRG Stadium, Houston - 1pm ET",
        "June 14: Ivory Coast vs Ecuador - Lincoln Financial Field, Philadelphia - 7pm ET",
        "June 20: Germany vs Ivory Coast - BMO Field, Toronto - 4pm ET",
        "June 20: Ecuador vs Curacao - Arrowhead Stadium, Kansas City - 8pm ET",
        "June 25: Ecuador vs Germany - MetLife Stadium, New York/New Jersey - 4pm ET",
        "June 25: Curacao vs Ivory Coast - Lincoln Financial Field, Philadelphia - 4pm ET",
    ],
    "Group F": [
        "June 14: Netherlands vs Japan - AT&T Stadium, Dallas - 4pm ET",
        "June 14: UEFA playoff B vs Tunisia - Estadio BBVA, Monterrey - 10pm ET",
        "June 20: Netherlands vs UEFA playoff B - NRG Stadium, Houston - 1pm ET",
        "June 20: Tunisia vs Japan - Estadio BBVA, Monterrey - Midnight ET",
        "June 25: Japan vs UEFA playoff B - AT&T Stadium, Dallas - 7pm ET",
        "June 25: Tunisia vs Netherlands - Arrowhead Stadium, Kansas City - 7pm ET",
    ],
    "Group G": [
        "June 15: Iran vs New Zealand - SoFi Stadium, Los Angeles - 9pm ET",
        "June 15: Belgium vs Egypt - Lumen Field, Seattle - 3pm ET",
        "June 21: Belgium vs Iran - SoFi Stadium, Los Angeles - 3pm ET",
        "June 21: New Zealand vs Egypt - BC Place, Vancouver - 9pm ET",
        "June 26: Egypt vs Iran - Lumen Field, Seattle - 11pm ET",
        "June 26: New Zealand vs Belgium - BC Place, Vancouver - 11pm ET",
    ],
    "Group H": [
        "June 15: Spain vs Cape Verde - Mercedes-Benz Stadium, Atlanta - 12pm ET",
        "June 15: Saudi Arabia vs Uruguay - Hard Rock Stadium, Miami - 6pm ET",
        "June 21: Spain vs Saudi Arabia - Mercedes-Benz Stadium, Atlanta - 12pm ET",
        "June 21: Uruguay vs Cape Verde - Hard Rock Stadium, Miami - 6pm ET",
        "June 26: Cape Verde vs Saudi Arabia - NRG Stadium, Houston - 8pm ET",
        "June 26: Uruguay vs Spain - Estadio Akron, Guadalajara - 8pm ET",
    ],
    "Group I": [
        "June 16: France vs Senegal - MetLife Stadium, New York/New Jersey - 3pm ET",
        "June 16: Inter-confederation playoff 2 vs Norway - Gillette Stadium, Boston - 6pm ET",
        "June 22: France vs Inter-confederation playoff 2 - Lincoln Financial Field, Philadelphia - 5pm ET",
        "June 22: Norway vs Senegal - MetLife Stadium, New York/New Jersey - 8pm ET",
        "June 26: Norway vs France - Gillette Stadium, Boston - 3pm ET",
        "June 26: Senegal vs Inter-confederation playoff 2 - BMO Field, Toronto - 3pm ET",
    ],
    "Group J": [
        "June 16: Argentina vs Algeria - Arrowhead Stadium - Kansas City - 9pm ET",
        "June 16: Austria vs Jordan - Levi’s Stadium, San Francisco Bay Area - Midnight ET",
        "June 22: Argentina vs Austria - AT&T Stadium, Dallas - 1pm ET",
        "June 22: Jordan vs Algeria - Levi’s Stadium, San Francisco Bay Area - 11pm ET",
        "June 27: Algeria vs Austria - Arrowhead Stadium, Kansas City - 10pm ET",
        "June 27: Jordan vs Argentina - AT&T Stadium, Dallas - 10pm ET",
    ],
    "Group K": [
        "June 17: Portugal vs Inter-confederation playoff 1 - NRG Stadium, Houston - 1pm ET",
        "June 17: Uzbekistan vs Colombia - Estadio Azteca, Mexico City - 10pm ET",
        "June 23: Portugal vs Uzbekistan - NRG Stadium, Houston - 1pm ET",
        "June 23: Colombia vs Inter-confederation playoff 1 - Estadio Akron, Guadalajara - 10pm ET",
        "June 27: Colombia vs Portugal - Hard Rock Stadium, Miami - 7:30pm ET",
        "June 27: Inter-confederation playoff 1 vs Uzbekistan - Mercedes-Benz Stadium, Atlanta - 7:30pm ET",
    ],
    "Group L": [
        "June 17: England vs Croatia - AT&T Stadium, Dallas - 4pm ET",
        "June 17: Ghana vs Panama - BMO Field, Toronto - 7pm ET",
        "June 23: England vs Ghana - Gillette Stadium, Boston - 4pm ET",
        "June 23: Panama vs Croatia - BMO Field, Toronto - 7pm ET",
        "June 27: Panama vs England - MetLife Stadium, New York/New Jersey - 5pm ET",
        "June 27: Croatia vs Ghana - Lincoln Financial Field, Philadelphia - 5pm ET",
    ],
    "Round of 32": [
        "June 28: Runner up Group A vs Runner up Group B - SoFi Stadium, Los Angeles - 3pm ET",
        "June 29: Winner Group C vs Runner up Group F - NRG Stadium, Houston - 1pm ET",
        "June 29: Winner Group E vs 3rd Group A/B/C/D/F - Gillette Stadium, Boston - 4:30pm ET",
        "June 29: Winner Group F vs Runner up Group C - Estadio BBVA, Monterrey - 9pm ET",
        "June 30: Runner up Group E vs Runner up Group I - AT&T Stadium, Dallas - 1pm ET",
        "June 30: Winner Group I vs 3rd Group C/D/F/G/H - MetLife Stadium, New York/New Jersey - 5pm ET",
        "June 30: Winner Group A vs 3rd Group C/E/F/H/I - Estadio Azteca, Mexico City - 9pm ET",
        "July 1: Winner Group L vs 3rd Group E/H/I/J/K - Mercedes-Benz Stadium, Atlanta - 12pm ET",
        "July 1: Winner Group G vs 3rd Group A/E/H/I/J - Lumen Field, Seattle - 4pm ET",
        "July 1: Winner Group D vs 3rd Group B/E/F/I/J - Levi’s Stadium, San Francisco Bay Area - 8pm ET",
        "July 2: Winner Group H vs Runner up Group J - SoFi Stadium, Los Angeles - 3pm ET",
        "July 2: Runner up Group K vs Runner up Group L - BMO Field, Toronto - 7pm ET",
        "July 2: Winner Group B vs 3rd Group E/F/G/I/J - BC Place, Vancouver - 11pm ET",
        "July 3: Runner up Group D vs Runner up Group G - AT&T Stadium, Dallas - 2pm ET",
        "July 3: Winner Group J vs Runner up Group H - Hard Rock Stadium, Miami - 6pm ET",
        "July 3: Winner Group K vs 3rd Group D/E/I/J/L - Arrowhead Stadium, Kansas City - 9:30pm ET",
    ],
    "Round of 16": [
        "July 4: Winner Match 73 vs Winner Match 75 - NRG Stadium, Houston - 1pm ET",
        "July 4: Winner Match 74 vs Winner Match 77 - Lincoln Financial Field, Philadelphia - 5pm ET",
        "July 5: Winner Match 76 vs Winner Match 78 - MetLife Stadium, New York/New Jersey - 4pm ET",
        "July 5: Winner Match 79 vs Winner Match 80 - Estadio Azteca, Mexico City - 8pm ET",
        "July 6: Winner Match 83 vs Winner Match 84 - AT&T Stadium, Dallas - 3pm ET",
        "July 6: Winner Match 81 vs Winner Match 82 - Lumen Field, Seattle - 8pm ET",
        "July 7: Winner Match 86 vs Winner Match 88 - Mercedes-Benz Stadium, Atlanta - 12pm ET",
        "July 7: Winner Match 85 vs Winner Match 87 - BC Place, Vancouver - 4pm ET",
    ],
    "Quarterfinal": [
        "July 9: Winner Match 89 vs Winner Match 90 - Gillette Stadium, Boston - 4pm ET",
        "July 10: Winner Match 93 vs Winner Match 94 - SoFi Stadium, Los Angeles - 3pm ET",
        "July 11: Winner Match 91 vs Winner Match 92 - Hard Rock Stadium, Miami - 5pm ET",
        "July 11: Winner Match 95 vs Winner Match 96 - Arrowhead Stadium, Kansas City - 9pm ET",
    ],
    "Semifinal": [
        "July 14: Winner Match 97 vs Winner Match 98 - AT&T Stadium, Dallas - 3pm ET",
        "July 15: Winner Match 99 vs Winner Match 100 - Mercedes-Benz Stadium, Atlanta - 3pm ET",
    ],
    "Third-place game": [
        "July 18: Loser Match 101 vs Loser Match 102 - Hard Rock Stadium, Miami - 5pm ET",
    ],
    "Final": [
        "July 19: Winner Match 101 vs Winner Match 102 - MetLife Stadium, New York/New Jersey - 3pm ET",
    ],
}

ET = ZoneInfo("America/New_York")
BERLIN = ZoneInfo("Europe/Berlin")
YEAR = 2026


def parse_time(value: str) -> tuple[int, int]:
    value = value.strip().replace(' ET', '')
    if value.lower() == 'midnight':
        return 0, 0
    dt = datetime.strptime(value, '%I%p') if ':' not in value else datetime.strptime(value, '%I:%M%p')
    return dt.hour, dt.minute


def parse_match(line: str, idx: int, stage: str) -> tuple[str, datetime, str, str, str | None]:
    m = re.match(r'^(June|July)\s+(\d+):\s+(.+?)\s+vs\s+(.+?)\s+-\s+(.+?)\s+-\s+(.+?)\s+-\s+(.+?) ET$', line)
    if not m:
        raise ValueError(f'Cannot parse line: {line}')
    month_name, day, home, away, venue, city, time_str = m.groups()
    month = 6 if month_name == 'June' else 7
    hour, minute = parse_time(time_str)
    local = datetime(YEAR, month, int(day), hour, minute, tzinfo=ET).astimezone(BERLIN)
    location = f"{venue} — {city}"
    return (f"{stage}-{idx:03d}", local, home, away, location)


def main() -> None:
    conn = mysql.connector.connect(
        host='127.0.0.1',
        port=3306,
        database='wkpool2026',
        user='wkpool',
        password='NewPWStrong',
    )
    cur = conn.cursor()
    cur.execute('DELETE FROM predictions')
    cur.execute('DELETE FROM matches')

    insert_sql = (
        'INSERT INTO matches (external_id, stage, match_date, home_team, away_team, status) '
        'VALUES (%s, %s, %s, %s, %s, %s)'
    )

    count = 0
    for stage, lines in DATA.items():
        for idx, line in enumerate(lines, start=1):
            external_id, local_dt, home, away, _location = parse_match(line, idx, stage)
            cur.execute(insert_sql, (
                external_id,
                stage,
                local_dt.strftime('%Y-%m-%d %H:%M:%S'),
                home,
                away,
                'scheduled',
            ))
            count += 1

    conn.commit()
    print(f'Imported {count} matches.')
    cur.close()
    conn.close()


if __name__ == '__main__':
    main()
