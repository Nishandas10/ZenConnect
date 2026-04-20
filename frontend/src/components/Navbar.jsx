import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useNotifications } from '../hooks/useNotifications';
import { FiBell, FiLogOut, FiUser, FiCheck } from 'react-icons/fi';

function Navbar() {
  const { user, logout } = useAuth();
  const { notifications, unreadCount, markAsRead, markAllAsRead } = useNotifications();
  const [showNotifications, setShowNotifications] = useState(false);
  const [showUserMenu, setShowUserMenu] = useState(false);
  const navigate = useNavigate();
  const userMenuRef = useRef(null);

  // Close user menu when clicking outside
  useEffect(() => {
    const handler = (e) => {
      if (userMenuRef.current && !userMenuRef.current.contains(e.target)) {
        setShowUserMenu(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const handleNotificationClick = async (notification) => {
    if (!notification.read_at) {
      await markAsRead(notification.id);
    }
    if (notification.data?.ticket_id) {
      navigate(`/tickets/${notification.data.ticket_id}`);
    }
    setShowNotifications(false);
  };

  return (
    <nav className="navbar navbar-expand navbar-light bg-white border-bottom px-4 shadow-sm">
      <div className="container-fluid">
        <span className="navbar-text fw-semibold">
          Welcome, {user?.name}
          <span className="badge bg-secondary ms-2 text-capitalize">{user?.role}</span>
        </span>

        <div className="d-flex align-items-center gap-3">
          {/* Notifications */}
          <div className="position-relative">
            <button
              className="btn btn-link text-dark position-relative p-1"
              onClick={() => setShowNotifications(!showNotifications)}
            >
              <FiBell size={20} />
              {unreadCount > 0 && (
                <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style={{ fontSize: '0.65rem' }}>
                  {unreadCount}
                </span>
              )}
            </button>

            {showNotifications && (
              <div className="position-absolute end-0 mt-2 bg-white border rounded shadow-lg" style={{ width: '360px', maxHeight: '400px', overflowY: 'auto', zIndex: 1050 }}>
                <div className="d-flex justify-content-between align-items-center p-3 border-bottom">
                  <h6 className="mb-0">Notifications</h6>
                  {unreadCount > 0 && (
                    <button className="btn btn-sm btn-link" onClick={markAllAsRead}>
                      <FiCheck className="me-1" /> Mark all read
                    </button>
                  )}
                </div>
                {notifications.length === 0 ? (
                  <p className="text-muted text-center p-3 mb-0">No notifications</p>
                ) : (
                  notifications.slice(0, 10).map(n => (
                    <div
                      key={n.id}
                      className={`p-3 border-bottom cursor-pointer ${!n.read_at ? 'bg-light' : ''}`}
                      style={{ cursor: 'pointer' }}
                      onClick={() => handleNotificationClick(n)}
                    >
                      <p className="mb-1 small">{n.data?.message || 'Notification'}</p>
                      <small className="text-muted">
                        {new Date(n.created_at).toLocaleDateString()}
                      </small>
                    </div>
                  ))
                )}
              </div>
            )}
          </div>

          {/* User Menu */}
          <div className="position-relative" ref={userMenuRef}>
            <button
              className="btn btn-outline-secondary d-flex align-items-center gap-2"
              onClick={() => setShowUserMenu(!showUserMenu)}
            >
              <FiUser /> {user?.name}
            </button>
            {showUserMenu && (
              <ul className="dropdown-menu dropdown-menu-end show position-absolute" style={{ right: 0, top: '100%', marginTop: '4px' }}>
                <li>
                  <button className="dropdown-item text-danger" onClick={handleLogout}>
                    <FiLogOut className="me-2" /> Logout
                  </button>
                </li>
              </ul>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
}

export default Navbar;
