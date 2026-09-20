/* KomikByte client. The API remains the authority for prices, balances, and ownership. */
const app = document.getElementById('app');
const state = { user: null, authMode: 'login', purchase: null, searchTimer: null };
const pesos = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 });

const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[char]));
const money = amount => pesos.format(Number(amount || 0));
const coverClass = id => ['neon', 'sky', 'lantern'][((Number(id) - 1) % 3 + 3) % 3];
const chapterLabel = chapter => `Chapter ${Number(chapter.chapter_number).toFixed(Number(chapter.chapter_number) % 1 ? 1 : 0)}`;
const formatDate = value => new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T')));

function toast(message, type = 'success') {
  const region = document.getElementById('toast-region');
  const item = document.createElement('div');
  item.className = `toast ${type}`;
  item.textContent = message;
  region.append(item);
  setTimeout(() => item.remove(), 4300);
}

async function api(endpoint, options = {}) {
  const settings = { credentials: 'same-origin', headers: { Accept: 'application/json' }, ...options };
  if (settings.body && typeof settings.body !== 'string') {
    settings.headers['Content-Type'] = 'application/json';
    settings.body = JSON.stringify(settings.body);
  }
  let response;
  try { response = await fetch(`api/${endpoint}`, settings); }
  catch { return { success: false, message: 'Unable to reach the server. Check Apache and your connection.' }; }
  try { return await response.json(); }
  catch { return { success: false, message: 'The server returned an invalid response.' }; }
}

function updateHeader() {
  const loggedIn = Boolean(state.user);
  document.body.classList.toggle('is-authenticated', loggedIn);
  document.getElementById('login-button').style.display = loggedIn ? 'none' : '';
  document.getElementById('register-button').style.display = loggedIn ? 'none' : '';
  if (loggedIn) {
    document.getElementById('header-balance').textContent = Number(state.user.wallet_balance || 0).toFixed(2);
    document.getElementById('header-username').textContent = state.user.username;
    document.getElementById('user-initial').textContent = state.user.username.charAt(0).toUpperCase();
  }
}

async function syncSession() {
  const result = await api('session.php');
  state.user = result.authenticated ? result.user : null;
  updateHeader();
  return state.user;
}

function setNav(route) {
  const base = route.split('/')[0] || 'home';
  document.querySelectorAll('[data-nav]').forEach(link => link.classList.toggle('active', link.dataset.nav === base));
}

function loading(message = 'Loading…') {
  app.innerHTML = `<section class="loading-page"><div class="spinner"></div><p>${escapeHtml(message)}</p></section>`;
}

function comicCard(comic) {
  return `<a class="comic-card" href="#/comic/${comic.id}">
    <div class="comic-cover ${coverClass(comic.id)}"><span class="cover-number">K-BYTE ${String(comic.id).padStart(2, '0')}</span><strong class="cover-title">${escapeHtml(comic.title)}</strong></div>
    <div class="comic-info"><span class="comic-meta">${escapeHtml(comic.status)} · ${escapeHtml(comic.author)}</span><h3>${escapeHtml(comic.title)}</h3><p>${escapeHtml(comic.description)}</p>
      <div class="comic-footer"><span>${comic.chapter_count} CHAPTER${comic.chapter_count === 1 ? '' : 'S'}</span><strong>${comic.starting_price === null ? 'COMING SOON' : comic.starting_price === 0 ? 'FREE TO READ' : `FROM ${money(comic.starting_price)}`}</strong></div>
    </div></a>`;
}

async function loadComics(search = '') {
  const grid = document.getElementById('comic-grid');
  const heroContainer = document.getElementById('hero-container');
  if (!grid) return;

  grid.innerHTML = '<div class="loading-page" style="grid-column:1/-1;min-height:160px"><div class="spinner"></div></div>';
  if (heroContainer && !search) heroContainer.innerHTML = ''; 

  const result = await api(`comics.php${search ? `?search=${encodeURIComponent(search)}` : ''}`);
  if (!document.getElementById('comic-grid')) return;

  if (!result.success) { grid.innerHTML = `<p class="form-error">${escapeHtml(result.message)}</p>`; return; }
  if (!result.comics.length) {
      grid.innerHTML = '<section class="empty-state" style="grid-column:1/-1;min-height:200px"><h2>No stories found</h2></section>';
      return;
  }

  // Build the Bangan-style Hero Grid if we have at least 3 comics
  if (heroContainer && !search && result.comics.length >= 3) {
      heroContainer.innerHTML = `
        <div class="hero-grid">
            <a class="hero-card hero-main ${coverClass(result.comics[0].id)}" href="#/comic/${result.comics[0].id}">
                <span class="badge-hot">HOT</span>
                <h2 style="font-size: 32px; margin: 5px 0;">${escapeHtml(result.comics[0].title)}</h2>
                <p style="margin: 0; color: #d1d5db; font-size: 13px;">${result.comics[0].chapter_count} Chapters • 4.9 ★</p>
            </a>
            <div class="hero-side-grid">
                <a class="hero-card ${coverClass(result.comics[1].id)}" href="#/comic/${result.comics[1].id}">
                    <span class="badge-hot">HOT</span>
                    <h2 style="font-size: 20px; margin: 5px 0;">${escapeHtml(result.comics[1].title)}</h2>
                    <p style="margin: 0; color: #d1d5db; font-size: 12px;">${result.comics[1].chapter_count} Chapters • 4.5 ★</p>
                </a>
                <a class="hero-card ${coverClass(result.comics[2].id)}" href="#/comic/${result.comics[2].id}">
                    <span class="badge-hot">HOT</span>
                    <h2 style="font-size: 20px; margin: 5px 0;">${escapeHtml(result.comics[2].title)}</h2>
                    <p style="margin: 0; color: #d1d5db; font-size: 12px;">${result.comics[2].chapter_count} Chapters • 4.8 ★</p>
                </a>
            </div>
        </div>
      `;
  }

  grid.innerHTML = result.comics.map(comicCard).join('');
}

function renderHome() {
  setNav('home');
  app.innerHTML = `
    <section class="page discovery" style="padding-top: 10px; max-width: 1400px;">
      <div id="hero-container"></div>
      
      <div class="section-label" style="border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-top: 40px;">
        <h2 style="color: var(--ink); font-size: 18px;">Popular Today</h2>
      </div>
      <div id="comic-grid" class="comic-grid" style="margin-top: 20px;"></div>
    </section>
  `;
  
  // Attach search to the new header search bar
  const searchBar = document.getElementById('header-search');
  if (searchBar) {
      searchBar.addEventListener('input', event => {
        clearTimeout(state.searchTimer);
        state.searchTimer = setTimeout(() => loadComics(event.target.value.trim()), 250);
      });
  }
  
  loadComics();
}

async function renderComic(id) {
  loading('Opening comic…');
  const result = await api(`comic.php?id=${encodeURIComponent(id)}`);
  if (!result.success) return renderMissing(result.message);
  const comic = result.comic;
  setNav('home');
  app.innerHTML = `<section class="page"><a class="reader-breadcrumb" href="#/">← Discover</a><div class="comic-detail-top">
    <div class="comic-cover detail-cover ${coverClass(comic.id)}"><span class="cover-number">K-BYTE ${String(comic.id).padStart(2, '0')}</span><strong class="cover-title">${escapeHtml(comic.title)}</strong></div>
    <div class="detail-copy"><span class="status-pill">${escapeHtml(comic.status)}</span><h1>${escapeHtml(comic.title)}</h1><p class="byline">Written by <strong>${escapeHtml(comic.author)}</strong></p><p class="description">${escapeHtml(comic.description)}</p><div class="detail-stats"><div>CHAPTERS<strong>${comic.chapters.length}</strong></div><div>ACCESS<strong>${comic.chapters.filter(ch => ch.is_free).length} free</strong></div><div>FORMAT<strong>Webtoon</strong></div></div></div>
  </div><div class="chapter-section"><div class="section-label"><div><span class="eyebrow">EPISODE GUIDE</span><h2>Chapters</h2></div><span>${state.user ? 'Your unlocked chapters are marked.' : 'Log in to unlock paid chapters.'}</span></div><div class="chapter-list">${comic.chapters.map(chapterRow).join('')}</div></div></section>`;
}

function chapterRow(chapter) {
  const chapterName = escapeHtml(chapterLabel(chapter));
  
  if (chapter.can_read) {
    return `<div class="chapter-row unlocked">
      <span class="chapter-name">${chapterName}</span>
      <a class="button button-read" href="#/reader/${chapter.id}">Read</a>
    </div>`;
  } 
  
  return `<div class="chapter-row locked">
    <span class="chapter-name">${chapterName}</span>
    <button class="button button-unlock" data-purchase="${chapter.id}" data-price="${chapter.price}" data-title="${chapterName}: ${escapeHtml(chapter.title)}">
      🔒 Unlock (${money(chapter.price)})
    </button>
  </div>`;
}

async function renderReader(id) {
  loading('Opening chapter…');
  const result = await api(`chapter.php?id=${encodeURIComponent(id)}`);
  if (!result.success) return renderMissing(result.message);
  const chapter = result.chapter;
  setNav('home');
  const content = chapter.can_read
    ? `<div class="reader-body">${escapeHtml(chapter.content).split('\n').filter(Boolean).map(p => `<p>${p}</p>`).join('')}</div>`
    : `<div class="reader-body locked-reader"><div class="lock-icon">⌑</div><h2>This chapter is locked</h2><p>Unlock this episode with your simulated KomikByte wallet.<br>No real money is involved.</p><p class="locked-price">${money(chapter.price)}</p><button class="button button-primary" data-purchase="${chapter.id}" data-price="${chapter.price}" data-title="${escapeHtml(chapterLabel(chapter))}: ${escapeHtml(chapter.title)}">Unlock this chapter</button></div>`;
  app.innerHTML = `<section class="page reader"><div class="reader-breadcrumb"><a href="#/comic/${chapter.comic_id}">${escapeHtml(chapter.comic_title)}</a> <span> / ${escapeHtml(chapterLabel(chapter))}</span></div><header class="reader-head"><span class="eyebrow">${chapter.is_free ? 'FREE EPISODE' : chapter.purchased ? 'IN YOUR LIBRARY' : 'LOCKED EPISODE'}</span><h1 class="reader-title">${escapeHtml(chapter.title)}</h1></header>${content}<div class="reader-actions"><a class="button button-quiet" href="#/comic/${chapter.comic_id}">← Chapter list</a>${state.user ? '<a class="button button-secondary" href="#/library">My library</a>' : '<button class="button button-secondary" data-open-auth="login">Log in</button>'}</div></section>`;
}

function requirePageAuth() {
  if (state.user) return true;
  showAuth('login');
  location.hash = '#/';
  return false;
}

async function fetchTransactions() {
  const result = await api('transactions.php');
  return result.success ? result.transactions : [];
}

function transactionRows(transactions) {
  if (!transactions.length) return '<tr><td class="empty-table" colspan="5">No wallet activity yet. Add simulated funds to begin.</td></tr>';
  return transactions.map(tx => `<tr><td>${escapeHtml(formatDate(tx.created_at))}</td><td><span class="transaction-type">${escapeHtml(tx.type.replace('_', ' '))}</span>${tx.chapter_label ? `<span class="transaction-detail">${escapeHtml(tx.chapter_label)}</span>` : ''}</td><td class="money">${money(tx.amount)}</td><td class="money">${money(tx.balance_after)}</td><td><span class="success-status">${escapeHtml(tx.status)}</span></td></tr>`).join('');
}

async function renderWallet() {
  if (!requirePageAuth()) return;
  loading('Loading wallet…');
  const [walletResult, transactions] = await Promise.all([api('wallet.php'), fetchTransactions()]);
  if (!walletResult.success) { toast(walletResult.message, 'error'); return renderHome(); }
  state.user.wallet_balance = walletResult.wallet_balance;
  updateHeader(); setNav('wallet');
  app.innerHTML = `<section class="page"><div class="page-heading"><div><span class="eyebrow">YOUR SIMULATED WALLET</span><h1>A balance for<br>testing flows.</h1></div><p>Every change is recorded in your transaction history.</p></div><div class="wallet-layout"><section class="balance-card"><span class="eyebrow">AVAILABLE BALANCE · PHP</span><div class="balance-value">${money(walletResult.wallet_balance)}</div><p>This wallet uses simulated funds for academic purposes. No real money is involved and no external payment service is connected.</p><button class="button button-primary" data-open-topup>Add simulated funds</button></section><aside class="audit-card"><span class="eyebrow">INTEGRITY BY DESIGN</span><h2>Built for TestQua</h2><p>Wallet operations are handled by the server in database transactions.</p><ul class="integrity-list"><li><span>01</span>DECIMAL money storage</li><li><span>02</span>Atomic wallet updates</li><li><span>03</span>Complete audit trail</li><li><span>04</span>Duplicate purchase protection</li></ul></aside></div><section class="transactions-section"><div class="section-label"><div><span class="eyebrow">AUDIT TRAIL</span><h2>Recent activity</h2></div><a class="button button-secondary" href="#/history">View all activity</a></div>${transactionTable(transactions.slice(0, 5))}</section></section>`;
}

function transactionTable(transactions) {
  return `<table class="transaction-table"><thead><tr><th>DATE & TIME</th><th>ACTIVITY</th><th style="text-align:right">AMOUNT</th><th style="text-align:right">BALANCE AFTER</th><th>STATUS</th></tr></thead><tbody>${transactionRows(transactions)}</tbody></table>`;
}

async function renderHistory() {
  if (!requirePageAuth()) return;
  loading('Loading activity…'); const transactions = await fetchTransactions(); setNav('history');
  app.innerHTML = `<section class="page"><div class="page-heading"><div><span class="eyebrow">AUDIT TRAIL</span><h1>Every peso,<br>accounted for.</h1></div><p>Successful simulated top-ups and chapter purchases are retained here.</p></div>${transactionTable(transactions)}</section>`;
}

async function renderLibrary() {
  if (!requirePageAuth()) return;
  loading('Loading library…'); const result = await api('access.php'); setNav('library');
  if (!result.success) return renderMissing(result.message);
  app.innerHTML = `<section class="page"><div class="page-heading"><div><span class="eyebrow">MY COLLECTION</span><h1>Unlocked stories.</h1></div><p>Paid chapters you permanently unlocked with simulated funds.</p></div><div class="chapter-list">${result.chapters.length ? result.chapters.map(ch => `<div class="chapter-row"><span class="chapter-number">${String(ch.chapter_number).padStart(2, '0')}</span><span class="chapter-name"><small style="display:block;color:var(--muted);font-weight:500;margin-bottom:3px">${escapeHtml(ch.comic_title)}</small>${escapeHtml(ch.title)}</span><span class="chapter-tag owned">UNLOCKED</span><a class="button button-secondary" href="#/reader/${ch.id}">Read</a></div>`).join('') : '<section class="empty-state"><span class="empty-icon">◇</span><h2>Your library is waiting</h2><p>Unlock a paid chapter and it will stay here.</p><a href="#/" class="button button-primary">Browse stories</a></section>'}</div></section>`;
}

function renderMissing(message = 'That page could not be found.') {
  setNav(''); app.innerHTML = `<section class="page"><section class="empty-state"><span class="empty-icon">?</span><h2>Not quite there</h2><p>${escapeHtml(message)}</p><a href="#/" class="button button-primary">Back to Discover</a></section></section>`;
}

function currentRoute() { return location.hash.replace(/^#\/?/, '') || 'home'; }
function router() {
  const route = currentRoute();
  const [page, id] = route.split('/');
  if (page === 'home' || page === 'discover') { renderHome(); if (page === 'discover') requestAnimationFrame(() => document.getElementById('discover')?.scrollIntoView({ behavior: 'smooth' })); }
  else if (page === 'comic' && id) renderComic(id);
  else if (page === 'reader' && id) renderReader(id);
  else if (page === 'wallet') renderWallet();
  else if (page === 'history') renderHistory();
  else if (page === 'library') renderLibrary();
  else renderMissing();
  app.focus({ preventScroll: true });
}

function showAuth(mode = 'login') {
  state.authMode = mode;
  const dialog = document.getElementById('auth-dialog');
  const register = mode === 'register';
  document.querySelectorAll('[data-mode]').forEach(button => button.classList.toggle('active', button.dataset.mode === mode));
  document.getElementById('username-field').classList.toggle('hidden', !register);
  document.getElementById('email-field').classList.toggle('hidden', !register);
  document.querySelector('input[name="identity"]').closest('label').classList.toggle('hidden', register);
  document.getElementById('auth-eyebrow').textContent = register ? 'NEW READER' : 'WELCOME BACK';
  document.getElementById('auth-title').textContent = register ? 'Make room for new stories.' : 'Pick up where you left off.';
  document.getElementById('auth-description').textContent = register ? 'Create an account to build a simulated chapter collection.' : 'Log in to reach your wallet and unlocked chapters.';
  document.getElementById('auth-submit').textContent = register ? 'Create account' : 'Log in';
  document.getElementById('auth-error').textContent = '';
  document.getElementById('auth-form').reset();
  if (!dialog.open) dialog.showModal();
}

function closeDialog(id) { const dialog = document.getElementById(id); if (dialog?.open) dialog.close(); }

async function submitAuth(event) {
  event.preventDefault();
  const form = event.currentTarget, error = document.getElementById('auth-error'), submit = document.getElementById('auth-submit');
  const values = Object.fromEntries(new FormData(form));
  const register = state.authMode === 'register';
  const payload = register ? { username: values.username, email: values.email, password: values.password } : { identity: values.identity, password: values.password };
  error.textContent = ''; submit.disabled = true; submit.textContent = 'Please wait…';
  const result = await api(`${register ? 'register' : 'login'}.php`, { method: 'POST', body: payload });
  submit.disabled = false; submit.textContent = register ? 'Create account' : 'Log in';
  if (!result.success) { error.textContent = result.message; return; }
  state.user = result.user; updateHeader(); closeDialog('auth-dialog'); toast(result.message);
  if (currentRoute() === 'home') renderHome(); else router();
}

function openPurchase(button) {
  if (!state.user) { showAuth('login'); return; }
  state.purchase = { chapterId: Number(button.dataset.purchase), price: Number(button.dataset.price), title: button.dataset.title };
  document.getElementById('purchase-title').textContent = button.dataset.title;
  document.getElementById('purchase-price').textContent = money(state.purchase.price);
  document.getElementById('purchase-balance').textContent = money(state.user.wallet_balance);
  document.getElementById('purchase-error').textContent = '';
  document.getElementById('confirm-purchase').disabled = false;
  document.getElementById('purchase-dialog').showModal();
}

async function confirmPurchase() {
  if (!state.purchase) return;
  const button = document.getElementById('confirm-purchase'), error = document.getElementById('purchase-error');
  button.disabled = true; button.textContent = 'Securing transaction…'; error.textContent = '';
  const result = await api('unlock.php', { method: 'POST', body: { chapter_id: state.purchase.chapterId } });
  button.disabled = false; button.textContent = 'Confirm simulated purchase';
  if (!result.success) { error.textContent = result.message; if (result.message.includes('Authentication')) { closeDialog('purchase-dialog'); showAuth('login'); } return; }
  state.user.wallet_balance = result.balance_after; updateHeader(); closeDialog('purchase-dialog'); toast('Chapter unlocked. Your transaction has been recorded.');
  location.hash = `#/reader/${state.purchase.chapterId}`;
}

async function submitTopup(event) {
  event.preventDefault();
  const error = document.getElementById('topup-error'), button = event.currentTarget.querySelector('[type="submit"]');
  error.textContent = ''; button.disabled = true; button.textContent = 'Adding funds…';
  const result = await api('topup.php', { method: 'POST', body: { amount: document.getElementById('topup-amount').value } });
  button.disabled = false; button.textContent = 'Add simulated funds';
  if (!result.success) { error.textContent = result.message; return; }
  state.user.wallet_balance = result.balance_after; updateHeader(); closeDialog('topup-dialog'); toast(`${money(result.amount)} simulated funds added.`); router();
}

document.getElementById('login-button').addEventListener('click', () => showAuth('login'));
document.getElementById('register-button').addEventListener('click', () => showAuth('register'));
document.getElementById('close-auth').addEventListener('click', () => closeDialog('auth-dialog'));
document.getElementById('auth-form').addEventListener('submit', submitAuth);
document.getElementById('topup-form').addEventListener('submit', submitTopup);
document.getElementById('confirm-purchase').addEventListener('click', confirmPurchase);
document.getElementById('user-menu').addEventListener('click', async () => { await api('logout.php', { method: 'POST', body: {} }); state.user = null; updateHeader(); toast('You have been logged out.'); location.hash = '#/'; });
document.querySelectorAll('[data-mode]').forEach(button => button.addEventListener('click', () => showAuth(button.dataset.mode)));
document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => closeDialog(button.dataset.closeDialog)));
document.querySelectorAll('[data-amount]').forEach(button => button.addEventListener('click', () => { document.getElementById('topup-amount').value = button.dataset.amount; document.querySelectorAll('[data-amount]').forEach(item => item.classList.toggle('selected', item === button)); }));
document.addEventListener('click', event => {
  const purchaseButton = event.target.closest('[data-purchase]'); if (purchaseButton) openPurchase(purchaseButton);
  const authButton = event.target.closest('[data-open-auth]'); if (authButton) showAuth(authButton.dataset.openAuth);
  const topupButton = event.target.closest('[data-open-topup]'); if (topupButton && state.user) document.getElementById('topup-dialog').showModal();
});
window.addEventListener('hashchange', router);

(async function init() { await syncSession(); router(); })();
