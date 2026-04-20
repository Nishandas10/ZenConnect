import { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ticketsAPI } from '../../services/api';
import { useAuth } from '../../hooks/useAuth';
import TicketCard from '../../components/TicketCard';
import { FiSearch, FiFilter } from 'react-icons/fi';

function TicketList() {
  const { isAdmin, isAgent } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const [tickets, setTickets] = useState([]);
  const [pagination, setPagination] = useState({});
  const [loading, setLoading] = useState(true);
  const [viewAll, setViewAll] = useState(searchParams.get('view') === 'all');

  const [filters, setFilters] = useState({
    status: searchParams.get('status') || '',
    priority: searchParams.get('priority') || '',
    search: searchParams.get('search') || '',
    page: searchParams.get('page') || 1,
  });

  const fetchTickets = useCallback(async () => {
    setLoading(true);
    try {
      const params = { ...filters, per_page: 15 };
      if (isAdmin || (isAgent && viewAll)) params.all = true;
      Object.keys(params).forEach(key => !params[key] && delete params[key]);
      const response = await ticketsAPI.getAll(params);
      setTickets(response.data.data || []);
      setPagination(response.data.meta || {});
    } catch {
      // ignore
    } finally {
      setLoading(false);
    }
  }, [filters, isAdmin, isAgent, viewAll]);

  useEffect(() => {
    fetchTickets();
  }, [fetchTickets]);

  const handleFilterChange = (key, value) => {
    const newFilters = { ...filters, [key]: value, page: 1 };
    setFilters(newFilters);
    const params = {};
    Object.entries(newFilters).forEach(([k, v]) => { if (v) params[k] = v; });
    setSearchParams(params);
  };

  const toggleView = () => {
    setViewAll(!viewAll);
    const params = {};
    Object.entries(filters).forEach(([k, v]) => { if (v) params[k] = v; });
    if (!viewAll) params.view = 'all';
    setSearchParams(params);
  };

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h4 className="fw-bold mb-0">
          {isAdmin ? 'All Tickets' : isAgent ? (viewAll ? 'All Tickets' : 'My Assigned Tickets') : 'My Tickets'}
        </h4>
        {isAgent && (
          <div className="btn-group" role="group">
            <button
              type="button"
              className={`btn btn-sm ${!viewAll ? 'btn-primary' : 'btn-outline-primary'}`}
              onClick={() => viewAll && toggleView()}
            >
              Assigned to Me
            </button>
            <button
              type="button"
              className={`btn btn-sm ${viewAll ? 'btn-primary' : 'btn-outline-primary'}`}
              onClick={() => !viewAll && toggleView()}
            >
              All Tickets
            </button>
          </div>
        )}
      </div>

      {/* Filters */}
      <div className="card mb-4">
        <div className="card-body">
          <div className="row g-3">
            <div className="col-md-4">
              <div className="input-group">
                <span className="input-group-text"><FiSearch /></span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search tickets..."
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                />
              </div>
            </div>
            <div className="col-md-3">
              <select
                className="form-select"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
              >
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
            </div>
            <div className="col-md-3">
              <select
                className="form-select"
                value={filters.priority}
                onChange={(e) => handleFilterChange('priority', e.target.value)}
              >
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div className="col-md-2">
              <button
                className="btn btn-outline-secondary w-100"
                onClick={() => {
                  setFilters({ status: '', priority: '', search: '', page: 1 });
                  setSearchParams({});
                }}
              >
                <FiFilter className="me-1" /> Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Ticket List */}
      {loading ? (
        <div className="text-center py-5">
          <div className="spinner-border text-primary" />
        </div>
      ) : tickets.length === 0 ? (
        <div className="text-center py-5">
          <h5 className="text-muted">No tickets found</h5>
          <p className="text-muted">Try adjusting your filters or create a new ticket.</p>
        </div>
      ) : (
        <>
          {tickets.map(ticket => (
            <TicketCard key={ticket.id} ticket={ticket} />
          ))}

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <nav className="mt-4">
              <ul className="pagination justify-content-center">
                {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map(page => (
                  <li key={page} className={`page-item ${pagination.current_page === page ? 'active' : ''}`}>
                    <button
                      className="page-link"
                      onClick={() => handleFilterChange('page', page)}
                    >
                      {page}
                    </button>
                  </li>
                ))}
              </ul>
            </nav>
          )}
        </>
      )}
    </div>
  );
}

export default TicketList;
