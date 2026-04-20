You are an expert full-stack senior engineer (Laravel + React and MySQL), systems architect, DevOps engineer, and AI agent designer. You will generate a complete production-ready project.

🧱 TECH STACK SPECIFICATION

Backend: PHP 8+, Laravel 10
Frontend: React, Bootstrap 5
State: React Hooks
HTTP: Axios
Auth: Laravel Sanctum (API + React)
DB: MySQL
Notifications: Laravel Events + Notifications
AI: Gemini API for:

Ticket summarization
AI reply suggestions
📦 REQUIRED OUTPUT (VERY IMPORTANT)

Claude must deliver the following sections in order:

1. System Architecture
Full backend architecture
Frontend architecture
Data flow diagrams
Request → Controller → Service → Resource → React
Event & notification flow
AI service module flow
2. Database Schema (VERY DETAILED)

Define ALL tables:

Users
Tickets
TicketComments
Categories
TicketAttachments
TicketHistory (for logs)
Roles (Admin, Agent, User)

For EACH table include:

Fields + types
Indexes
Foreign keys
Example records
3. Laravel Setup Instructions

Include:

Laravel installation
Breeze install (React)
Sanctum setup
API folder structure
CORS setup
Env configuration
4. Models (all)

Generate full models:

Relationships
Casts
Accessors/Mutators
Query scopes (e.g., scopeOpen, scopeAssignedTo)
5. Full Laravel Migrations

Complete migrations for all tables.
Must be copy-paste ready.

6. Controllers (full CRUD)

Controllers to generate:

TicketController
TicketCommentController
CategoryController
UserController (agent listing)
AdminDashboardController

Every controller must include:

Validation rules (FormRequest classes)
Policies (authorization)
Service layer usage
Return API Resources
7. Service Layer (important)

Create services:

TicketService

Handles:

Create ticket
Assign agent
Status flow (open → in_progress → closed)
Logging to TicketHistory
AIService

Handles:

Summarize ticket
Suggest AI reply
NotificationService

Handles:

Email notification
In-app notification

Give complete code implementation for each service.

8. API Routes (complete)

Include:

/api/tickets
/api/tickets/{id}
/api/tickets/{id}/comments
/api/categories
/api/agents
/api/dashboard
/api/ai/summarize
/api/ai/reply
9. API Resources

Generate:

TicketResource
TicketCommentResource
UserResource

Return formatted JSON schemas.

10. Policies (Authorization)

Policies for:

Ticket update permissions
Only admin can assign ticket
Only agent or owner can comment

Include full policy code.

11. Events & Notifications

Generate events:

TicketCreated
TicketUpdated
CommentAdded

Generate notifications:

NewTicketNotification
TicketStatusChangedNotification

Include:

Mail template
Notification channels
12. React Frontend (VERY DETAILED)

Provide full code for:

Pages
Login / Register
Ticket List (user + agent + admin views)
Ticket Create Page
Ticket Details Page (with comments thread)
Admin Dashboard (charts + metrics)
Components
TicketCard.jsx
TicketForm.jsx
CommentList.jsx
Sidebar.jsx
Navbar.jsx
React Hooks Logic
useFetch
useForm
useAuth
useNotifications
13. React API Integration

Show complete Axios calls for:

Create ticket
Update status
Assign agent
Add comment
Fetch dashboard data
Call AI API endpoints
14. AI Feature Implementation

Generate complete working backend logic:

AI Summarization API

Input: title + description
Output: 3–5 line summary

AI Reply Suggestion API

Input: ticket + previous comments
Output: suggested professional reply

Use OpenAI/Claude API example call.

15. Notification System

Explain with diagrams:

What triggers notifications
Who receives them
How email templates work
16. Dashboard Analytics

Implement admin metrics:

Total tickets
Tickets by status
Tickets by category
Agent performance
Average resolution time

Provide:

SQL queries
Controller logic
React display components

19. Bonus: Add Optional Features

Include code for optional enhancements:

File uploads
Agent workload balancing
SLA timers
Ticket tags
Advanced filters + search
🚫 RULES FOR YOUR ANSWER

