// Deterministic visual verification of the rendered design guide.
// Loads the served HTML, inspects the 2.5.21 index and cross-referenced
// sections, checks for raw-markdown artifacts, and saves screenshots.
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const consoleErrors = [];
  page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });

  const out = { index: {}, xref: {}, screenshots: [] };
  await page.goto('http://127.0.0.1:8973/index.html', { waitUntil: 'networkidle' });

  // ---- 2.5.21 index table ----
  const idx = await page.evaluate(() => {
    const heads = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')];
    const h = heads.find((el) => el.textContent.includes('Final Complete Checklist'));
    const results = { found: !!h, level: h ? h.tagName : null, headers: null, sampleRows: [], rowCount: 0, artifacts: {} };
    if (!h) return results;
    h.scrollIntoView({ block: 'center' });
    let table = null;
    let node = h.nextElementSibling;
    for (let i = 0; i < 6 && node; i++) {
      const t = node.querySelector ? node.querySelector('table') : null;
      if (t) { table = t; break; }
      if (node.tagName && node.tagName.match(/^H[1-6]$/)) break;
      node = node.nextElementSibling;
    }
    if (!table) return results;
    results.headers = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());
    const bodyRows = [...table.querySelectorAll('tbody tr')];
    results.rowCount = bodyRows.length;
    results.sampleRows = bodyRows.slice(0, 3).map((tr) =>
      [...tr.querySelectorAll('td')].map((td) => td.textContent.trim().replace(/\s+/g, ' '))
    );
    const fullText = table.textContent;
    results.artifacts['**'] = (fullText.match(/\*\*/g) || []).length;
    results.artifacts['`'] = (fullText.match(/`/g) || []).length;
    results.artifacts['|'] = (fullText.match(/[^|]\|[^|]/g) || []).length;
    return results;
  });
  out.index = idx;
  await page.screenshot({ path: '/tmp/guide-shot-index.png' });
  out.screenshots.push('/tmp/guide-shot-index.png');

  // ---- 2.5.18 extended constraints (C521-C620): login/settings/notifications rows ----
  const xr = await page.evaluate(() => {
    const heads = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')];
    const idxStart = heads.findIndex((el) => el.textContent.includes('Extended Constraints C521'));
    const results = { found: idxStart >= 0, level: idxStart >= 0 ? heads[idxStart].tagName : null, rows: {}, artifacts: {} };
    if (idxStart < 0) return results;
    const sectionHead = heads[idxStart];
    sectionHead.scrollIntoView({ block: 'start' });
    // Collect all tables between this heading and the next same-or-higher-level heading
    const tables = [];
    let node = sectionHead.nextElementSibling;
    let stop = false;
    while (node && !stop) {
      const tag = node.tagName;
      if (tag && /^H[1-6]$/.test(tag)) {
        const lvl = parseInt(tag[1], 10);
        const cur = parseInt(sectionHead.tagName[1], 10);
        if (lvl <= cur) stop = true;
      }
      if (!stop && node.querySelector) {
        const t = node.querySelector('table');
        if (t) tables.push(t);
      }
      node = node.nextElementSibling;
    }
    results.tableCount = tables.length;
    const wanted = ['C578', 'C579', 'C580', 'C584', 'C585', 'C586', 'C587', 'C591', 'C592', 'C594', 'C595'];
    for (const t of tables) {
      for (const row of t.querySelectorAll('tbody tr')) {
        const tds = [...row.querySelectorAll('td')];
        const first = tds[0] ? tds[0].textContent.trim() : '';
        if (wanted.includes(first)) {
          results.rows[first] = tds.map((td) => td.textContent.trim().replace(/\s+/g, ' '));
        }
      }
      const allText = t.textContent;
      results.artifacts['**'] = (results.artifacts['**'] || 0) + (allText.match(/\*\*/g) || []).length;
      results.artifacts['`'] = (results.artifacts['`'] || 0) + (allText.match(/`/g) || []).length;
    }
    return results;
  });
  out.xref = xr;
  await page.screenshot({ path: '/tmp/guide-shot-xref.png' });
  out.screenshots.push('/tmp/guide-shot-xref.png');

  out.consoleErrors = consoleErrors;
  console.log(JSON.stringify(out, null, 2));
  await browser.close();
})();
