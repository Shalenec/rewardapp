// RewardKe - Main JS

document.addEventListener('DOMContentLoaded', function () {

    // ===== HAMBURGER MENU =====
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function () {
            navMenu.classList.toggle('open');
        });
    }

    // ===== USER DROPDOWN =====
    const userDropBtn = document.getElementById('userDropBtn');
    const userDropMenu = document.getElementById('userDropMenu');
    if (userDropBtn && userDropMenu) {
        userDropBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropMenu.classList.toggle('show');
        });
        document.addEventListener('click', function () {
            userDropMenu.classList.remove('show');
        });
    }

    // ===== FLOATING ACTION BUTTON =====
    const fabMain = document.getElementById('fabMain');
    const fabOptions = document.getElementById('fabOptions');
    if (fabMain && fabOptions) {
        fabMain.addEventListener('click', function () {
            fabMain.classList.toggle('open');
            fabOptions.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!fabMain.contains(e.target) && !fabOptions.contains(e.target)) {
                fabMain.classList.remove('open');
                fabOptions.classList.remove('open');
            }
        });

        // Animate FAB items sequentially when opened
        fabOptions.addEventListener('transitionend', function () {
            if (fabOptions.classList.contains('open')) {
                const items = fabOptions.querySelectorAll('.fab-item');
                items.forEach((item, i) => {
                    item.style.transitionDelay = (i * 0.06) + 's';
                });
            }
        });
    }

    // ===== TABS =====
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-pane').forEach(function (pane) {
                pane.classList.remove('active');
            });
            const pane = document.getElementById('tab-' + target);
            if (pane) pane.classList.add('active');
        });
    });

    // ===== AUTO-OPEN TAB FROM URL =====
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const tabBtn = document.querySelector('.tab-btn[data-tab="' + tabParam + '"]');
        if (tabBtn) tabBtn.click();
    }

    // ===== COPY REFERRAL CODE =====
    const copyBtns = document.querySelectorAll('.referral-copy-btn, .copy-btn');
    copyBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = this.dataset.copy || this.previousElementSibling?.textContent;
            if (target) {
                navigator.clipboard.writeText(target.trim()).then(function () {
                    const orig = btn.textContent;
                    btn.textContent = 'Copied!';
                    btn.style.background = '#ecfdf5';
                    btn.style.color = '#065f46';
                    setTimeout(function () {
                        btn.textContent = orig;
                        btn.style.background = '';
                        btn.style.color = '';
                    }, 2000);
                }).catch(function () {
                    // Fallback
                    const el = document.createElement('textarea');
                    el.value = target.trim();
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                    btn.textContent = 'Copied!';
                    setTimeout(() => btn.textContent = 'Copy', 2000);
                });
            }
        });
    });

    // ===== AD VIDEO MODAL =====
    window.openAdModal = function (adId, adTitle, sponsor, videoUrl, reward, duration) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.id = 'adModal';
        modal.innerHTML = `
            <div class="modal-box">
                <div class="modal-header">
                    <div>
                        <div class="modal-title">${adTitle}</div>
                        <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">Sponsored by ${sponsor} &bull; Earn KES ${reward}</div>
                    </div>
                    <button class="modal-close" id="adModalClose" disabled style="opacity:0.3;cursor:not-allowed;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div class="video-container">
                        <iframe src="${videoUrl}?autoplay=1&rel=0&controls=0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                    </div>
                    <div class="countdown-text" id="countdownText">Watch for <strong id="countdownSec">${duration}</strong> seconds to earn your reward</div>
                    <div class="countdown-bar"><div class="countdown-fill" id="countdownFill" style="width:0%"></div></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-gray btn-sm" id="skipAdBtn" disabled style="opacity:0.3;cursor:not-allowed;">Skip</button>
                    <button class="btn btn-success btn-sm" id="claimRewardBtn" disabled style="opacity:0.3;cursor:not-allowed;"><i class="fas fa-gift"></i> Claim Reward</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        let elapsed = 0;
        const total = parseInt(duration);
        const fill = document.getElementById('countdownFill');
        const sec = document.getElementById('countdownSec');
        const claimBtn = document.getElementById('claimRewardBtn');
        const skipBtn = document.getElementById('skipAdBtn');
        const closeBtn = document.getElementById('adModalClose');

        const timer = setInterval(function () {
            elapsed++;
            const pct = Math.min((elapsed / total) * 100, 100);
            fill.style.width = pct + '%';
            sec.textContent = Math.max(total - elapsed, 0);

            if (elapsed >= total) {
                clearInterval(timer);
                claimBtn.disabled = false;
                claimBtn.style.opacity = '1';
                claimBtn.style.cursor = 'pointer';
                skipBtn.disabled = false;
                skipBtn.style.opacity = '1';
                skipBtn.style.cursor = 'pointer';
                closeBtn.disabled = false;
                closeBtn.style.opacity = '1';
                closeBtn.style.cursor = 'pointer';
                document.getElementById('countdownText').innerHTML = '<span style="color:#10b981;font-weight:700;"><i class="fas fa-check-circle"></i> Well done! Claim your KES ' + reward + ' reward!</span>';
            }
        }, 1000);

        claimBtn.addEventListener('click', function () {
            claimBtn.disabled = true;
            claimBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Processing...';
            fetch('ajax/claim_ad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ad_id=' + adId
            })
            .then(r => r.json())
            .then(function (data) {
                modal.remove();
                if (data.success) {
                    showToast('success', 'KES ' + reward + ' reward credited to your wallet!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('danger', data.message || 'Error claiming reward.');
                }
            })
            .catch(function () {
                showToast('danger', 'Connection error. Please try again.');
                modal.remove();
            });
        });

        const closeModal = function () {
            clearInterval(timer);
            modal.remove();
        };
        closeBtn.addEventListener('click', closeModal);
        skipBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal && elapsed >= total) closeModal();
        });
    };

    // ===== TOAST NOTIFICATION =====
    window.showToast = function (type, message) {
        const toast = document.createElement('div');
        toast.className = 'flash-alert flash-' + type;
        toast.style.cssText = 'position:fixed;top:80px;right:24px;z-index:99999;max-width:360px;animation:slideInDown 0.3s ease';
        const iconMap = { success: 'check-circle', danger: 'times-circle', warning: 'exclamation-circle', info: 'info-circle' };
        toast.innerHTML = '<i class="fas fa-' + (iconMap[type] || 'info-circle') + '"></i> ' + message;
        document.body.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // ===== AUTO DISMISS FLASH =====
    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
        setTimeout(function () {
            flashAlert.style.opacity = '0';
            flashAlert.style.transition = 'opacity 0.4s';
            setTimeout(() => flashAlert.remove(), 400);
        }, 5000);
    }

    // ===== NUMBER ANIMATION =====
    const statValues = document.querySelectorAll('.stat-value[data-value]');
    statValues.forEach(function (el) {
        const target = parseFloat(el.dataset.value);
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        let start = 0;
        const duration = 1200;
        const step = target / (duration / 16);
        const counter = setInterval(function () {
            start += step;
            if (start >= target) {
                start = target;
                clearInterval(counter);
            }
            const display = target % 1 !== 0 ? start.toFixed(2) : Math.floor(start).toLocaleString();
            el.textContent = prefix + display + suffix;
        }, 16);
    });

    // ===== INVESTMENT AMOUNT CALCULATOR =====
    const amtInput = document.getElementById('investAmount');
    const pkgSelect = document.getElementById('packageSelect');
    if (amtInput && pkgSelect) {
        function updateCalc() {
            const opt = pkgSelect.options[pkgSelect.selectedIndex];
            const amt = parseFloat(amtInput.value) || 0;
            const rate = parseFloat(opt.dataset.rate) || 0;
            const days = parseInt(opt.dataset.days) || 0;
            const daily = (amt * rate / 100);
            const total = daily * days;
            const dailyEl = document.getElementById('calcDaily');
            const totalEl = document.getElementById('calcTotal');
            const endEl = document.getElementById('calcEnd');
            if (dailyEl) dailyEl.textContent = 'KES ' + daily.toFixed(2);
            if (totalEl) totalEl.textContent = 'KES ' + total.toFixed(2);
            if (endEl) {
                const end = new Date();
                end.setDate(end.getDate() + days);
                endEl.textContent = end.toLocaleDateString('en-KE', { year: 'numeric', month: 'short', day: 'numeric' });
            }
        }
        amtInput.addEventListener('input', updateCalc);
        pkgSelect.addEventListener('change', updateCalc);
        updateCalc();
    }

    // ===== CONFIRM DIALOGS =====
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });
});
