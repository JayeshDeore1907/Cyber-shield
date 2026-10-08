# CyberShield — Render Deployment

## Deploy to Render
1. Upload the contents of this folder to a GitHub repository.
2. In Render: New → Web Service → select the GitHub repository.
3. Runtime/Language: Docker.
4. Branch: main.
5. Root Directory: leave blank.
6. Click Create Web Service.

The included Dockerfile starts the PHP app on 0.0.0.0 using Render's PORT environment variable (default 10000).

Demo admin: admin / admin123
