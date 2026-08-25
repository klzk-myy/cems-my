#!/usr/bin/env python3
"""Render docs/page-design-guide.md to a standalone styled HTML file for visual review.

Self-contained: no external markdown libraries. Handles headings, tables,
bold/italic/inline-code, code fences, lists, blockquotes, and rules — the
constructs used by the design guide.
"""
import re
import sys

SRC = "docs/page-design-guide.md"
OUT = "/tmp/guide-render.html"


def esc(text: str) -> str:
    """Escape raw source text so markdown can never leak HTML into the output."""
    return (
        text.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
    )


def inline(text: str) -> str:
    """Convert inline markdown constructs to HTML."""
    text = esc(text)
    # Shield code spans first with collision-free tokens; they are rendered
    # directly to <code> and restored last so no other rule can touch them.
    tokens = {}
    counter = [0]

    def _shield(m):
        key = f"\u27e6c{counter[0]}\u27e7"
        counter[0] += 1
        tokens[key] = f"<code>{m.group(1)}</code>"
        return key

    text = re.sub(r"`([^`]+)`", _shield, text)
    # Bold — lazy so a literal * inside bold (e.g. "**use * (asterisk)**") is kept.
    text = re.sub(r"\*\*(.+?)\*\*", lambda m: f"<strong>{m.group(1)}</strong>", text)
    # Emphasis — only pairs of single * without ** adjacency.
    text = re.sub(r"(?<![*\w])\*([^*\n]+?)\*(?![*\w])", r"<em>\1</em>", text)
    text = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r'<a href="\2">\1</a>', text)
    for key, val in tokens.items():
        text = text.replace(key, val)
    return text


def split_cells(line: str) -> list[str]:
    """Split a pipe-table row on unescaped | characters (respects \\|)."""
    cells = []
    cur = []
    s = line.strip().strip("|")
    i = 0
    while i < len(s):
        if s[i] == "\\" and i + 1 < len(s) and s[i + 1] == "|":
            cur.append("|")
            i += 2
        elif s[i] == "|":
            cells.append("".join(cur).strip())
            cur = []
            i += 1
        else:
            cur.append(s[i])
            i += 1
    cells.append("".join(cur).strip())
    return cells


def parse_table(lines, i):
    """Parse a markdown pipe table starting at line i. Returns (html, next_index)."""
    header_cells = split_cells(lines[i])
    i += 1
    # separator row
    if i < len(lines) and re.match(r"^\s*\|[\s:|-]+\|\s*$", lines[i]):
        i += 1
    rows = []
    while i < len(lines) and lines[i].strip().startswith("|") and "|" in lines[i]:
        cells = split_cells(lines[i])
        rows.append(cells)
        i += 1
    html = ["<div class='table-wrap'><table>"]
    html.append("<thead><tr>" + "".join(f"<th>{inline(h)}</th>" for h in header_cells) + "</tr></thead>")
    if rows:
        html.append("<tbody>")
        for row in rows:
            cells = row + [""] * (len(header_cells) - len(row))
            html.append("<tr>" + "".join(f"<td>{inline(c)}</td>" for c in cells[:len(header_cells)]) + "</tr>")
        html.append("</tbody>")
    html.append("</table></div>")
    return "".join(html), i


def render(md: str) -> str:
    lines = md.split("\n")
    out = []
    i = 0
    n = len(lines)
    in_fence = False
    fence_lang = ""
    fence_buf = []

    while i < n:
        line = lines[i]

        # code fences
        if line.startswith("```"):
            if not in_fence:
                in_fence = True
                fence_lang = line[3:].strip()
                fence_buf = []
            else:
                in_fence = False
                lang = f' class="language-{fence_lang}"' if fence_lang else ""
                code = esc("\n".join(fence_buf))
                out.append(f"<pre><code{lang}>{code}</code></pre>")
            i += 1
            continue

        if in_fence:
            fence_buf.append(line)
            i += 1
            continue

        stripped = line.strip()

        # blank line
        if not stripped:
            i += 1
            continue

        # table
        if stripped.startswith("|") and "|" in stripped and i + 1 < n and re.match(r"^\s*\|[\s:|-]+\|\s*$", lines[i + 1]):
            html, i = parse_table(lines, i)
            out.append(html)
            continue

        # headings
        m = re.match(r"^(#{1,6})\s+(.*)$", line)
        if m:
            level = len(m.group(1))
            text = inline(m.group(2))
            # anchor-friendly id
            anchor = re.sub(r"[^a-z0-9]+", "-", m.group(2).lower()).strip("-")
            out.append(f"<h{level} id='{anchor}'>{text}</h{level}>")
            i += 1
            continue

        # horizontal rule
        if re.match(r"^\s*(-{3,}|\*{3,}|_{3,})\s*$", line):
            out.append("<hr>")
            i += 1
            continue

        # blockquote
        if stripped.startswith(">"):
            buf = []
            while i < n and lines[i].strip().startswith(">"):
                buf.append(re.sub(r"^\s*>\s?", "", lines[i]))
                i += 1
            out.append("<blockquote>" + inline(" ".join(buf)) + "</blockquote>")
            continue

        # unordered list
        if re.match(r"^\s*[-*+]\s+", line):
            buf = []
            while i < n and re.match(r"^\s*[-*+]\s+", lines[i]):
                buf.append(inline(re.sub(r"^\s*[-*+]\s+", "", lines[i])))
                i += 1
            out.append("<ul>" + "".join(f"<li>{b}</li>" for b in buf) + "</ul>")
            continue

        # ordered list
        if re.match(r"^\s*\d+\.\s+", line):
            buf = []
            while i < n and re.match(r"^\s*\d+\.\s+", lines[i]):
                buf.append(inline(re.sub(r"^\s*\d+\.\s+", "", lines[i])))
                i += 1
            out.append("<ol>" + "".join(f"<li>{b}</li>" for b in buf) + "</ol>")
            continue

        # paragraph (consume consecutive non-empty, non-special lines)
        buf = [inline(line)]
        i += 1
        while i < n:
            l = lines[i]
            s = l.strip()
            if (
                not s
                or s.startswith("|")
                or s.startswith("```")
                or re.match(r"^(#{1,6})\s+", l)
                or re.match(r"^\s*[-*+]\s+", l)
                or re.match(r"^\s*\d+\.\s+", l)
                or s.startswith(">")
                or re.match(r"^\s*(-{3,}|\*{3,}|_{3,})\s*$", l)
            ):
                break
            buf.append("<br>" + inline(l))
            i += 1
        out.append(f"<p>{''.join(buf)}</p>")

    return "\n".join(out)


def main() -> None:
    md = open(SRC, encoding="utf-8").read()
    body = render(md)
    title = "Page Design Guide — Visual Review"

    html = f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title}</title>
<style>
  :root {{
    --bg: #faf9f7; --surface: #ffffff; --ink: #1f2933; --muted: #6b7280;
    --line: #e5e0da; --accent: #b45309; --code-bg: #f3f0eb; --head: #111827;
  }}
  * {{ box-sizing: border-box; }}
  body {{ margin: 0; background: var(--bg); color: var(--ink);
         font: 15px/1.6 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }}
  .wrap {{ max-width: 980px; margin: 0 auto; padding: 40px 32px 80px; }}
  h1 {{ font-size: 30px; border-bottom: 2px solid var(--accent); padding-bottom: 10px; }}
  h2 {{ font-size: 22px; margin-top: 44px; border-bottom: 1px solid var(--line); padding-bottom: 6px; }}
  h3 {{ font-size: 18px; margin-top: 32px; }}
  h4 {{ font-size: 16px; margin-top: 26px; }}
  h5, h6 {{ font-size: 15px; margin-top: 22px; }}
  a {{ color: var(--accent); }}
  code {{ background: var(--code-bg); border: 1px solid var(--line); border-radius: 4px;
         padding: 1px 5px; font: 13px "SF Mono", Consolas, Menlo, monospace; }}
  pre {{ background: var(--code-bg); border: 1px solid var(--line); border-radius: 8px;
        padding: 14px 16px; overflow-x: auto; }}
  pre code {{ background: none; border: none; padding: 0; font-size: 13px; }}
  .table-wrap {{ overflow-x: auto; margin: 14px 0 22px; border: 1px solid var(--line);
                border-radius: 8px; background: var(--surface); }}
  table {{ border-collapse: collapse; width: 100%; font-size: 14px; }}
  th {{ text-align: left; background: #f5f2ed; color: var(--head); font-weight: 600;
       padding: 9px 12px; border-bottom: 1px solid var(--line); }}
  td {{ padding: 8px 12px; border-bottom: 1px solid #f0ece6; vertical-align: top; }}
  tbody tr:last-child td {{ border-bottom: none; }}
  tbody tr:hover {{ background: #fbf8f3; }}
  blockquote {{ margin: 14px 0; padding: 10px 16px; border-left: 4px solid var(--accent);
               background: #fdf8f0; border-radius: 0 8px 8px 0; color: #57534e; }}
  ul, ol {{ padding-left: 24px; }}
  hr {{ border: none; border-top: 1px solid var(--line); margin: 28px 0; }}
  .toc {{ background: var(--surface); border: 1px solid var(--line); border-radius: 8px;
         padding: 16px 20px; margin-bottom: 24px; font-size: 13px; }}
</style>
</head>
<body>
<div class="wrap">
{body}
</div>
</body>
</html>"""

    open(OUT, "w", encoding="utf-8").write(html)
    print(f"wrote {OUT} ({len(html)} bytes)")


if __name__ == "__main__":
    main()
