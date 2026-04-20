import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ticketsAPI } from '../../services/api';
import TicketForm from '../../components/TicketForm';
import { toast } from 'react-toastify';

function TicketCreate() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (values) => {
    setLoading(true);
    try {
      const response = await ticketsAPI.create(values);
      toast.success('Ticket created successfully!');
      navigate(`/tickets/${response.data.ticket.id}`);
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to create ticket');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h4 className="fw-bold mb-4">Create New Ticket</h4>
      <div className="card">
        <div className="card-body p-4">
          <TicketForm onSubmit={handleSubmit} loading={loading} />
        </div>
      </div>
    </div>
  );
}

export default TicketCreate;
