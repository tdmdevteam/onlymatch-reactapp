#!/bin/bash
set -e

echo "Building and starting the application..."
docker compose down 2>/dev/null || true
docker compose build
docker compose up -d

echo "Waiting for services to start..."
sleep 5

echo "Testing frontend..."
curl -s -o /dev/null -w "Frontend HTTP Status: %{http_code}\n" http://localhost:9080/

echo "Testing API endpoints..."
curl -s -w "\nAPI /api/profiles Status: %{http_code}\n" http://localhost:9080/api/profiles

echo ""
echo "✅ Setup complete! Access the app at http://localhost:9080"
echo "To view logs: docker-compose logs -f"
echo "To stop: docker-compose down"

