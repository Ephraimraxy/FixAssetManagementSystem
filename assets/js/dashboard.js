// Dashboard charts and icon animation initialisation

document.addEventListener('DOMContentLoaded', () => {
  // Asset Status Doughnut Chart
  const assetCtx = document.getElementById('assetStatusChart');
  if (assetCtx) {
    new Chart(assetCtx, {
      type: 'doughnut',
      data: {
        labels: ['In Use', 'In Repair', 'Disposed'],
        datasets: [{
          data: [60, 25, 15], // Placeholder data – replace with AJAX later
          backgroundColor: ['#2563eb', '#d97706', '#dc2626'],
        }],
      },
      options: {
        plugins: {
          legend: { position: 'bottom' },
        },
      },
    });
  }

  // Recent Activity Bar Chart
  const activityCtx = document.getElementById('recentActivityChart');
  if (activityCtx) {
    const now = new Date();
    const labels = [...Array(7)].map((_, i) => {
      const d = new Date(now.getTime() - (6 - i) * 24 * 60 * 60 * 1000);
      return `${d.getMonth()+1}/${d.getDate()}`;
    });
    new Chart(activityCtx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Assets Added',
          data: labels.map(() => Math.floor(Math.random() * 10) + 1),
          backgroundColor: '#16a34a',
        }],
      },
      options: {
        plugins: {
          legend: { display: false },
        },
        scales: {
          y: { beginAtZero: true, suggestedMax: 12 },
        },
      },
    });
  }
});
