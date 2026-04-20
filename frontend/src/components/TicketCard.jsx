import { Link } from 'react-router-dom';
import { FiClock, FiUser, FiMessageSquare } from 'react-icons/fi';

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

function TicketCard({ ticket }) {
  return (
    <div className="card mb-3 border-start border-4" style={{ borderColor: `var(--bs-${priorityColors[ticket.priority]})` }}>
      <div className="card-body">
        <div className="d-flex justify-content-between align-items-start">
          <div className="flex-grow-1">
            <div className="d-flex align-items-center gap-2 mb-2">
              <span className="text-muted small">{ticket.ticket_number}</span>
              <span className={`badge bg-${statusColors[ticket.status]}`}>
                {ticket.status?.replace('_', ' ')}
              </span>
              <span className={`badge bg-${priorityColors[ticket.priority]}`}>
                {ticket.priority}
              </span>
            </div>
            <h6 className="mb-1">
              <Link to={`/tickets/${ticket.id}`} className="text-decoration-none text-dark">
                {ticket.title}
              </Link>
            </h6>
            <p className="text-muted small mb-2" style={{ maxWidth: '600px' }}>
              {ticket.description?.substring(0, 120)}...
            </p>
            <div className="d-flex align-items-center gap-3 text-muted small">
              <span><FiUser className="me-1" />{ticket.user?.name}</span>
              {ticket.assignee && (
                <span>→ {ticket.assignee.name}</span>
              )}
              <span><FiClock className="me-1" />{new Date(ticket.created_at).toLocaleDateString()}</span>
              {ticket.comments_count > 0 && (
                <span><FiMessageSquare className="me-1" />{ticket.comments_count}</span>
              )}
            </div>
          </div>
          {ticket.category && (
            <span className="badge bg-light text-dark">{ticket.category.name}</span>
          )}
        </div>
        {ticket.tags?.length > 0 && (
          <div className="mt-2">
            {ticket.tags.map(tag => (
              <span key={tag.id} className="badge me-1" style={{ backgroundColor: tag.color, color: '#fff' }}>
                {tag.name}
              </span>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export default TicketCard;
