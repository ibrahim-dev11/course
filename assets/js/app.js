
'use strict';

/**
 * فۆرمی ناو
 */
async function submitName(e) {
    e.preventDefault();
    const input = document.getElementById('nameInput');
    const name  = input ? input.value.trim() : '';
    if (!name || name.length < 2) {
        showToast('تکایە ناوێکی دروست بنووسە', 'warning');
        return;
    }
    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }

    try {
        const res  = await fetch('save_name.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name }),
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'کێشەیەک ڕووی دا', 'error');
            if (btn) { btn.disabled = false; btn.style.opacity = ''; }
        }
    } catch {
        showToast('کێشەی تۆڕ ڕووی دا', 'error');
        if (btn) { btn.disabled = false; btn.style.opacity = ''; }
    }
}

/**
 * دەنگدان بۆ هەڵبژاردنێک — سەرەتا مۆدالی تێبینی دەردەخات
 */
let _pendingPollId   = null;
let _pendingOptionId = null;
let _pendingIsYes    = false;

function castVote(cardEl) {
    const pollId   = parseInt(cardEl.dataset.pollId);
    const optionId = parseInt(cardEl.dataset.optionId);
    if (!pollId || !optionId) return;

    _pendingPollId   = pollId;
    _pendingOptionId = optionId;
    _pendingIsYes    = cardEl.classList.contains('option-yes');

    // نوێکردنەوەی مۆدال
    document.getElementById('commentModalIcon').textContent  = _pendingIsYes ? '🔥' : '🙅';
    document.getElementById('commentModalTitle').textContent = _pendingIsYes ? 'بەڵێ هەڵبژاردیت' : 'نەخێر هەڵبژاردیت';
    document.getElementById('commentInput').value = '';
    document.getElementById('commentOverlay').classList.add('show');
}

async function submitVoteWithComment(withComment) {
    const comment = withComment ? document.getElementById('commentInput').value.trim() : '';
    document.getElementById('commentOverlay').classList.remove('show');

    showLoader();
    const grid  = document.getElementById('optionsGrid');
    const cards = grid ? grid.querySelectorAll('.option-card') : [];
    cards.forEach(c => c.style.pointerEvents = 'none');

    try {
        const response = await fetch('vote.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ poll_id: _pendingPollId, option_id: _pendingOptionId, comment }),
        });

        const data = await response.json();
        hideLoader();

        if (data.success) {
            updateVoteUI(data, _pendingOptionId);
            showToast(data.message, 'success');
        } else if (data.already_voted) {
            showToast('پێشتر دەنگت داوە! 😅', 'warning');
        } else {
            showToast(data.message || 'کێشەیەک ڕووی دا', 'error');
            cards.forEach(c => c.style.pointerEvents = '');
        }
    } catch (err) {
        hideLoader();
        showToast('کێشەی تۆڕ ڕووی دا. دووبارە هەوڵبدەرەوە.', 'error');
        cards.forEach(c => c.style.pointerEvents = '');
    }
}

// داخستنی مۆدال بە کلیکی دەرەوە
document.addEventListener('click', e => {
    if (e.target.id === 'commentOverlay') {
        document.getElementById('commentOverlay').classList.remove('show');
    }
});

/**
 * نوێکردنەوەی UI دوای دەنگدان
 */
function updateVoteUI(data, votedOptionId) {
    const grid = document.getElementById('optionsGrid');
    if (!grid) return;

    // نوێکردنەوەی ژمارە و بار بۆ هەر کارت
    data.results.forEach(result => {
        const card = grid.querySelector(`[data-option-id="${result.id}"]`);
        if (!card) return;

        // زیادکردنی دۆخی دوای دەنگدان
        card.classList.add('voted-state');
        card.style.cursor = 'default';
        card.onclick = null;

        // نیشاندانی بارەکان
        const statsEl = card.querySelector('.option-stats');
        if (statsEl) statsEl.classList.add('show');

        // ئەنیمیشنی بار
        const bar = card.querySelector('.progress-bar');
        if (bar) {
            requestAnimationFrame(() => {
                setTimeout(() => {
                    bar.style.width = result.percentage + '%';
                }, 100);
            });
        }

        // نوێکردنەوەیژمارەکان
        const countEl = card.querySelector('.vote-count');
        if (countEl) countEl.textContent = result.vote_count.toLocaleString() + ' دەنگ';

        const pctEl = card.querySelector('.vote-percent');
        if (pctEl) pctEl.textContent = result.percentage + '%';

        // نیشاندانی دەنگی خۆی
        if (result.id == votedOptionId) {
            card.classList.add('user-voted');
            const textWrap = card.querySelector('.option-text-wrap');
            if (textWrap && !textWrap.querySelector('.your-vote-badge')) {
                const badge = document.createElement('span');
                badge.className = 'your-vote-badge';
                badge.textContent = 'دەنگەکەت ✓';
                textWrap.appendChild(badge);
            }
        }
    });

    // نوێکردنەوەی کۆی دەنگ
    const totalEl = document.getElementById('totalVotes');
    if (totalEl) {
        animateNumber(totalEl, parseInt(totalEl.textContent) || 0, data.total_votes);
    }

    // نیشاندانی پەیام
    const msgEl = document.getElementById('votedMsg');
    if (msgEl) msgEl.classList.add('show');

    // شاردنەوەی hint
    const hint = document.querySelector('.vote-hint');
    if (hint) hint.style.display = 'none';
}

/**
 * ئەنیمیشنی ژمارە
 */
function animateNumber(el, from, to) {
    const duration = 800;
    const start    = performance.now();
    const update   = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(from + (to - from) * eased).toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

/**
 * Toast پەیام
 */
function showToast(message, type = 'info') {
    // لابردنی toast کۆن
    const old = document.querySelector('.toast-popup');
    if (old) old.remove();

    const colors = {
        success: 'linear-gradient(135deg,#22c55e,#16a34a)',
        error:   'linear-gradient(135deg,#ef4444,#dc2626)',
        warning: 'linear-gradient(135deg,#f59e0b,#d97706)',
        info:    'linear-gradient(135deg,#6366f1,#8b5cf6)',
    };
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

    const toast = document.createElement('div');
    toast.className = 'toast-popup';
    toast.style.cssText = `
        position:fixed; top:1.5rem; right:50%; transform:translateX(50%);
        background:${colors[type] || colors.info};
        color:#fff; padding:0.875rem 1.5rem;
        border-radius:100px; font-family:'Noto Kufi Arabic',sans-serif;
        font-size:0.9rem; font-weight:600; direction:rtl;
        box-shadow:0 8px 30px rgba(0,0,0,0.4);
        z-index:99999; display:flex; align-items:center; gap:0.5rem;
        animation:toastIn 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards;
        white-space:nowrap; max-width:90vw; text-align:center;
    `;
    toast.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;

    if (!document.querySelector('#toast-style')) {
        const s = document.createElement('style');
        s.id = 'toast-style';
        s.textContent = `
            @keyframes toastIn  { from { opacity:0; transform:translateX(50%) translateY(-20px) scale(0.9); } to { opacity:1; transform:translateX(50%) translateY(0) scale(1); } }
            @keyframes toastOut { from { opacity:1; transform:translateX(50%) scale(1); } to { opacity:0; transform:translateX(50%) scale(0.85); } }
        `;
        document.head.appendChild(s);
    }

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 320);
    }, 3500);
}

function showLoader() {
    const el = document.getElementById('loaderOverlay');
    if (el) el.classList.add('show');
}

function hideLoader() {
    const el = document.getElementById('loaderOverlay');
    if (el) el.classList.remove('show');
}

// ئەنیمیشنی بارەکانی نێتیجە لە results.php
document.addEventListener('DOMContentLoaded', () => {
    // بارەکانی results.php
    const fills = document.querySelectorAll('.result-fill');
    if (fills.length > 0) {
        setTimeout(() => {
            fills.forEach(el => {
                el.style.width = el.dataset.width || '0%';
            });
        }, 400);
    }

    // بارەکانی index.php (کاتێک پێشتر دەنگ داوە)
    const bars = document.querySelectorAll('.progress-bar[data-width]');
    if (bars.length > 0) {
        setTimeout(() => {
            bars.forEach(el => {
                el.style.transition = 'width 1s cubic-bezier(0.4,0,0.2,1)';
                el.style.width = el.dataset.width + '%';
            });
        }, 300);
    }
});
