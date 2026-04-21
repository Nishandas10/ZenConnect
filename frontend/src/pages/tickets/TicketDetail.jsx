import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ticketsAPI, commentsAPI, agentsAPI, aiAPI, tagsAPI } from '../../services/api';
import { useAuth } from '../../hooks/useAuth';
import CommentList from '../../components/CommentList';
import { toast } from 'react-toastify';
import { FiArrowLeft, FiEdit2, FiTrash2, FiCpu, FiUserPlus, FiClock, FiPaperclip, FiTag, FiX } from 'react-icons/fi';

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

function TicketDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { isAdmin, isAgent, user } = useAuth();

  const [ticket, setTicket] = useState(null);
  const [agents, setAgents] = useState([]);
  const [tagLoading, setTagLoading] = useState(false);
  const [tagInput, setTagInput] = useState('');
  const [showTagInput, setShowTagInput] = useState(false);
  const [loading, setLoading] = useState(true);
  const [commentLoading, setCommentLoading] = useState(false);
  const [aiLoading, setAiLoading] = useState({ summarize: false, reply: false });
  const [suggestedReply, setSuggestedReply] = useState('');
  const [editing, setEditing] = useState(false);
  const [editForm, setEditForm] = useState({});

  const fetchTicket = useCallback(async () => {
    try {
      const response = await ticketsAPI.getOne(id);
      setTicket(response.data.ticket);
      setEditForm({
        status: response.data.ticket.status,
        priority: response.data.ticket.priority,
      });
    } catch {
      toast.error('Failed to load ticket');
      navigate('/tickets');
    } finally {
      setLoading(false);
    }
  }, [id, navigate]);

  useEffect(() => {
    fetchTicket();
    if (isAdmin) {
      agentsAPI.getAll().then(res => setAgents(res.data.data || [])).catch(() => {});
    }
  }, [fetchTicket, isAdmin]);

  const handleAddComment = async (data) => {
    setCommentLoading(true);
    try {
      await commentsAPI.create(ticket.id, data);
      toast.success('Comment added');
      fetchTicket();
    } catch {
      toast.error('Failed to add comment');
    } finally {
      setCommentLoading(false);
    }
  };

  const handleUpdateTicket = async () => {
    try {
      const response = await ticketsAPI.update(ticket.id, editForm);
      setTicket(response.data.ticket);
      setEditing(false);
      toast.success('Ticket updated');
    } catch {
      toast.error('Failed to update ticket');
    }
  };

  const handleAssign = async (agentId) => {
    try {
      const response = await ticketsAPI.assign(ticket.id, agentId);
      setTicket(response.data.ticket);
      toast.success('Agent assigned');
    } catch {
      toast.error('Failed to assign agent');
    }
  };

  const handleAutoAssign = async () => {
    try {
      const response = await ticketsAPI.autoAssign(ticket.id);
      setTicket(response.data.ticket);
      toast.success('Agent auto-assigned');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to auto-assign');
    }
  };

  const handleSelfAssign = async () => {
    try {
      const response = await ticketsAPI.selfAssign(ticket.id);
      setTicket(response.data.ticket);
      toast.success('Ticket assigned to you');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to take ownership');
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Are you sure you want to delete this ticket?')) return;
    try {
      await ticketsAPI.delete(ticket.id);
      toast.success('Ticket deleted');
      navigate('/tickets');
    } catch {
      toast.error('Failed to delete ticket');
    }
  };

  const handleSummarize = async () => {
    setAiLoading(prev => ({ ...prev, summarize: true }));
    try {
      const response = await aiAPI.summarize(ticket.id);
      setTicket(prev => ({ ...prev, ai_summary: response.data.summary }));
      toast.success('AI summary generated');
    } catch {
      toast.error('Failed to generate summary');
    } finally {
      setAiLoading(prev => ({ ...prev, summarize: false }));
    }
  };

  const handleRemoveTag = async (tag) => {
    const newTagIds = (ticket.tags?.map(t => t.id) || []).filter(id => id !== tag.id);
    setTagLoading(true);
    try {
      await tagsAPI.updateTicketTags(ticket.id, newTagIds);
      setTicket(prev => ({ ...prev, tags: prev.tags.filter(t => t.id !== tag.id) }));
    } catch {
      toast.error('Failed to remove tag');
    } finally {
      setTagLoading(false);
    }
  };

  const handleAddTag = async (e) => {
    e.preventDefault();
    const name = tagInput.trim();
    if (!name) return;
    setTagLoading(true);
    try {
      const res = await tagsAPI.addToTicket(ticket.id, name);
      const newTag = res.data.tag;
      if (!ticket.tags?.some(t => t.id === newTag.id)) {
        setTicket(prev => ({ ...prev, tags: [...(prev.tags || []), newTag] }));
      }
      setTagInput('');
      setShowTagInput(false);
    } catch {
      toast.error('Failed to add tag');
    } finally {
      setTagLoading(false);
    }
  };

  const handleSuggestReply = async () => {    setAiLoading(prev => ({ ...prev, reply: true }));
    try {
      const response = await aiAPI.suggestReply(ticket.id);
      setSuggestedReply(response.data.suggested_reply || '');
      toast.success('AI reply suggestion generated');
    } catch {
      toast.error('Failed to generate reply suggestion');
    } finally {
      setAiLoading(prev => ({ ...prev, reply: false }));
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5">
        <div className="spinner-border text-primary" />
      </div>
    );
  }

  if (!ticket) return null;

  return (
    <div>
      {/* Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div className="d-flex align-items-center gap-3">
          <button className="btn btn-outline-secondary btn-sm" onClick={() => navigate('/tickets')}>
            <FiArrowLeft />
          </button>
          <div>
            <h4 className="fw-bold mb-0">{ticket.title}</h4>
            <span className="text-muted">{ticket.ticket_number}</span>
          </div>
        </div>
        <div className="d-flex gap-2">
          {(isAdmin || isAgent) && (
            <>
              <button className="btn btn-outline-primary btn-sm" onClick={() => setEditing(!editing)}>
                <FiEdit2 className="me-1" /> Edit
              </button>
            </>
          )}
          {isAdmin && (
            <button className="btn btn-outline-danger btn-sm" onClick={handleDelete}>
              <FiTrash2 className="me-1" /> Delete
            </button>
          )}
        </div>
      </div>

      <div className="row">
        {/* Main Content */}
        <div className="col-lg-8">
          {/* Ticket Details */}
          <div className="card mb-4">
            <div className="card-body">
              <div className="d-flex gap-2 mb-3">
                <span className={`badge bg-${statusColors[ticket.status]}`}>
                  {ticket.status?.replace('_', ' ')}
                </span>
                <span className={`badge bg-${priorityColors[ticket.priority]}`}>
                  {ticket.priority}
                </span>
                {ticket.category && (
                  <span className="badge bg-light text-dark">{ticket.category.name}</span>
                )}
              </div>

              <p style={{ whiteSpace: 'pre-wrap' }}>{ticket.description}</p>

              {/* Tags */}
              <div className="mt-3">
                {ticket.tags?.map(tag => (
                  <span key={tag.id} className="badge me-1 mb-1" style={{ backgroundColor: tag.color, color: '#fff' }}>
                    {tag.name}
                    {(isAdmin || isAgent) && (
                      <button
                        className="btn-close btn-close-white ms-1"
                        style={{ fontSize: '0.5rem', verticalAlign: 'middle' }}
                        onClick={() => handleRemoveTag(tag)}
                        disabled={tagLoading}
                        aria-label="Remove tag"
                      />
                    )}
                  </span>
                ))}
                {(isAdmin || isAgent) && (
                  showTagInput ? (
                    <form onSubmit={handleAddTag} className="d-inline-flex align-items-center gap-1 ms-1">
                      <input
                        autoFocus
                        type="text"
                        className="form-control form-control-sm"
                        style={{ width: 130 }}
                        placeholder="Tag name..."
                        value={tagInput}
                        onChange={e => setTagInput(e.target.value)}
                        disabled={tagLoading}
                      />
                      <button type="submit" className="btn btn-primary btn-sm" disabled={tagLoading || !tagInput.trim()}>
                        {tagLoading ? <span className="spinner-border spinner-border-sm" /> : 'Add'}
                      </button>
                      <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => { setShowTagInput(false); setTagInput(''); }}>
                        <FiX />
                      </button>
                    </form>
                  ) : (
                    <button
                      className="btn btn-outline-secondary btn-sm ms-1"
                      type="button"
                      onClick={() => setShowTagInput(true)}
                    >
                      <FiTag className="me-1" />Add Tag
                    </button>
                  )
                )}
              </div>

              {/* Attachments */}
              {ticket.attachments?.length > 0 && (
                <div className="mt-3 pt-3 border-top">
                  <h6 className="fw-bold"><FiPaperclip /> Attachments</h6>
                  {ticket.attachments.map(att => (
                    <a key={att.id} href={att.url} target="_blank" rel="noopener noreferrer" className="d-block small">
                      {att.original_filename} ({(att.size / 1024).toFixed(1)} KB)
                    </a>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* AI Summary */}
          {(isAdmin || isAgent) && (
            <div className="card mb-4">
              <div className="card-body">
                <div className="d-flex justify-content-between align-items-center mb-2">
                  <h6 className="fw-bold mb-0"><FiCpu className="me-2" />AI Summary</h6>
                  <button className="btn btn-outline-primary btn-sm" onClick={handleSummarize} disabled={aiLoading.summarize}>
                    {aiLoading.summarize ? <span className="spinner-border spinner-border-sm" /> : 'Generate Summary'}
                  </button>
                </div>
                {ticket.ai_summary ? (
                  <p className="mb-0 bg-light p-3 rounded" style={{ whiteSpace: 'pre-wrap' }}>{ticket.ai_summary}</p>
                ) : (
                  <p className="text-muted mb-0">No AI summary generated yet.</p>
                )}
              </div>
            </div>
          )}

          {/* AI Reply Suggestion */}
          {(isAdmin || isAgent) && (
            <div className="card mb-4">
              <div className="card-body">
                <div className="d-flex justify-content-between align-items-center mb-2">
                  <h6 className="fw-bold mb-0"><FiCpu className="me-2" />AI Reply Suggestion</h6>
                  <button className="btn btn-outline-primary btn-sm" onClick={handleSuggestReply} disabled={aiLoading.reply}>
                    {aiLoading.reply ? <span className="spinner-border spinner-border-sm" /> : 'Suggest Reply'}
                  </button>
                </div>
                {suggestedReply ? (
                  <div className="bg-light p-3 rounded">
                    <p className="mb-2" style={{ whiteSpace: 'pre-wrap' }}>{suggestedReply}</p>
                    <button
                      className="btn btn-sm btn-primary"
                      onClick={() => {
                        navigator.clipboard.writeText(suggestedReply);
                        toast.info('Copied to clipboard');
                      }}
                    >
                      Copy to clipboard
                    </button>
                  </div>
                ) : (
                  <p className="text-muted mb-0">Click "Suggest Reply" to get an AI-generated response.</p>
                )}
              </div>
            </div>
          )}

          {/* Comments */}
          <div className="card mb-4">
            <div className="card-body">
              <CommentList
                comments={ticket.comments}
                onAddComment={handleAddComment}
                loading={commentLoading}
              />
            </div>
          </div>

          {/* History */}
          {ticket.histories?.length > 0 && (
            <div className="card mb-4">
              <div className="card-body">
                <h6 className="fw-bold mb-3"><FiClock className="me-2" />Activity History</h6>
                <div className="timeline">
                  {ticket.histories.map(history => (
                    <div key={history.id} className="d-flex gap-3 mb-2 small">
                      <span className="text-muted" style={{ minWidth: '140px' }}>
                        {new Date(history.created_at).toLocaleString()}
                      </span>
                      <div>
                        <strong>{history.user?.name || 'System'}</strong>{' '}
                        {history.action === 'created' && 'created this ticket'}
                        {history.action === 'updated' && (
                          <>changed <strong>{history.field}</strong> from "{history.old_value}" to "{history.new_value}"</>
                        )}
                        {history.action === 'assigned' && 'assigned this ticket'}
                        {history.action === 'commented' && 'added a comment'}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="col-lg-4">
          {/* Edit Panel */}
          {editing && (isAdmin || isAgent) && (
            <div className="card mb-4">
              <div className="card-body">
                <h6 className="fw-bold mb-3">Update Ticket</h6>
                <div className="mb-3">
                  <label className="form-label small fw-semibold">Status</label>
                  <select
                    className="form-select form-select-sm"
                    value={editForm.status}
                    onChange={(e) => setEditForm({ ...editForm, status: e.target.value })}
                  >
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                  </select>
                </div>
                <div className="mb-3">
                  <label className="form-label small fw-semibold">Priority</label>
                  <select
                    className="form-select form-select-sm"
                    value={editForm.priority}
                    onChange={(e) => setEditForm({ ...editForm, priority: e.target.value })}
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
                <button className="btn btn-primary btn-sm w-100" onClick={handleUpdateTicket}>
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {/* Assignment Panel */}
          {isAdmin && (
            <div className="card mb-4">
              <div className="card-body">
                <h6 className="fw-bold mb-3"><FiUserPlus className="me-2" />Assignment</h6>
                <p className="small mb-2">
                  <strong>Assigned to:</strong> {ticket.assignee?.name || 'Unassigned'}
                </p>
                <select
                  className="form-select form-select-sm mb-2"
                  value={ticket.assignee?.id || ''}
                  onChange={(e) => e.target.value && handleAssign(parseInt(e.target.value))}
                >
                  <option value="">Select agent...</option>
                  {agents.map(agent => (
                    <option key={agent.id} value={agent.id}>
                      {agent.name} ({agent.open_ticket_count || 0} active)
                    </option>
                  ))}
                </select>
                <button className="btn btn-outline-secondary btn-sm w-100" onClick={handleAutoAssign}>
                  Auto-assign (least workload)
                </button>
              </div>
            </div>
          )}

          {/* Agent Actions Panel - for agents to take ownership */}
          {isAgent && !ticket.assignee && (
            <div className="card mb-4 border-primary">
              <div className="card-body">
                <h6 className="fw-bold mb-3"><FiUserPlus className="me-2" />Unassigned Ticket</h6>
                <p className="small text-muted mb-3">This ticket is not assigned to anyone yet. Take ownership to start working on it.</p>
                <button className="btn btn-primary btn-sm w-100" onClick={handleSelfAssign}>
                  Take Ownership
                </button>
              </div>
            </div>
          )}

          {/* Agent Assignment Info - for agents viewing assigned tickets */}
          {isAgent && ticket.assignee && (
            <div className="card mb-4">
              <div className="card-body">
                <h6 className="fw-bold mb-3"><FiUserPlus className="me-2" />Assignment</h6>
                <p className="small mb-0">
                  <strong>Assigned to:</strong> {ticket.assignee.name}
                  {ticket.assignee.id === user?.id && <span className="badge bg-success ms-2">You</span>}
                </p>
              </div>
            </div>
          )}

          {/* Info Panel */}
          <div className="card mb-4">
            <div className="card-body">
              <h6 className="fw-bold mb-3">Details</h6>
              <table className="table table-sm table-borderless mb-0">
                <tbody>
                  <tr>
                    <td className="text-muted small">Created by</td>
                    <td className="small fw-semibold">{ticket.user?.name}</td>
                  </tr>
                  <tr>
                    <td className="text-muted small">Created</td>
                    <td className="small">{new Date(ticket.created_at).toLocaleString()}</td>
                  </tr>
                  <tr>
                    <td className="text-muted small">Updated</td>
                    <td className="small">{new Date(ticket.updated_at).toLocaleString()}</td>
                  </tr>
                  {ticket.sla_deadline && (
                    <tr>
                      <td className="text-muted small">SLA Deadline</td>
                      <td className={`small ${new Date(ticket.sla_deadline) < new Date() ? 'text-danger fw-bold' : ''}`}>
                        {new Date(ticket.sla_deadline).toLocaleString()}
                      </td>
                    </tr>
                  )}
                  {ticket.resolved_at && (
                    <tr>
                      <td className="text-muted small">Resolved</td>
                      <td className="small">{new Date(ticket.resolved_at).toLocaleString()}</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default TicketDetail;
