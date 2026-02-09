from datetime import datetime, timedelta
import os

import bcrypt
import jwt
from flask import Flask, jsonify, request
from flask_cors import CORS
from flask_sqlalchemy import SQLAlchemy


db = SQLAlchemy()


def create_app():
    app = Flask(__name__)
    CORS(app)

    app.config["SQLALCHEMY_DATABASE_URI"] = os.getenv(
        "DATABASE_URL", "sqlite:///app.db"
    )
    app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False
    app.config["JWT_SECRET"] = os.getenv("JWT_SECRET", "dev-secret-change-me")
    app.config["JWT_EXP_MINUTES"] = int(os.getenv("JWT_EXP_MINUTES", "43200"))

    db.init_app(app)

    class User(db.Model):
        __tablename__ = "users"
        id = db.Column(db.String, primary_key=True)
        email = db.Column(db.String, unique=True, nullable=False)
        password_hash = db.Column(db.String, nullable=False)
        role = db.Column(db.String, default="child")
        name = db.Column(db.String)
        created_at = db.Column(db.DateTime, default=datetime.utcnow)

    class UserPreferences(db.Model):
        __tablename__ = "user_preferences"
        id = db.Column(db.Integer, primary_key=True, autoincrement=True)
        user_id = db.Column(db.String, nullable=False)
        interests = db.Column(db.Text, default="")
        sub_interests = db.Column(db.Text, default="")
        created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def generate_token(user_id: str, email: str):
        payload = {
            "sub": user_id,
            "email": email,
            "exp": datetime.utcnow() + timedelta(minutes=app.config["JWT_EXP_MINUTES"]),
        }
        return jwt.encode(payload, app.config["JWT_SECRET"], algorithm="HS256")

    def require_auth():
        auth_header = request.headers.get("Authorization", "")
        if not auth_header.startswith("Bearer "):
            return None
        token = auth_header.replace("Bearer ", "")
        try:
            payload = jwt.decode(token, app.config["JWT_SECRET"], algorithms=["HS256"])
            return payload
        except jwt.PyJWTError:
            return None

    with app.app_context():
        db.create_all()

    @app.get("/health")
    def health():
        return jsonify({"status": "ok"})

    @app.post("/api/auth/register")
    def register():
        data = request.get_json(silent=True) or {}
        name = (data.get("name") or "").strip()
        email = (data.get("email") or "").strip().lower()
        password = data.get("password") or ""

        if not email or not password:
            return jsonify({"success": False, "message": "Email and password required"}), 400

        if User.query.filter_by(email=email).first():
            return jsonify({"success": False, "message": "Email already exists"}), 409

        password_hash = bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")
        user = User(id=os.urandom(16).hex(), email=email, password_hash=password_hash, name=name)
        db.session.add(user)
        db.session.commit()

        token = generate_token(user.id, user.email)
        return jsonify({
            "success": True,
            "message": "Account created",
            "token": token,
            "userId": user.id,
            "name": user.name,
            "email": user.email,
            "role": user.role,
        })

    @app.post("/api/auth/login")
    def login():
        data = request.get_json(silent=True) or {}
        email = (data.get("email") or "").strip().lower()
        password = data.get("password") or ""

        if not email or not password:
            return jsonify({"success": False, "message": "Email and password required"}), 400

        user = User.query.filter_by(email=email).first()
        if not user:
            return jsonify({"success": False, "message": "Invalid credentials"}), 401

        if not bcrypt.checkpw(password.encode("utf-8"), user.password_hash.encode("utf-8")):
            return jsonify({"success": False, "message": "Invalid credentials"}), 401

        token = generate_token(user.id, user.email)
        return jsonify({
            "success": True,
            "message": "Login success",
            "token": token,
            "userId": user.id,
            "name": user.name,
            "email": user.email,
            "role": user.role,
        })

    @app.post("/api/users/<user_id>/role")
    def update_role(user_id):
        payload = require_auth()
        if payload is None or payload.get("sub") != user_id:
            return jsonify({"success": False, "message": "Unauthorized"}), 401

        data = request.get_json(silent=True) or {}
        role = (data.get("role") or "").strip().lower()
        if role not in {"child", "adult", "parent"}:
            return jsonify({"success": False, "message": "Invalid role"}), 400

        user = User.query.filter_by(id=user_id).first()
        if not user:
            return jsonify({"success": False, "message": "User not found"}), 404

        user.role = role
        db.session.commit()

        return jsonify({
            "success": True,
            "message": "Role updated",
            "role": user.role,
        })

    @app.post("/api/users/<user_id>/preferences")
    def save_preferences(user_id):
        payload = require_auth()
        if payload is None or payload.get("sub") != user_id:
            return jsonify({"success": False, "message": "Unauthorized"}), 401

        data = request.get_json(silent=True) or {}
        interests = data.get("interests") or []
        sub_interests = data.get("sub_interests") or []

        user = User.query.filter_by(id=user_id).first()
        if not user:
            return jsonify({"success": False, "message": "User not found"}), 404

        record = UserPreferences.query.filter_by(user_id=user_id).first()
        if record is None:
            record = UserPreferences(
                user_id=user_id,
                interests=",".join(interests),
                sub_interests=",".join(sub_interests),
            )
            db.session.add(record)
        else:
            record.interests = ",".join(interests)
            record.sub_interests = ",".join(sub_interests)
        db.session.commit()

        return jsonify({
            "success": True,
            "message": "Preferences saved",
        })

    @app.get("/api/leaderboard")
    def leaderboard():
        data = [
            {"rank": 1, "name": "Anaya R.", "points": 2450},
            {"rank": 2, "name": "You", "points": 0},
            {"rank": 3, "name": "Rahul K.", "points": 1650},
            {"rank": 4, "name": "Meera S.", "points": 1280},
            {"rank": 5, "name": "Jacob T.", "points": 980},
        ]
        return jsonify({"success": True, "items": data})

    return app


app = create_app()

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False, use_reloader=False)
