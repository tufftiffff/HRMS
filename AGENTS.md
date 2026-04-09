# AGENTS.md

Project: HRMS (Laravel 10)

Rules:
- Do not break existing routes, controllers, or views.
- Add face recognition as a new feature (opt-in).
- Employees enroll their own face data.
- Attendance check-in uses face recognition + basic liveness.
- Use ArcFace embeddings (InsightFace).
- Laravel must call a Python FastAPI service for face recognition.
- Store face embeddings in database (JSON/BLOB).
- Raw images are optional and must be protected.
- All employee routes require auth middleware.
- Log all face enrollment and attendance attempts.
