import { useState, useEffect } from 'react';
import { useForm } from '../hooks/useForm';
import { categoriesAPI } from '../services/api';

function TicketForm({ onSubmit, initialValues, loading: submitLoading }) {
  const [categories, setCategories] = useState([]);
  const { values, errors, loading, handleChange, handleSubmit } = useForm(
    initialValues || {
      title: '',
      description: '',
      priority: 'medium',
      category_id: '',
      attachments: [],
    }
  );

  useEffect(() => {
    categoriesAPI.getAll().then(res => setCategories(res.data.data || [])).catch(() => {});
  }, []);

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="mb-3">
        <label className="form-label fw-semibold">Title *</label>
        <input
          type="text"
          name="title"
          className={`form-control ${errors.title ? 'is-invalid' : ''}`}
          value={values.title}
          onChange={handleChange}
          placeholder="Brief description of your issue"
        />
        {errors.title && <div className="invalid-feedback">{errors.title[0]}</div>}
      </div>

      <div className="mb-3">
        <label className="form-label fw-semibold">Description *</label>
        <textarea
          name="description"
          className={`form-control ${errors.description ? 'is-invalid' : ''}`}
          value={values.description}
          onChange={handleChange}
          rows={6}
          placeholder="Provide detailed information about your issue..."
        />
        {errors.description && <div className="invalid-feedback">{errors.description[0]}</div>}
      </div>

      <div className="row mb-3">
        <div className="col-md-6">
          <label className="form-label fw-semibold">Priority</label>
          <select
            name="priority"
            className="form-select"
            value={values.priority}
            onChange={handleChange}
          >
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div className="col-md-6">
          <label className="form-label fw-semibold">Category</label>
          <select
            name="category_id"
            className="form-select"
            value={values.category_id}
            onChange={handleChange}
          >
            <option value="">Select category...</option>
            {categories.map(cat => (
              <option key={cat.id} value={cat.id}>{cat.name}</option>
            ))}
          </select>
        </div>
      </div>

      <div className="mb-3">
        <label className="form-label fw-semibold">Attachments</label>
        <input
          type="file"
          name="attachments"
          className="form-control"
          onChange={handleChange}
          multiple
          accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip"
        />
        <small className="text-muted">Max 5 files, 10MB each. Accepted: images, PDF, Word, text, ZIP.</small>
      </div>

      <button
        type="submit"
        className="btn btn-primary"
        disabled={loading || submitLoading}
      >
        {(loading || submitLoading) ? (
          <>
            <span className="spinner-border spinner-border-sm me-2" />
            Submitting...
          </>
        ) : (
          'Submit Ticket'
        )}
      </button>
    </form>
  );
}

export default TicketForm;
