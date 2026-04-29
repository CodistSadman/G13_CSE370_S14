// ── API Helper ──────────────────────────────────────────
const API_BASE = '../api';

async function api(endpoint, method = 'GET', body = null) {
    const opts = {
        method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin'
    };
    if (body) opts.body = JSON.stringify(body);
    const res  = await fetch(`${API_BASE}/${endpoint}`, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
}

// ── Session ─────────────────────────────────────────────
function getUser() {
    const u = sessionStorage.getItem('nutriphase_user');
    return u ? JSON.parse(u) : null;
}
function setUser(user) {
    sessionStorage.setItem('nutriphase_user', JSON.stringify(user));
}
function clearUser() {
    sessionStorage.removeItem('nutriphase_user');
}
function requireLogin() {
    if (!getUser()) window.location.href = 'login.html';
}
function requireRole(role) {
    const user = getUser();
    if (!user || user.role !== role) window.location.href = 'dashboard.html';
}

// ── UI Helpers ───────────────────────────────────────────
function showAlert(containerId, message, type = 'success') {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => el.innerHTML = '', 4000);
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-BD', { year: 'numeric', month: 'short', day: 'numeric' });
}

function bmiCategory(bmi) {
    if (bmi < 18.5) return { label: 'Underweight', cls: 'badge-yellow' };
    if (bmi < 25)   return { label: 'Normal',      cls: 'badge-green' };
    if (bmi < 30)   return { label: 'Overweight',  cls: 'badge-yellow' };
    return                  { label: 'Obese',       cls: 'badge-red' };
}

function riskBadge(risk) {
    const map = { Low: 'badge-green', Medium: 'badge-yellow', High: 'badge-red' };
    return map[risk] || 'badge-blue';
}

// ── Sidebar active link ───────────────────────────────────
function setActiveSidebarLink() {
    const page = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
        a.classList.toggle('active', a.getAttribute('href') === page);
    });
}

// ── Logout ───────────────────────────────────────────────
async function logout() {
    try { await api('auth.php?action=logout', 'POST'); } catch (_) {}
    clearUser();
    window.location.href = 'login.html';
}

document.addEventListener('DOMContentLoaded', () => {
    setActiveSidebarLink();

    // Attach logout buttons
    document.querySelectorAll('[data-logout]').forEach(btn => {
        btn.addEventListener('click', logout);
    });

    // Show user name in sidebar if element exists
    const nameEl = document.getElementById('sidebar-username');
    if (nameEl) {
        const user = getUser();
        if (user) nameEl.textContent = user.name;
    }
});
