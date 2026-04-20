import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { dashboardAPI } from '../services/api';
import { useAuth } from '../hooks/useAuth';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Title } from 'chart.js';
import { Doughnut, Bar, Line } from 'react-chartjs-2';
import { FiInbox, FiClock, FiCheckCircle, FiAlertCircle, FiTrendingUp, FiUsers } from 'react-icons/fi';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Title);

const statusColors = {
  open: 'primary',
  in_progress: 'warning',
  resolved: 'success',
  closed: 'secondary',
};

const priorityColors = {
  low: 'info',
  medium: 'primary',
  high: 'warning',
  urgent: 'danger',
};

function Dashboard() {
  const { isUser } = useAuth();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    dashboardAPI.getData()
      .then(res => setData(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (isUser) {
    return (
      <div className="text-center py-5">
        <h4>Welcome to ZenConnect</h4>
        <p className="text-muted">Manage your support tickets easily.</p>
        <Link to="/tickets/create" className="btn btn-primary">Create New Ticket</Link>
      </div>
    );
  }

  if (loading) {
    return <div className="text-center py-5"><div className="spinner-border text-primary" /></div>;
  }

  if (!data) return <p className="text-muted">Failed to load dashboard data.</p>;

  const { metrics, tickets_by_status, tickets_by_priority, agent_performance, recent_tickets, daily_ticket_counts } = data;

  const statusChartData = {
    labels: Object.keys(tickets_by_status).map(s => s.replace('_', ' ')),
    datasets: [{
      data: Object.values(tickets_by_status),
      backgroundColor: ['#0d6efd', '#ffc107', '#198754', '#6c757d'],
    }],
  };

  const priorityChartData = {
    labels: Object.keys(tickets_by_priority).map(p => p.charAt(0).toUpperCase() + p.slice(1)),
    datasets: [{
      label: 'Tickets',
      data: Object.values(tickets_by_priority),
      backgroundColor: ['#0dcaf0', '#0d6efd', '#ffc107', '#dc3545'],
    }],
  };

  const dailyChartData = {
    labels: Object.keys(daily_ticket_counts).map(d => new Date(d).toLocaleDateString('en', { month: 'short', day: 'numeric' })),
    datasets: [{
      label: 'New Tickets',
      data: Object.values(daily_ticket_counts),
      borderColor: '#0d6efd',
      backgroundColor: 'rgba(13, 110, 253, 0.1)',
      fill: true,
      tension: 0.4,
    }],
  };

  const MetricCard = ({ icon, label, value, color = 'primary' }) => (
    <div className="col-md-3 col-sm-6">
      <div className="card border-0 shadow-sm h-100">
        <div className="card-body d-flex align-items-center gap-3">
          <div className={`rounded-circle d-flex align-items-center justify-content-center bg-${color} bg-opacity-10`} style={{ width: 48, height: 48 }}>
            <span className={`text-${color}`} style={{ fontSize: '1.3rem' }}>{icon}</span>
          </div>
          <div>
            <h4 className="mb-0 fw-bold">{value}</h4>
            <small className="text-muted">{label}</small>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <div>
      <h4 className="fw-bold mb-4">Dashboard</h4>

      {/* Metric Cards */}
      <div className="row g-3 mb-4">
        <MetricCard icon={<FiInbox />} label="Total Tickets" value={metrics.total_tickets} />
        <MetricCard icon={<FiAlertCircle />} label="Open Tickets" value={metrics.open_tickets} color="warning" />
        <MetricCard icon={<FiCheckCircle />} label="Resolved" value={metrics.resolved_tickets} color="success" />
        <MetricCard icon={<FiClock />} label="Avg Resolution (hrs)" value={metrics.avg_resolution_hours} color="info" />
      </div>

      <div className="row g-3 mb-4">
        <MetricCard icon={<FiTrendingUp />} label="Today's Tickets" value={metrics.today_tickets} />
        <MetricCard icon={<FiTrendingUp />} label="This Week" value={metrics.this_week_tickets} color="info" />
        <MetricCard icon={<FiClock />} label="In Progress" value={metrics.in_progress_tickets} color="warning" />
        <MetricCard icon={<FiUsers />} label="Closed" value={metrics.closed_tickets} color="secondary" />
      </div>

      {/* Charts */}
      <div className="row g-3 mb-4">
        <div className="col-md-4">
          <div className="card shadow-sm h-100">
            <div className="card-body">
              <h6 className="fw-bold mb-3">Tickets by Status</h6>
              <Doughnut data={statusChartData} options={{ plugins: { legend: { position: 'bottom' } } }} />
            </div>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card shadow-sm h-100">
            <div className="card-body">
              <h6 className="fw-bold mb-3">Tickets by Priority</h6>
              <Bar data={priorityChartData} options={{ plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }} />
            </div>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card shadow-sm h-100">
            <div className="card-body">
              <h6 className="fw-bold mb-3">Daily Tickets (30 days)</h6>
              <Line data={dailyChartData} options={{ plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }} />
            </div>
          </div>
        </div>
      </div>

      {/* Agent Performance */}
      {agent_performance?.length > 0 && (
        <div className="card shadow-sm mb-4">
          <div className="card-body">
            <h6 className="fw-bold mb-3">Agent Performance</h6>
            <div className="table-responsive">
              <table className="table table-hover mb-0">
                <thead className="table-light">
                  <tr>
                    <th>Agent</th>
                    <th>Total Assigned</th>
                    <th>Resolved</th>
                    <th>Open</th>
                    <th>Avg Resolution (hrs)</th>
                  </tr>
                </thead>
                <tbody>
                  {agent_performance.map(agent => (
                    <tr key={agent.id}>
                      <td className="fw-semibold">{agent.name}</td>
                      <td>{agent.total_assigned}</td>
                      <td><span className="text-success">{agent.resolved}</span></td>
                      <td><span className="text-warning">{agent.open}</span></td>
                      <td>{agent.avg_resolution_hours}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* Recent Tickets */}
      {recent_tickets?.length > 0 && (
        <div className="card shadow-sm">
          <div className="card-body">
            <h6 className="fw-bold mb-3">Recent Tickets</h6>
            <div className="table-responsive">
              <table className="table table-hover mb-0">
                <thead className="table-light">
                  <tr>
                    <th>Ticket #</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Created By</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  {recent_tickets.map(t => (
                    <tr key={t.id}>
                      <td>
                        <Link to={`/tickets/${t.id}`} className="text-decoration-none">{t.ticket_number}</Link>
                      </td>
                      <td>{t.title}</td>
                      <td><span className={`badge bg-${statusColors[t.status] || 'secondary'}`}>{t.status?.replace('_', ' ')}</span></td>
                      <td><span className={`badge bg-${priorityColors[t.priority] || 'secondary'}`}>{t.priority}</span></td>
                      <td>{t.user}</td>
                      <td>{t.assignee || '—'}</td>
                      <td className="small">{new Date(t.created_at).toLocaleDateString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default Dashboard;
