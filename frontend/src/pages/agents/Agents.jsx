import { useState, useEffect } from 'react';
import { agentsAPI } from '../../services/api';
import { FiUser } from 'react-icons/fi';

function Agents() {
  const [agents, setAgents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    agentsAPI.getAll()
      .then(res => setAgents(res.data.data || []))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <div className="text-center py-5"><div className="spinner-border text-primary" /></div>;
  }

  return (
    <div>
      <h4 className="fw-bold mb-4">Agents</h4>
      <div className="row g-3">
        {agents.map(agent => (
          <div key={agent.id} className="col-md-4">
            <div className="card shadow-sm h-100">
              <div className="card-body text-center">
                <div className="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style={{ width: 64, height: 64 }}>
                  <FiUser size={28} className="text-primary" />
                </div>
                <h6 className="fw-bold">{agent.name}</h6>
                <p className="text-muted small mb-2">{agent.email}</p>
                <span className="badge bg-info">
                  {agent.open_ticket_count || 0} active tickets
                </span>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default Agents;
