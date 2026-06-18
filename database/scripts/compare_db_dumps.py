import re
import sys

def get_tables(path):
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()
    tables = {}
    for m in re.finditer(r"CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=", content, re.DOTALL):
        name = m.group(1)
        body = m.group(2)
        cols = []
        for line in body.split("\n"):
            line = line.strip().rstrip(",")
            if not line:
                continue
            if line.startswith(("PRIMARY", "KEY", "UNIQUE", "CONSTRAINT", "FULLTEXT")):
                continue
            cm = re.match(r"`([^`]+)`\s+(.+)", line)
            if cm:
                cols.append((cm.group(1), cm.group(2)))
        tables[name] = cols
    return tables


def main():
    sub_path = sys.argv[1]
    live_path = sys.argv[2]
    sub = get_tables(sub_path)
    live = get_tables(live_path)

    only_live = sorted(set(live) - set(sub))
    only_sub = sorted(set(sub) - set(live))
    common = sorted(set(sub) & set(live))

    print("=== TABLES ONLY IN LIVE (missing from sublive) ===")
    for t in only_live:
        print(t)
    print(f"Count: {len(only_live)}")

    print("\n=== TABLES ONLY IN SUBLIVE (keep as-is) ===")
    for t in only_sub:
        print(t)
    print(f"Count: {len(only_sub)}")

    print("\n=== COLUMN DIFFS ===")
    col_diffs = []
    for t in common:
        sub_cols = {c[0]: c[1] for c in sub[t]}
        live_cols = {c[0]: c[1] for c in live[t]}
        extra_live = set(live_cols) - set(sub_cols)
        extra_sub = set(sub_cols) - set(live_cols)
        if extra_live or extra_sub:
            col_diffs.append((t, extra_live, extra_sub, live_cols, sub_cols))

    for t, el, es, live_cols, sub_cols in col_diffs:
        if el:
            print(f"{t}: +live {sorted(el)}")
            for c in sorted(el):
                print(f"  live def: {c} -> {live_cols[c]}")
        if es:
            print(f"{t}: +sub only {sorted(es)}")
    print(f"Total tables with column diffs: {len(col_diffs)}")


if __name__ == "__main__":
    main()
