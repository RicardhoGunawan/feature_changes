import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    if (!document.querySelector('#salesPurchaseChart')) return;

    try {
        const response = await api.getDashboardData();
        if (response.success) {
            const data = response.data;
            updateDashboardStats(data);
            updateRecentActivities(data.recent_activities);
            renderAttendanceChart(data.chart_data);
            renderMonthlyStats(data.this_month);
        }
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
});

function updateDashboardStats(data) {
    // Total Employees
    const totalEmployeesEl = document.querySelector('#totalEmployees');
    if (totalEmployeesEl) totalEmployeesEl.textContent = data.employees.total_employees;

    // Today's Checked In
    const checkedInEl = document.querySelector('#checkedInToday');
    if (checkedInEl) checkedInEl.textContent = data.today.checked_in;

    // Today's Late
    const lateEl = document.querySelector('#lateToday');
    if (lateEl) lateEl.textContent = data.today.late;

    // Attendance Rate
    const rateEl = document.querySelector('#attendanceRate');
    if (rateEl) rateEl.textContent = data.today.attendance_rate;

    // Pending Leave Requests
    const pendingLeaveEl = document.querySelector('#pendingLeave');
    if (pendingLeaveEl) pendingLeaveEl.textContent = data.pending_leave || 0;
}

function updateRecentActivities(activities) {
    const listContainer = document.querySelector('#recentActivitiesList');
    if (!listContainer) return;

    listContainer.innerHTML = activities.map(activity => `
        <li class="list-group-item d-flex align-items-center gap-3">
            <div class="icon-shape icon-sm ${activity.action === 'Check In' ? 'bg-success' : 'bg-primary'} text-white rounded-circle">
                <i class="ti ti-${activity.action === 'Check In' ? 'login' : 'logout'}"></i>
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold">${activity.employee_name}</p>
                <small class="text-muted">${activity.position} • ${activity.action}</small>
            </div>
            <div class="text-end">
                <p class="mb-0 small fw-bold">${activity.time}</p>
                <span class="badge ${activity.status === 'late' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'} border">${activity.status}</span>
            </div>
        </li>
    `).join('');
}

function renderAttendanceChart(chartData) {
    const chartContainer = document.querySelector("#salesPurchaseChart");
    if (!chartContainer) return;

    // Clear existing chart if any
    chartContainer.innerHTML = '';

    const options = {
        series: [
            {
                name: 'Tepat Waktu',
                data: chartData.map(d => d.on_time),
            },
            {
                name: 'Terlambat',
                data: chartData.map(d => d.late),
            },
        ],
        colors: ['#198754', '#dc3545'],
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: { show: false },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
            },
        },
        dataLabels: { enabled: false },
        xaxis: {
            categories: chartData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
            }),
        },
        legend: {
            position: 'top',
        },
        fill: { opacity: 1 },
    };

    const chart = new ApexCharts(chartContainer, options);
    chart.render();
}

function renderMonthlyStats(monthData) {
    const container = document.querySelector('#customerChart');
    if (!container) return;

    container.innerHTML = '';

    const rate = monthData.total_attendance > 0 
        ? Math.round((monthData.on_time_count / monthData.total_attendance) * 100) 
        : 0;

    const options = {
        series: [rate],
        chart: {
            height: 250,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                hollow: { size: '70%' },
                dataLabels: {
                    name: { show: false },
                    value: {
                        fontSize: '30px',
                        show: true,
                        formatter: function (val) { return val + '%' }
                    }
                }
            }
        },
        labels: ['On Time Rate'],
        colors: ['#0d6efd'],
    };

    const chart = new ApexCharts(container, options);
    chart.render();

    // Update text stats
    const onTimeEl = document.querySelector('#monthOnTime');
    if (onTimeEl) onTimeEl.textContent = monthData.on_time_count;

    const lateEl = document.querySelector('#monthLate');
    if (lateEl) lateEl.textContent = monthData.late_count;

    const totalEl = document.querySelector('#monthTotal');
    if (totalEl) totalEl.textContent = monthData.total_attendance;
}
