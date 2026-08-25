#!/usr/bin/env python3
"""Locate pawn/ticket domain terms and forfeit state vars in the guide."""
import re

lines = open("docs/page-design-guide.md", encoding="utf-8").read().split("\n")

terms = ["showForfeit", "forfeitId", "ticket", "pawn", "Ticket", "Pawn",
         "Forfeit", "forfeit", "Redeem", "redeem", "TK-", "TK-2026"]

for kw in terms:
    hits = [i for i, l in enumerate(lines, 1) if kw in l]
    if hits:
        print(f"{kw!r}: {len(hits)} line(s) -> {hits[:40]}")
        for h in hits[:6]:
            print(f"    {h}: {lines[h-1].strip()[:110]}")
        print()
