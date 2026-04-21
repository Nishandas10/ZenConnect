import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8001/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth API
export const authAPI = {
  register: (data) => api.post('/register', data),
  login: (data) => api.post('/login', data),
  logout: () => api.post('/logout'),
  getUser: () => api.get('/user'),
};

// Tickets API
export const ticketsAPI = {
  getAll: (params) => api.get('/tickets', { params }),
  getOne: (id) => api.get(`/tickets/${id}`),
  create: (data) => {
    const formData = new FormData();
    Object.keys(data).forEach(key => {
      if (key === 'attachments') {
        data[key].forEach(file => formData.append('attachments[]', file));
      } else if (key === 'tags') {
        data[key].forEach(tag => formData.append('tags[]', tag));
      } else {
        formData.append(key, data[key]);
      }
    });
    return api.post('/tickets', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  update: (id, data) => api.put(`/tickets/${id}`, data),
  delete: (id) => api.delete(`/tickets/${id}`),
  assign: (id, agentId) => api.post(`/tickets/${id}/assign`, { agent_id: agentId }),
  autoAssign: (id) => api.post(`/tickets/${id}/auto-assign`),
  selfAssign: (id) => api.post(`/tickets/${id}/self-assign`),
};

// Comments API
export const commentsAPI = {
  getAll: (ticketId, params) => api.get(`/tickets/${ticketId}/comments`, { params }),
  create: (ticketId, data) => api.post(`/tickets/${ticketId}/comments`, data),
};

// Categories API
export const categoriesAPI = {
  getAll: () => api.get('/categories'),
  create: (data) => api.post('/categories', data),
  update: (id, data) => api.put(`/categories/${id}`, data),
  delete: (id) => api.delete(`/categories/${id}`),
};

// Agents API
export const agentsAPI = {
  getAll: () => api.get('/agents'),
};

// Tags API
export const tagsAPI = {
  getAll: () => api.get('/tags'),
  updateTicketTags: (ticketId, tagIds) => api.put(`/tickets/${ticketId}`, { tags: tagIds }),
};

// Dashboard API
export const dashboardAPI = {
  getData: () => api.get('/dashboard'),
};

// AI API
export const aiAPI = {
  summarize: (ticketId) => api.post('/ai/summarize', { ticket_id: ticketId }),
  suggestReply: (ticketId) => api.post('/ai/reply', { ticket_id: ticketId }),
};

// Notifications API
export const notificationsAPI = {
  getAll: (params) => api.get('/notifications', { params }),
  markAsRead: (id) => api.post(`/notifications/${id}/read`),
  markAllAsRead: () => api.post('/notifications/read-all'),
};

export default api;
