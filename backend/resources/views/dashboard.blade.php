<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SULO — Biogas Digester Monitor</title>
    <script src="{{ asset('js/chart.umd.min.js') }}"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #0f1117;
            --card: #1a1d27;
            --border: #2a2d3a;
            --text: #e4e4e7;
            --muted: #71717a;
            --green: #22c55e;
            --yellow: #eab308;
            --red: #ef4444;
            --blue: #3b82f6;
            --teal: #14b8a6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ─── Header ─── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: var(--card);
            border-bottom: 1px solid var(--border);
        }
        .header h1 { font-size: 1.4rem; font-weight: 700; }
        .header h1 span { color: var(--teal); }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        .status-normal  { background: rgba(34,197,94,0.15); color: var(--green); }
        .status-warning { background: rgba(234,179,8,0.15); color: var(--yellow); }
        .status-critical{ background: rgba(239,68,68,0.15); color: var(--red); }
        .online-tag {
            font-size: 0.75rem;
            color: var(--muted);
            padding: 4px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
        }

        /* ─── Grid ─── */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ─── Cards ─── */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .card-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .card-unit {
            font-size: 0.9rem;
            font-weight: 400;
            color: var(--muted);
            margin-left: 4px;
        }
        .card-range {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 6px;
        }
        .valve-open { color: var(--red); }
        .valve-closed { color: var(--green); }

        /* ─── Charts Section ─── */
        .charts-section {
            padding: 0 24px 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 16px;
        }
        .chart-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .chart-card h3 {
            font-size: 0.9rem;
            margin-bottom: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .chart-container {
            position: relative;
            height: 200px;
        }

        /* ─── Alerts Table ─── */
        .alerts-section {
            padding: 0 24px 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .alerts-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .alerts-card h3 {
            font-size: 0.9rem;
            margin-bottom: 16px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 10px 12px;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border);
        }
        th { color: var(--muted); font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        .severity-critical { color: var(--red); font-weight: 600; }
        .severity-warning { color: var(--yellow); font-weight: 600; }

        /* ─── Footer ─── */
        .footer {
            text-align: center;
            padding: 16px;
            color: var(--muted);
            font-size: 0.75rem;
            border-top: 1px solid var(--border);
            margin-top: 12px;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .dashboard { grid-template-columns: 1fr 1fr; padding: 16px; }
            .charts-grid { grid-template-columns: 1fr; }
            .card-value { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <h1><span>SULO</span> — Biogas Digester Monitor</h1>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="online-tag" id="connStatus">● Offline LAN</span>
            <div class="status-badge status-normal" id="overallStatus">Normal</div>
        </div>
    </div>

    <!-- Live Sensor Cards -->
    <div class="dashboard">
        <!-- Temperature -->
        <div class="card">
            <div class="card-label">🌡️ Temperature</div>
            <div class="card-value" id="tempValue">--<span class="card-unit">°C</span></div>
            <div class="card-range">Safe: 25–55 °C</div>
        </div>

        <!-- pH -->
        <div class="card">
            <div class="card-label">🧪 pH Level</div>
            <div class="card-value" id="phValue">--</div>
            <div class="card-range">Safe: 6.0–8.5</div>
        </div>

        <!-- Gas Level -->
        <div class="card">
            <div class="card-label">💨 Gas Level</div>
            <div class="card-value" id="gasValue">--<span class="card-unit">ppm</span></div>
            <div class="card-range">Max safe: 5000 ppm</div>
        </div>

        <!-- Pressure -->
        <div class="card">
            <div class="card-label">🔴 Pressure</div>
            <div class="card-value" id="pressValue">--<span class="card-unit">kPa</span></div>
            <div class="card-range">Max: 200 kPa (valve opens)</div>
        </div>

        <!-- Flow Rate -->
        <div class="card">
            <div class="card-label">🌊 Flow Rate</div>
            <div class="card-value" id="flowValue">--<span class="card-unit">L/min</span></div>
            <div class="card-range">Methane output</div>
        </div>

        <!-- Solenoid Valve -->
        <div class="card">
            <div class="card-label">⚙️ Solenoid Valve</div>
            <div class="card-value valve-closed" id="valveStatus">CLOSED</div>
            <div class="card-range" id="valveLabel">Normal operation</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-section">
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Temperature & Pressure (24h)</h3>
                <div class="chart-container">
                    <canvas id="tempPressChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>pH & Gas Level (24h)</h3>
                <div class="chart-container">
                    <canvas id="phGasChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Log -->
    <div class="alerts-section">
        <div class="alerts-card">
            <h3>⚠️ Recent Alerts</h3>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="alertsTable">
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--muted); padding: 24px;">
                            No alerts recorded
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        SULO — Smart Utility for Local Organic Waste | Local Network Only | No Internet Required
    </div>

    <script>
        // ─── Configuration ───
        const API_BASE = window.location.origin + '/api';
        const REFRESH_INTERVAL = 5000; // 5 seconds

        // ─── Chart Setup ───
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: '#71717a', font: { size: 11 } } }
            },
            scales: {
                x: {
                    type: 'category',
                    labels: [],
                    ticks: { color: '#71717a', maxTicksLimit: 8, font: { size: 10 } },
                    grid: { color: '#2a2d3a' }
                },
                y: {
                    ticks: { color: '#71717a', font: { size: 10 } },
                    grid: { color: '#2a2d3a' }
                }
            }
        };

        const tempPressChart = new Chart(document.getElementById('tempPressChart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Temperature (°C)',
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.1)',
                        tension: 0.3,
                        fill: true,
                        data: []
                    },
                    {
                        label: 'Pressure (kPa)',
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1',
                        data: []
                    }
                ]
            },
            options: {
                ...chartOptions,
                scales: {
                    ...chartOptions.scales,
                    y: { ...chartOptions.scales.y, position: 'left', title: { display: true, text: '°C', color: '#71717a' } },
                    y1: { ...chartOptions.scales.y, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'kPa', color: '#71717a' } }
                }
            }
        });

        const phGasChart = new Chart(document.getElementById('phGasChart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'pH',
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.1)',
                        tension: 0.3,
                        fill: true,
                        data: []
                    },
                    {
                        label: 'Gas (ppm)',
                        borderColor: '#eab308',
                        backgroundColor: 'rgba(234,179,8,0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1',
                        data: []
                    }
                ]
            },
            options: {
                ...chartOptions,
                scales: {
                    ...chartOptions.scales,
                    y: { ...chartOptions.scales.y, position: 'left', title: { display: true, text: 'pH', color: '#71717a' } },
                    y1: { ...chartOptions.scales.y, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'ppm', color: '#71717a' } }
                }
            }
        });

        // ─── Fetch Latest Reading ───
        async function fetchLatest() {
            try {
                const res = await fetch(`${API_BASE}/readings/latest`);
                if (!res.ok) throw new Error('No data');
                const data = await res.json();

                document.getElementById('tempValue').innerHTML = `${parseFloat(data.temperature).toFixed(1)}<span class="card-unit">°C</span>`;
                document.getElementById('phValue').textContent = parseFloat(data.ph).toFixed(2);
                document.getElementById('gasValue').innerHTML = `${parseFloat(data.gas_level).toFixed(0)}<span class="card-unit">ppm</span>`;
                document.getElementById('pressValue').innerHTML = `${parseFloat(data.pressure).toFixed(1)}<span class="card-unit">kPa</span>`;
                document.getElementById('flowValue').innerHTML = `${parseFloat(data.flow_rate).toFixed(2)}<span class="card-unit">L/min</span>`;

                // Valve
                const valveEl = document.getElementById('valveStatus');
                const valveLabel = document.getElementById('valveLabel');
                if (data.valve_open) {
                    valveEl.textContent = 'OPEN';
                    valveEl.className = 'card-value valve-open';
                    valveLabel.textContent = '⚠ Venting excess pressure';
                } else {
                    valveEl.textContent = 'CLOSED';
                    valveEl.className = 'card-value valve-closed';
                    valveLabel.textContent = 'Normal operation';
                }

                // Status badge
                const statusBadge = document.getElementById('overallStatus');
                const statusMap = { 0: ['Normal', 'status-normal'], 1: ['Warning', 'status-warning'], 2: ['Critical', 'status-critical'] };
                const [label, cls] = statusMap[data.status] || statusMap[0];
                statusBadge.textContent = label;
                statusBadge.className = `status-badge ${cls}`;

                // Connection indicator
                document.getElementById('connStatus').textContent = '● Connected to LAN';

            } catch (e) {
                document.getElementById('connStatus').textContent = '● Server unreachable';
                document.getElementById('connStatus').style.color = '#ef4444';
            }
        }

        // ─── Fetch History for Charts ───
        async function fetchHistory() {
            try {
                const res = await fetch(`${API_BASE}/readings/history?hours=24`);
                if (!res.ok) return;
                const data = await res.json();

                const labels = data.map(r => {
                    const d = new Date(r.hour);
                    return d.getHours().toString().padStart(2, '0') + ':00';
                });

                tempPressChart.data.labels = labels;
                tempPressChart.data.datasets[0].data = data.map(r => parseFloat(r.temperature));
                tempPressChart.data.datasets[1].data = data.map(r => parseFloat(r.pressure));
                tempPressChart.update('none');

                phGasChart.data.labels = labels;
                phGasChart.data.datasets[0].data = data.map(r => parseFloat(r.ph));
                phGasChart.data.datasets[1].data = data.map(r => parseFloat(r.gas_level));
                phGasChart.update('none');

            } catch (e) {
                console.error('History fetch failed:', e);
            }
        }

        // ─── Fetch Alerts ───
        async function fetchAlerts() {
            try {
                const res = await fetch(`${API_BASE}/alerts?limit=10`);
                if (!res.ok) return;
                const alerts = await res.json();

                const tbody = document.getElementById('alertsTable');
                if (!alerts.length) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--muted); padding: 24px;">No alerts recorded</td></tr>`;
                    return;
                }

                tbody.innerHTML = alerts.map(a => {
                    const time = new Date(a.created_at).toLocaleString();
                    const sevClass = a.severity === 'critical' ? 'severity-critical' : 'severity-warning';
                    const ack = a.acknowledged ? '✅ Acknowledged' : '⏳ Active';
                    return `<tr>
                        <td>${time}</td>
                        <td>${a.alert_type}</td>
                        <td class="${sevClass}">${a.severity.toUpperCase()}</td>
                        <td>${a.message}</td>
                        <td>${ack}</td>
                    </tr>`;
                }).join('');

            } catch (e) {
                console.error('Alerts fetch failed:', e);
            }
        }

        // ─── Refresh Loop ───
        async function refreshAll() {
            await Promise.all([fetchLatest(), fetchHistory(), fetchAlerts()]);
        }

        refreshAll();
        setInterval(refreshAll, REFRESH_INTERVAL);
    </script>
</body>
</html>
