#!/usr/bin/env python3
"""
Private WordPress.org tracker for ecom-email-health-check.

Reads PUBLIC WordPress.org API data (installs, downloads, ratings, support
thread counts and our search position for a few phrases) and appends one row to
a local CSV in the git-ignored folder .tracker/ of this checkout. The repository
is public, so the numbers are never committed (see .gitignore), posted, or
logged anywhere else.

Usage:
    bin/wporg-tracker.py            record one row (weekly, from cron or a systemd timer)
    bin/wporg-tracker.py --report   print the latest row and the change since the previous one
    bin/wporg-tracker.py --dry-run  fetch and print without writing anything

Settings (environment variables):
    ECEHC_TRACKER_FILE   data file, default <this checkout>/.tracker/stats.csv
    ECEHC_TRACKER_QUERIES  ';'-separated search phrases, replaces the default list

Only the Python standard library is used. No credentials are needed or stored.
"""

import argparse
import csv
import json
import os
import sys
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone

SLUG = "ecom-email-health-check"
API = "https://api.wordpress.org/plugins/info/1.2/"
DEFAULT_QUERIES = [
    "email health check",
    "email log",
    "wp mail log",
    "woocommerce email log",
    "woocommerce email not sending",
    "woocommerce order emails",
    "spf dkim dmarc",
    "check email deliverability",
    "email failure alert",
]
TOP = 60  # how many results to look through for each phrase
FIELDS = [
    "date_utc",
    "version",
    "active_installs",
    "downloads",
    "rating",
    "num_ratings",
    "support_threads",
    "support_threads_resolved",
    "positions",
]
METRICS = [f for f in FIELDS if f not in ("date_utc", "version", "positions")]


def data_file():
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    default = os.path.join(root, ".tracker", "stats.csv")
    return os.path.expanduser(os.environ.get("ECEHC_TRACKER_FILE", default))


def queries():
    raw = os.environ.get("ECEHC_TRACKER_QUERIES")
    return [q.strip() for q in raw.split(";") if q.strip()] if raw else DEFAULT_QUERIES


def get_json(params):
    url = API + "?" + urllib.parse.urlencode(params)
    request = urllib.request.Request(url, headers={"User-Agent": "ecehc-wporg-tracker (private, weekly)"})
    with urllib.request.urlopen(request, timeout=30) as response:
        return json.load(response)


def plugin_stats():
    params = {
        "action": "plugin_information",
        "request[slug]": SLUG,
        "request[fields][active_installs]": 1,
        "request[fields][downloaded]": 1,
        "request[fields][ratings]": 1,
        "request[fields][support_threads]": 1,
        "request[fields][support_threads_resolved]": 1,
    }
    data = get_json(params)
    if "version" not in data:
        raise RuntimeError("Unexpected answer from the WordPress.org API for the plugin.")
    return {
        "version": data.get("version", ""),
        "active_installs": data.get("active_installs", ""),
        "downloads": data.get("downloaded", ""),
        "rating": data.get("rating", ""),
        "num_ratings": data.get("num_ratings", ""),
        "support_threads": data.get("support_threads", ""),
        "support_threads_resolved": data.get("support_threads_resolved", ""),
    }


def position(phrase):
    params = {
        "action": "query_plugins",
        "request[search]": phrase,
        "request[per_page]": TOP,
    }
    plugins = get_json(params).get("plugins", [])
    for index, plugin in enumerate(plugins, start=1):
        if plugin.get("slug") == SLUG:
            return index
    return None  # not within the first TOP results


def collect():
    row = {"date_utc": datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M")}
    row.update(plugin_stats())
    positions = {}
    for phrase in queries():
        time.sleep(1)  # be polite to the API
        positions[phrase] = position(phrase)
    row["positions"] = json.dumps(positions, sort_keys=True)
    return row


def read_rows(path):
    if not os.path.exists(path):
        return []
    with open(path, newline="", encoding="utf-8") as handle:
        return list(csv.DictReader(handle))


def append_row(path, row):
    os.makedirs(os.path.dirname(path), mode=0o700, exist_ok=True)
    new_file = not os.path.exists(path)
    with open(path, "a", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=FIELDS)
        if new_file:
            writer.writeheader()
        writer.writerow(row)
    os.chmod(path, 0o600)  # private: only this user can read it


def describe_position(value):
    return "-" if value in (None, "", "null") else "#" + str(value)


def describe_change(old, new):
    try:
        delta = float(new) - float(old)
    except (TypeError, ValueError):
        return ""
    return "" if delta == 0 else " (%+g)" % delta


def print_row(row, previous=None):
    print("Date (UTC): %s | version %s" % (row["date_utc"], row["version"]))
    for metric in METRICS:
        change = describe_change(previous.get(metric), row.get(metric)) if previous else ""
        print("  %-26s %s%s" % (metric, row.get(metric, ""), change))
    print("  Search positions (first %d results; - means not found):" % TOP)
    current = json.loads(row.get("positions") or "{}")
    before = json.loads(previous.get("positions") or "{}") if previous else {}
    for phrase, value in current.items():
        note = ""
        if previous and phrase in before and before[phrase] != value:
            note = "   (was %s)" % describe_position(before[phrase])
        print("    %-34s %s%s" % (phrase, describe_position(value), note))


def main():
    parser = argparse.ArgumentParser(description="Private WordPress.org tracker (local data only).")
    parser.add_argument("--report", action="store_true", help="print the latest row and the change since the previous one")
    parser.add_argument("--dry-run", action="store_true", help="fetch and print without writing")
    args = parser.parse_args()
    path = data_file()

    if args.report:
        rows = read_rows(path)
        if not rows:
            print("No data yet in %s. Run the script without options first." % path)
            return 1
        print_row(rows[-1], rows[-2] if len(rows) > 1 else None)
        print("\n%d row(s) in %s" % (len(rows), path))
        return 0

    try:
        row = collect()
    except Exception as error:  # network or API problems: write nothing
        print("Could not fetch the data, nothing was written: %s" % error, file=sys.stderr)
        return 1

    previous = (read_rows(path) or [None])[-1]
    print_row(row, previous)
    if args.dry_run:
        print("\n(dry run: nothing written)")
        return 0
    append_row(path, row)
    print("\nSaved to %s" % path)
    return 0


if __name__ == "__main__":
    sys.exit(main())
