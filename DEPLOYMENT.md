# OnlyMatch App Deployment Guide

This application consists of a React frontend and PHP backend API, containerized using Docker.

## Architecture

- **Frontend**: React app served by nginx
- **Backend**: PHP API using SQLite database
- **Web Server**: nginx as reverse proxy
- **Process Manager**: Supervisor to manage nginx and PHP-FPM

## Local Development

1. Build and run with Docker Compose:
   ```bash
   docker-compose up --build
   ```

2. Access the app at http://localhost:9080

## Production Deployment

The GitHub Actions workflow automatically:
1. Builds a Docker image containing both frontend and backend
2. Pushes it to GitHub Container Registry
3. Deploys to your server using docker-compose

### Required GitHub Secrets

- `DEPLOY_HOST`: Your server hostname/IP
- `DEPLOY_USER`: SSH username
- `DEPLOY_PORT`: SSH port (default 22)
- `DEPLOY_SSH_KEY`: Private SSH key for authentication
- `GHCR_USER`: GitHub username for container registry
- `GHCR_PAT`: GitHub Personal Access Token with `packages:write` permission

## API Endpoints

The PHP backend provides these endpoints:

- `GET /api/profiles` - List all profiles
- `GET /api/profiles/{id}` - Get specific profile
- `POST /api/profiles` - Create profile (admin only)
- `DELETE /api/profiles/{id}` - Delete profile (admin only)
- `POST /api/login` - Admin login
- `POST /api/logout` - Admin logout
- `GET /api/me` - Check auth status

## Data Persistence

The following directories are persisted:
- `./data/uploads` - User uploaded images
- `./data/db.sqlite` - SQLite database

## Environment Variables

You can customize PHP settings in docker-compose.yml:
- `PHP_MEMORY_LIMIT` - Default: 256M
- `PHP_MAX_UPLOAD_SIZE` - Default: 10M
- `PHP_MAX_POST_SIZE` - Default: 10M

## Troubleshooting

1. **404 errors on API calls**: Ensure the backend is running and nginx is properly configured
2. **Database errors**: Check permissions on the SQLite file and its directory
3. **Upload errors**: Verify the uploads directory exists and has proper permissions