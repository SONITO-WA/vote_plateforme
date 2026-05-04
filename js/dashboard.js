/**
 * dashboard.js — VoxENSIASD
 * Graphiques Chart.js et rafraîchissement temps réel.
 */

document.addEventListener('DOMContentLoaded', () => {

    // === Configuration Chart.js globale ===
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#6b6157';
    }

    // === Initialisation des graphiques par type ===
    document.querySelectorAll('canvas[data-chart]').forEach(canvas => {
        const type = canvas.dataset.chart;
        try {
            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const values = JSON.parse(canvas.dataset.values || '[]');
            renderChart(canvas, type, labels, values);
        } catch (err) {
            console.error('Chart error:', err);
        }
    });

    // === Rafraîchissement temps réel des résultats (AJAX) ===
    const liveResults = document.querySelector('[data-live-election]');
    if (liveResults) {
        const electionId = liveResults.dataset.liveElection;
        if (electionId && electionId !== '') {
            setInterval(() => refreshResults(electionId), 5000);
        }
    }

    // === Confirmation suppression ===
    document.querySelectorAll('[data-confirm]').forEach(link => {
        link.addEventListener('click', e => {
            const msg = link.dataset.confirm;
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // === Soumission vote — confirmation ===
    const voteForm = document.querySelector('#vote-form');
    if (voteForm) {
        voteForm.addEventListener('submit', e => {
            const checked = voteForm.querySelector('input[name="candidate_id"]:checked');
            if (!checked) {
                e.preventDefault();
                alert('Veuillez sélectionner un candidat avant de voter.');
                return;
            }
            if (!confirm('Confirmer votre vote ?\n\nUne fois validé, votre choix est définitif et ne pourra plus être modifié.')) {
                e.preventDefault();
            }
        });
    }
});

/**
 * Crée un graphique Chart.js avec un style cohérent.
 */
function renderChart(canvas, type, labels, values) {
    const palette = ['#c89b3c', '#7a2e2e', '#2d4a3e', '#8b6914', '#6b6157', '#d4a574'];

    const config = {
        type: type === 'doughnut' ? 'doughnut' : 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Votes',
                data: values,
                backgroundColor: type === 'doughnut'
                    ? palette
                    : palette[0],
                borderColor: type === 'doughnut' ? '#f5f1e8' : 'transparent',
                borderWidth: type === 'doughnut' ? 3 : 0,
                borderRadius: type === 'bar' ? 0 : 0,
                barThickness: type === 'bar' ? 36 : undefined
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: type === 'doughnut',
                    position: 'right',
                    labels: { padding: 16, font: { size: 13 } }
                },
                tooltip: {
                    backgroundColor: '#0d1117',
                    titleColor: '#d4a574',
                    bodyColor: '#f5f1e8',
                    padding: 12,
                    titleFont: { family: "'Fraunces', serif", size: 14 },
                    bodyFont: { family: "'Inter', sans-serif", size: 13 },
                    borderColor: '#c89b3c',
                    borderWidth: 1,
                    cornerRadius: 0
                }
            },
            scales: type === 'bar' ? {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: '#6b6157' },
                    grid: { color: 'rgba(13,17,23,0.05)' }
                },
                x: {
                    ticks: { color: '#6b6157' },
                    grid: { display: false }
                }
            } : {}
        }
    };

    new Chart(canvas, config);
}

/**
 * AJAX : rafraîchit les données de résultats sans recharger la page.
 */
async function refreshResults(electionId) {
    try {
        const url = window.BASE_URL + '/pages/api_results.php?election_id=' + encodeURIComponent(electionId);
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) return;
        const data = await res.json();

        // Mettre à jour le tableau
        const tbody = document.querySelector('#live-results-tbody');
        if (tbody && data.results) {
            const total = data.total_votes || 0;
            tbody.innerHTML = data.results.map((r, i) => {
                const pct = total > 0 ? (r.votes / total * 100).toFixed(1) : 0;
                const winnerClass = i === 0 && r.votes > 0 ? ' class="winner-row"' : '';
                return `
                    <tr${winnerClass}>
                        <td><span class="rank rank-${i + 1}">${i + 1}</span></td>
                        <td><strong>${escapeHtml(r.name)}</strong></td>
                        <td><strong>${r.votes}</strong></td>
                        <td>
                            <div class="bar-cell">
                                <div class="bar"><span style="width:${pct}%"></span></div>
                                <small>${pct}%</small>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Mettre à jour les compteurs
        const totalEl = document.querySelector('#stat-total');
        if (totalEl) totalEl.textContent = data.total_votes;
        const turnoutEl = document.querySelector('#stat-turnout');
        if (turnoutEl) turnoutEl.textContent = (data.turnout || 0) + '%';

        // Mettre à jour le graphique (cherche d'abord data-live-chart, sinon #live-chart)
        const canvas = document.querySelector('canvas[data-live-chart]')
                    || document.getElementById('live-chart');
        if (canvas && data.results) {
            const chart = Chart.getChart(canvas);
            if (chart) {
                chart.data.labels = data.results.map(r => r.name);
                chart.data.datasets[0].data = data.results.map(r => r.votes);
                chart.update('none');
            }
        }
    } catch (err) {
        console.error('Refresh error:', err);
    }
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}
