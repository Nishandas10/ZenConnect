import { useState, useEffect } from 'react';
import { categoriesAPI } from '../../services/api';
import { useAuth } from '../../hooks/useAuth';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit2, FiTrash2 } from 'react-icons/fi';

function Categories() {
  const { isAdmin } = useAuth();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [form, setForm] = useState({ name: '', slug: '', description: '', is_active: true });

  const fetchCategories = async () => {
    try {
      const response = await categoriesAPI.getAll();
      setCategories(response.data.data || []);
    } catch {
      toast.error('Failed to load categories');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchCategories(); }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editingId) {
        await categoriesAPI.update(editingId, form);
        toast.success('Category updated');
      } else {
        await categoriesAPI.create(form);
        toast.success('Category created');
      }
      setShowForm(false);
      setEditingId(null);
      setForm({ name: '', slug: '', description: '', is_active: true });
      fetchCategories();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to save category');
    }
  };

  const handleEdit = (category) => {
    setForm({ name: category.name, slug: category.slug, description: category.description || '', is_active: category.is_active });
    setEditingId(category.id);
    setShowForm(true);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure?')) return;
    try {
      await categoriesAPI.delete(id);
      toast.success('Category deleted');
      fetchCategories();
    } catch {
      toast.error('Failed to delete category');
    }
  };

  const generateSlug = (name) => {
    return name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  };

  if (loading) {
    return <div className="text-center py-5"><div className="spinner-border text-primary" /></div>;
  }

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h4 className="fw-bold mb-0">Categories</h4>
        {isAdmin && (
          <button className="btn btn-primary btn-sm" onClick={() => { setShowForm(!showForm); setEditingId(null); setForm({ name: '', slug: '', description: '', is_active: true }); }}>
            <FiPlus className="me-1" /> Add Category
          </button>
        )}
      </div>

      {showForm && isAdmin && (
        <div className="card mb-4">
          <div className="card-body">
            <h6 className="fw-bold mb-3">{editingId ? 'Edit Category' : 'New Category'}</h6>
            <form onSubmit={handleSubmit}>
              <div className="row g-3">
                <div className="col-md-4">
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Name"
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value, slug: generateSlug(e.target.value) })}
                    required
                  />
                </div>
                <div className="col-md-3">
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Slug"
                    value={form.slug}
                    onChange={(e) => setForm({ ...form, slug: e.target.value })}
                    required
                  />
                </div>
                <div className="col-md-3">
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Description"
                    value={form.description}
                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                  />
                </div>
                <div className="col-md-2 d-flex gap-2">
                  <button type="submit" className="btn btn-primary btn-sm">Save</button>
                  <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => { setShowForm(false); setEditingId(null); }}>Cancel</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

      <div className="card">
        <div className="table-responsive">
          <table className="table table-hover mb-0">
            <thead className="table-light">
              <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Tickets</th>
                <th>Status</th>
                {isAdmin && <th>Actions</th>}
              </tr>
            </thead>
            <tbody>
              {categories.map(cat => (
                <tr key={cat.id}>
                  <td className="fw-semibold">{cat.name}</td>
                  <td className="text-muted">{cat.slug}</td>
                  <td>{cat.description || '—'}</td>
                  <td>{cat.tickets_count || 0}</td>
                  <td>
                    <span className={`badge ${cat.is_active ? 'bg-success' : 'bg-secondary'}`}>
                      {cat.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  {isAdmin && (
                    <td>
                      <button className="btn btn-outline-primary btn-sm me-1" onClick={() => handleEdit(cat)}>
                        <FiEdit2 />
                      </button>
                      <button className="btn btn-outline-danger btn-sm" onClick={() => handleDelete(cat.id)}>
                        <FiTrash2 />
                      </button>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export default Categories;
