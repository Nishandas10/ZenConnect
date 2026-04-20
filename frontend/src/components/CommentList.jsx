import { useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import { FiUser, FiLock, FiSend } from 'react-icons/fi';

function CommentList({ comments, onAddComment, loading }) {
  const { isAdmin, isAgent } = useAuth();
  const [body, setBody] = useState('');
  const [isInternal, setIsInternal] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!body.trim()) return;
    await onAddComment({ body, is_internal: isInternal });
    setBody('');
    setIsInternal(false);
  };

  return (
    <div>
      <h6 className="fw-bold mb-3">Comments ({comments?.length || 0})</h6>

      {/* Comments Thread */}
      <div className="mb-4">
        {(!comments || comments.length === 0) ? (
          <p className="text-muted">No comments yet.</p>
        ) : (
          comments.map(comment => (
            <div key={comment.id} className={`card mb-2 ${comment.is_internal ? 'border-warning bg-warning bg-opacity-10' : ''}`}>
              <div className="card-body py-2 px-3">
                <div className="d-flex justify-content-between align-items-center mb-1">
                  <div className="d-flex align-items-center gap-2">
                    <FiUser className="text-muted" />
                    <strong className="small">{comment.user?.name}</strong>
                    {comment.is_internal && (
                      <span className="badge bg-warning text-dark small">
                        <FiLock className="me-1" size={10} />Internal
                      </span>
                    )}
                  </div>
                  <small className="text-muted">
                    {new Date(comment.created_at).toLocaleString()}
                  </small>
                </div>
                <p className="mb-0 small" style={{ whiteSpace: 'pre-wrap' }}>{comment.body}</p>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Add Comment Form */}
      <form onSubmit={handleSubmit}>
        <div className="mb-2">
          <textarea
            className="form-control"
            rows={3}
            value={body}
            onChange={(e) => setBody(e.target.value)}
            placeholder="Add a comment..."
          />
        </div>
        <div className="d-flex justify-content-between align-items-center">
          <div>
            {(isAdmin || isAgent) && (
              <div className="form-check">
                <input
                  type="checkbox"
                  className="form-check-input"
                  id="is_internal"
                  checked={isInternal}
                  onChange={(e) => setIsInternal(e.target.checked)}
                />
                <label className="form-check-label small" htmlFor="is_internal">
                  <FiLock className="me-1" />Internal note (not visible to customer)
                </label>
              </div>
            )}
          </div>
          <button type="submit" className="btn btn-primary btn-sm" disabled={loading || !body.trim()}>
            {loading ? (
              <span className="spinner-border spinner-border-sm" />
            ) : (
              <><FiSend className="me-1" /> Send</>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}

export default CommentList;
