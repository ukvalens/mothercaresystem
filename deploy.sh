#!/bin/bash

echo "🏥 Deploying Maternal Care System..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "⚠️  Creating .env from template..."
    cp .env.example .env
    echo "📝 Please edit .env file with your configuration"
fi

# Build and start containers
echo "🐳 Building Docker containers..."
docker-compose build

echo "🚀 Starting services..."
docker-compose up -d

# Wait for database
echo "⏳ Waiting for database to be ready..."
sleep 10

# Initialize database
echo "🗄️  Initializing database..."
docker-compose exec web php app/config/create_tables.php

echo "✅ Deployment complete!"
echo "🌐 Access your application at: http://localhost:8080"
echo "🗄️  Database accessible at: localhost:3306"