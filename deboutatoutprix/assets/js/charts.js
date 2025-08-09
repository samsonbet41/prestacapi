class ChartManager {
    constructor() {
        this.charts = {};
        this.defaultColors = {
            primary: '#1F3B73',
            secondary: '#F5F7FA',
            accent: '#00B8D9',
            success: '#4CAF50',
            error: '#E53935',
            warning: '#FF9800',
            info: '#2196F3'
        };
        
        this.initializeCharts();
    }

    initializeCharts() {
        document.addEventListener('DOMContentLoaded', () => {
            this.createDashboardCharts();
            this.setupChartInteractions();
        });
    }

    createDashboardCharts() {
        this.createStatsChart();
        this.createRevenueChart();
        this.createLoanStatusChart();
        this.createMonthlyChart();
        this.createUserGrowthChart();
        this.createWithdrawalChart();
    }

    async createStatsChart() {
        const canvas = document.getElementById('statsChart');
        if (!canvas) return;

        try {
            const response = await fetch('ajax/chart-data.php?type=stats');
            const data = await response.json();

            this.charts.stats = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: ['Prêts Approuvés', 'En Attente', 'Rejetés'],
                    datasets: [{
                        data: [data.approved, data.pending, data.rejected],
                        backgroundColor: [
                            this.defaultColors.success,
                            this.defaultColors.warning,
                            this.defaultColors.error
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        } catch (error) {
            console.error('Erreur création graphique stats:', error);
            this.showChartError(canvas, 'Erreur de chargement des statistiques');
        }
    }

    async createRevenueChart() {
        const canvas = document.getElementById('revenueChart');
        if (!canvas) return;

        try {
            const response = await fetch('ajax/chart-data.php?type=revenue');
            const data = await response.json();

            this.charts.revenue = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenus (€)',
                        data: data.values,
                        borderColor: this.defaultColors.primary,
                        backgroundColor: this.hexToRgba(this.defaultColors.primary, 0.1),
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: this.defaultColors.primary,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: this.defaultColors.primary,
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return `Revenus: ${new Intl.NumberFormat('fr-FR').format(context.parsed.y)} €`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                },
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR', {
                                        style: 'currency',
                                        currency: 'EUR',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Erreur création graphique revenus:', error);
            this.showChartError(canvas, 'Erreur de chargement des revenus');
        }
    }

    async createLoanStatusChart() {
        const canvas = document.getElementById('loanStatusChart');
        if (!canvas) return;

        try {
            const response = await fetch('ajax/chart-data.php?type=loan_status');
            const data = await response.json();

            this.charts.loanStatus = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Nombre de prêts',
                        data: data.values,
                        backgroundColor: [
                            this.defaultColors.warning,
                            this.defaultColors.info,
                            this.defaultColors.success,
                            this.defaultColors.error
                        ],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff'
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                },
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Erreur création graphique statut prêts:', error);
            this.showChartError(canvas, 'Erreur de chargement des statuts');
        }
    }

    async createMonthlyChart() {
        const canvas = document.getElementById('monthlyChart');
        if (!canvas) return;

        try {
            const response = await fetch('ajax/chart-data.php?type=monthly');
            const data = await response.json();

            this.charts.monthly = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Demandes',
                            data: data.requests,
                            backgroundColor: this.hexToRgba(this.defaultColors.primary, 0.8),
                            borderRadius: 4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Montant (€)',
                            data: data.amounts,
                            type: 'line',
                            borderColor: this.defaultColors.accent,
                            backgroundColor: this.hexToRgba(this.defaultColors.accent, 0.1),
                            borderWidth: 3,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Nombre de demandes'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Montant (€)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR', {
                                        style: 'currency',
                                        currency: 'EUR',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Erreur création graphique mensuel:', error);
            this.showChartError(canvas, 'Erreur de chargement des données mensuelles');
        }
    }

    async createUserGrowthChart() {
        const canvas = document.getElementById('userGrowthChart');
        if (!canvas) return;

        try {
            const response = await fetch('ajax/chart-data.php?type=user_growth');
            const data = await response.json();

            this.charts.userGrowth = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Nouveaux utilisateurs',
                        data: data.values,
                        borderColor: this.defaultColors.success,
                        backgroundColor: this.hexToRgba(this.defaultColors.success, 0.1),
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: this.defaultColors.success,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Erreur création graphique croissance utilisateurs:', error);
            this.showChartError(canvas, 'Erreur de chargement de la croissance');
        }
    }

    async createWithdrawalChart() {
        const canvas = document.getElementById('withdrawalChart');
        if (!canvas) return;

        try {
            const response = await fetch('ajax/chart-data.php?type=withdrawals');
            const data = await response.json();

            this.charts.withdrawal = new Chart(canvas, {
                type: 'polarArea',
                data: {
                    labels: ['En attente', 'Approuvés', 'Traités', 'Rejetés'],
                    datasets: [{
                        data: [data.pending, data.approved, data.processed, data.rejected],
                        backgroundColor: [
                            this.hexToRgba(this.defaultColors.warning, 0.8),
                            this.hexToRgba(this.defaultColors.info, 0.8),
                            this.hexToRgba(this.defaultColors.success, 0.8),
                            this.hexToRgba(this.defaultColors.error, 0.8)
                        ],
                        borderColor: [
                            this.defaultColors.warning,
                            this.defaultColors.info,
                            this.defaultColors.success,
                            this.defaultColors.error
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Erreur création graphique retraits:', error);
            this.showChartError(canvas, 'Erreur de chargement des retraits');
        }
    }

    setupChartInteractions() {
        const periodButtons = document.querySelectorAll('.period-btn');
        periodButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.changePeriod(button.dataset.period, button.dataset.chart);
            });
        });

        const refreshButtons = document.querySelectorAll('[data-refresh-chart]');
        refreshButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.refreshChart(button.dataset.refreshChart);
            });
        });
    }

    async changePeriod(period, chartType) {
        const chart = this.charts[chartType];
        if (!chart) return;

        document.querySelectorAll('.period-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-period="${period}"]`).classList.add('active');

        try {
            const response = await fetch(`ajax/chart-data.php?type=${chartType}&period=${period}`);
            const data = await response.json();

            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update('active');
        } catch (error) {
            console.error('Erreur changement période:', error);
        }
    }

    async refreshChart(chartType) {
        const chart = this.charts[chartType];
        if (!chart) return;

        try {
            const response = await fetch(`ajax/chart-data.php?type=${chartType}&refresh=${Date.now()}`);
            const data = await response.json();

            chart.data.labels = data.labels;
            chart.data.datasets.forEach((dataset, index) => {
                dataset.data = data.datasets[index].data;
            });
            chart.update('active');

            this.showToast('Graphique mis à jour', 'success');
        } catch (error) {
            console.error('Erreur refresh graphique:', error);
            this.showToast('Erreur de mise à jour', 'error');
        }
    }

    createRealTimeChart(canvasId, type) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Temps réel',
                    data: [],
                    borderColor: this.defaultColors.accent,
                    backgroundColor: this.hexToRgba(this.defaultColors.accent, 0.1),
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            displayFormats: {
                                minute: 'HH:mm'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                animation: {
                    duration: 0
                }
            }
        });

        this.charts[canvasId] = chart;
        return chart;
    }

    updateRealTimeChart(chartId, value) {
        const chart = this.charts[chartId];
        if (!chart) return;

        const now = new Date();
        
        chart.data.labels.push(now);
        chart.data.datasets[0].data.push(value);

        if (chart.data.labels.length > 50) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
        }

        chart.update('none');
    }

    exportChart(chartId, format = 'png') {
        const chart = this.charts[chartId];
        if (!chart) return;

        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = `chart_${chartId}_${new Date().toISOString().split('T')[0]}.${format}`;
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    destroyChart(chartId) {
        const chart = this.charts[chartId];
        if (chart) {
            chart.destroy();
            delete this.charts[chartId];
        }
    }

    destroyAllCharts() {
        Object.keys(this.charts).forEach(chartId => {
            this.destroyChart(chartId);
        });
    }

    showChartError(canvas, message) {
        const container = canvas.parentNode;
        container.innerHTML = `
            <div class="chart-error">
                <div class="error-icon">📊</div>
                <div class="error-message">${message}</div>
                <button class="btn btn-sm btn-primary" onclick="location.reload()">Réessayer</button>
            </div>
        `;
    }

    hexToRgba(hex, alpha = 1) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    showToast(message, type = 'info') {
        if (window.adminApp && window.adminApp.showToast) {
            window.adminApp.showToast(message, type);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chartManager = new ChartManager();
});

window.exportChart = function(chartId, format = 'png') {
    if (window.chartManager) {
        window.chartManager.exportChart(chartId, format);
    }
};

window.refreshChart = function(chartId) {
    if (window.chartManager) {
        window.chartManager.refreshChart(chartId);
    }
};

Chart.defaults.font.family = 'Inter';
Chart.defaults.color = '#6B7280';
Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.1)';

Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
Chart.defaults.plugins.tooltip.bodyColor = '#ffffff';
Chart.defaults.plugins.tooltip.borderColor = '#1F3B73';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.displayColors = false;