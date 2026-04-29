function renderSidebar() {
    const user = getUser();
    if (!user) return;

    const isPatient      = user.role === 'patient';
    const isNutritionist = user.role === 'nutritionist';

    const patientLinks = `
        <a href="dashboard.html"><span class="icon">📊</span> Dashboard</a>
        <a href="habits.html"><span class="icon">📋</span> Habits</a>
        <a href="metrics.html"><span class="icon">⚖️</span> Body Metrics</a>
        <a href="diseases.html"><span class="icon">🩺</span> My Diseases</a>
        <a href="nutritionists.html"><span class="icon">👩‍⚕️</span> Find Nutritionists</a>
        <a href="predictions.html"><span class="icon">🔮</span> Health Insights</a>
        <a href="groups.html"><span class="icon">👥</span> Groups</a>
        <a href="friends.html"><span class="icon">🤝</span> Friends</a>
        <a href="track.html"><span class="icon">📡</span> My Tracking</a>
    `;

    const nutritionistLinks = `
        <a href="dashboard.html"><span class="icon">📊</span> Dashboard</a>
        <a href="nutritionists.html"><span class="icon">🧑‍🤝‍🧑</span> My Patients</a>
        <a href="track.html"><span class="icon">📡</span> Track Patients</a>
        <a href="predictions.html"><span class="icon">🔮</span> Predictions</a>
        <a href="payments.html"><span class="icon">💳</span> Payments</a>
    `;

    const html = `
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">Nutri<span>Phase</span></div>
        <nav class="sidebar-nav">
            ${isPatient ? patientLinks : ''}
            ${isNutritionist ? nutritionistLinks : ''}
        </nav>
        <div class="sidebar-footer">
            <div class="text-muted" style="font-size:12px;margin-bottom:8px">
                Signed in as<br><strong>${user.name}</strong>
                <span class="badge badge-green" style="margin-left:4px">${user.role}</span>
            </div>
            <button class="btn btn-outline btn-sm btn-block" data-logout>Sign Out</button>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('afterbegin', html);
    setActiveSidebarLink();

    document.querySelectorAll('[data-logout]').forEach(btn => {
        btn.addEventListener('click', logout);
    });
}

document.addEventListener('DOMContentLoaded', renderSidebar);
