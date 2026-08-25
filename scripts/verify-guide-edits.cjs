// Verify the route-example sections (3.1, 3.4, 3.7, 8.4, 8.5, 9.6) render with
// real transaction routes and aligned labels, and contain no leftover tickets.*/pawn refs.
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8973/index.html', { waitUntil: 'networkidle' });

  const result = await page.evaluate(() => {
    const all = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')];
    const heads = ['3.1 Button', '3.4 Stat Card', '3.7', '8.4 Alpine', '8.5 Alpine', '9.6 Alpine'];
    const sections = {};
    for (const h of all) {
      for (const key of heads) {
        if (!sections[key] && h.textContent.includes(key)) sections[key] = h.tagName;
      }
    }
    const bodyText = document.body.textContent;
    const checks = {
      leftover_tickets_route: /route\('tickets|tickets\./.test(bodyText),
      leftover_pawn_url: /\/pawn\//.test(bodyText),
      leftover_dashboard_stats: /dashboard\.stats/.test(bodyText),
      leftover_ticketId: /ticketId/.test(bodyText),
      has_transactions_approve: bodyText.includes("route('transactions.approve'"),
      has_transactions_cancel_store: bodyText.includes("route('transactions.cancel.store'"),
      has_transactions_store: bodyText.includes("route('transactions.store'"),
      has_transactions_show: bodyText.includes("route('transactions.show'"),
      has_transactions_create: bodyText.includes("route('transactions.create'"),
      has_route_dashboard: bodyText.includes("route('dashboard'"),
      label_Approve: />Approve</.test(bodyText.replace(/\s+/g, ' ')) || bodyText.includes('Approve'),
      label_CancelTransaction: bodyText.includes('Cancel Transaction'),
      label_ConfirmCancellation: bodyText.includes('Confirm Cancellation'),
      label_CreateTransaction: bodyText.includes('Create Transaction'),
      label_View_link: bodyText.includes(">View</a>"),
    };
    return { sections, checks };
  });

  console.log(JSON.stringify(result, null, 2));
  await page.screenshot({ path: '/tmp/guide-shot-edits.png' });
  await browser.close();
})();
