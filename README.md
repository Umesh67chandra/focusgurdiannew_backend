# FocusGuardian Backend

Flask REST API backend for the FocusGuardian app - a parental control and focus management application.

## Features

- User authentication (register/login) with JWT tokens
- User role management (child, adult, parent)
- User preferences/interests storage
- Leaderboard API
- SQLite database support
- CORS enabled for mobile app integration

## Setup

### Prerequisites
- Python 3.11+
- pip

### Installation

1. Clone the repository:
```bash
git clone https://github.com/Umesh67chandra/focusgurdiannew_backend.git
cd focusgurdiannew_backend
```

2. Create a virtual environment:
```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

3. Install dependencies:
```bash
pip install -r requirements.txt
```

### Running the Server

```bash
python -u app/main.py
```

The server will start on `http://127.0.0.1:5000`

### Health Check

Test the server:
```bash
curl http://127.0.0.1:5000/health
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user

### User Management
- `POST /api/users/<user_id>/role` - Update user role (requires auth)
- `POST /api/users/<user_id>/preferences` - Save user preferences (requires auth)

### Leaderboard
- `GET /api/leaderboard` - Get leaderboard data

## Environment Variables

- `DATABASE_URL` - SQLite database path (default: `sqlite:///app.db`)
- `JWT_SECRET` - Secret key for JWT signing (default: `dev-secret-change-me`)
- `JWT_EXP_MINUTES` - JWT token expiration in minutes (default: `43200` = 30 days)

## Database

SQLite database is automatically created at `instance/app.db` on first run.

## License

MIT
