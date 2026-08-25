#!/usr/bin/env python3
"""Verify every route() name referenced in the guide exists in the app's route table."""
import json
import re
import subprocess

guide = open("docs/page-design-guide.md", encoding="utf-8").read()

# Extract route names (with or without params)
guide_routes = set(re.findall(r"route\('([a-z0-9._-]+)'[^)]*\)", guide))

# Authoritative route list from the framework
out = subprocess.run(
    ["php", "artisan", "route:list", "--json"],
    capture_output=True,
    text=True,
    check=True,
).stdout
defined = {r.get("name") for r in json.loads(out) if r.get("name")}

print(f"guide route() references ({len(guide_routes)}):")
for r in sorted(guide_routes):
    print(f"  {r}  ->  {'OK' if r in defined else 'MISSING'}")
print()
missing = sorted(guide_routes - defined)
if missing:
    print("MISSING:", missing)
else:
    print("ALL route() references resolve to real routes ✓")
