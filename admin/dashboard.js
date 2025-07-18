// Add Font Awesome icons dynamically
if (!document.querySelector('[data-fa-i2svg]')) {
    const script = document.createElement('script');
    script.src = 'https://kit.fontawesome.com/your-kit-code.js';
    script.crossOrigin = 'anonymous';
    script.async = true;
    document.body.appendChild(script);
}

// Add charts using Chart.js
if (typeof Chart !== 'undefined') {
    // Recent Activity Chart
    const recentActivityCtx = document.querySelector('.chart');
    if (recentActivityCtx) {
        new Chart(recentActivityCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Activity',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#2563eb',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Asset Status Chart
    const assetStatusCtx = document.querySelector('.chart + .chart');
    if (assetStatusCtx) {
        new Chart(assetStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Maintenance'],
                datasets: [{
                    data: [10, 2, 3], // Default values since we can't access PHP variables here
                    backgroundColor: ['#16a34a', '#dc2626', '#d97706']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}
