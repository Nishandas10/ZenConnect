import { NavLink } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { FiHome, FiInbox, FiPlusCircle, FiGrid, FiUsers, FiSettings } from 'react-icons/fi';

function Sidebar() {
  const { isAdmin, isAgent } = useAuth();

  const navItems = [
    { to: '/', icon: <FiHome />, label: 'Dashboard', roles: ['admin', 'agent'] },
    { to: '/tickets', icon: <FiInbox />, label: 'Tickets', roles: ['admin', 'agent', 'user'] },
    { to: '/tickets/create', icon: <FiPlusCircle />, label: 'New Ticket', roles: ['admin', 'agent', 'user'] },
    { to: '/categories', icon: <FiGrid />, label: 'Categories', roles: ['admin'] },
    { to: '/agents', icon: <FiUsers />, label: 'Agents', roles: ['admin'] },
  ];

  const filteredItems = navItems.filter(item => {
    if (isAdmin) return item.roles.includes('admin');
    if (isAgent) return item.roles.includes('agent');
    return item.roles.includes('user');
  });

  return (
    <div className="sidebar bg-dark text-white" style={{ width: '250px', minHeight: '100vh' }}>
      <div className="p-3 border-bottom border-secondary">
        <h5 className="mb-0 fw-bold text-primary">
          <FiSettings className="me-2" />
          ZenConnect
        </h5>
      </div>
      <nav className="p-2">
        {filteredItems.map(item => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.to === '/'}
            className={({ isActive }) =>
              `d-flex align-items-center p-2 rounded text-decoration-none mb-1 ${
                isActive ? 'bg-primary text-white' : 'text-white-50 hover-bg-secondary'
              }`
            }
          >
            <span className="me-3" style={{ fontSize: '1.2rem' }}>{item.icon}</span>
            {item.label}
          </NavLink>
        ))}
      </nav>
    </div>
  );
}

export default Sidebar;
