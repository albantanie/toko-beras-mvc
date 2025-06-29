#!/bin/bash

# ===================================================================
# DEPLOYMENT SCRIPT FOR TOKO BERAS - GCP COMPUTE ENGINE
# ===================================================================
#
# This script will:
# 1. Build Docker image
# 2. Tag and push to Google Container Registry
# 3. Deploy to GCP Compute Engine
# ===================================================================

set -e

# Configuration
PROJECT_ID="your-gcp-project-id"  # Replace with your GCP project ID
IMAGE_NAME="toko-beras-app"
TAG="latest"
REGION="asia-southeast1"  # Singapore region
ZONE="asia-southeast1-a"
INSTANCE_NAME="toko-beras-instance"
MACHINE_TYPE="e2-medium"  # 2 vCPU, 4 GB RAM
DISK_SIZE="20GB"
REGISTRY=""  # Add your registry here if using one

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Starting deployment process...${NC}"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

# Build the Docker image
echo -e "${YELLOW}📦 Building Docker image...${NC}"
docker build -t ${IMAGE_NAME}:${TAG} .

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Docker build failed!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker image built successfully!${NC}"

# Save the image as tar file
echo -e "${YELLOW}💾 Saving Docker image to tar file...${NC}"
docker save -o ${IMAGE_NAME}-${TAG}.tar ${IMAGE_NAME}:${TAG}

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to save Docker image!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker image saved as ${IMAGE_NAME}-${TAG}.tar${NC}"

# Create deployment instructions
cat > DEPLOYMENT_INSTRUCTIONS.md << EOF
# Deployment Instructions

## Local Testing
To test the application locally:

\`\`\`bash
# Run with docker-compose
docker-compose up -d

# Or run directly with Docker
docker run -d -p 8080:80 --name toko-beras ${IMAGE_NAME}:${TAG}
\`\`\`

## VPS Deployment

1. **Transfer the image to your VPS:**
   \`\`\`bash
   # From your local machine, copy the tar file to VPS
   scp ${IMAGE_NAME}-${TAG}.tar user@your-vps-ip:/path/to/destination/
   \`\`\`

2. **On your VPS, load the image:**
   \`\`\`bash
   docker load -i ${IMAGE_NAME}-${TAG}.tar
   \`\`\`

3. **Run the container:**
   \`\`\`bash
   # Basic run
   docker run -d -p 80:80 --name toko-beras ${IMAGE_NAME}:${TAG}
   
   # With environment variables
   docker run -d -p 80:80 \\
     -e APP_ENV=production \\
     -e APP_DEBUG=false \\
     -e DB_CONNECTION=mysql \\
     -e DB_HOST=your-db-host \\
     -e DB_PORT=3306 \\
     -e DB_DATABASE=your-database \\
     -e DB_USERNAME=your-username \\
     -e DB_PASSWORD=your-password \\
     --name toko-beras ${IMAGE_NAME}:${TAG}
   
   # With persistent storage
   docker run -d -p 80:80 \\
     -v /path/to/storage:/var/www/storage \\
     -v /path/to/cache:/var/www/bootstrap/cache \\
     --name toko-beras ${IMAGE_NAME}:${TAG}
   \`\`\`

4. **Check if the container is running:**
   \`\`\`bash
   docker ps
   docker logs toko-beras
   \`\`\`

5. **Access the application:**
   - Local: http://localhost:8080
   - VPS: http://your-vps-ip

## Environment Variables

Make sure to set these environment variables in your VPS:

- \`APP_ENV=production\`
- \`APP_DEBUG=false\`
- \`APP_KEY=your-app-key\`
- \`DB_CONNECTION=mysql\` (or your preferred database)
- \`DB_HOST=your-database-host\`
- \`DB_PORT=3306\`
- \`DB_DATABASE=your-database-name\`
- \`DB_USERNAME=your-database-username\`
- \`DB_PASSWORD=your-database-password\`

## Database Setup

If you need to run migrations on the VPS:

\`\`\`bash
docker exec -it toko-beras php artisan migrate --force
\`\`\`

## Troubleshooting

1. **Check container logs:**
   \`\`\`bash
   docker logs toko-beras
   \`\`\`

2. **Access container shell:**
   \`\`\`bash
   docker exec -it toko-beras bash
   \`\`\`

3. **Check health endpoint:**
   \`\`\`bash
   curl http://localhost/health
   \`\`\`
EOF

echo -e "${GREEN}✅ Deployment instructions created in DEPLOYMENT_INSTRUCTIONS.md${NC}"
echo -e "${GREEN}🎉 Deployment package ready!${NC}"
echo -e "${YELLOW}📋 Next steps:${NC}"
echo -e "   1. Copy ${IMAGE_NAME}-${TAG}.tar to your VPS"
echo -e "   2. Follow the instructions in DEPLOYMENT_INSTRUCTIONS.md"
echo -e "   3. Run the container on your VPS"

# ===================================================================
# STEP 1: TAG FOR GOOGLE CONTAINER REGISTRY
# ===================================================================
echo "🏷️ Tagging image for GCR..."
docker tag $IMAGE_NAME:$TAG gcr.io/$PROJECT_ID/$IMAGE_NAME:$TAG

# ===================================================================
# STEP 2: PUSH TO GOOGLE CONTAINER REGISTRY
# ===================================================================
echo "⬆️ Pushing to Google Container Registry..."
docker push gcr.io/$PROJECT_ID/$IMAGE_NAME:$TAG

# ===================================================================
# STEP 4: CREATE COMPUTE ENGINE INSTANCE
# ===================================================================
echo "🖥️ Creating Compute Engine instance..."

# Check if instance already exists
if gcloud compute instances describe $INSTANCE_NAME --zone=$ZONE --project=$PROJECT_ID >/dev/null 2>&1; then
    echo "Instance $INSTANCE_NAME already exists. Stopping and updating..."
    gcloud compute instances stop $INSTANCE_NAME --zone=$ZONE --project=$PROJECT_ID
else
    echo "Creating new instance $INSTANCE_NAME..."
    gcloud compute instances create $INSTANCE_NAME \
        --zone=$ZONE \
        --machine-type=$MACHINE_TYPE \
        --image-family=cos-stable \
        --image-project=cos-cloud \
        --boot-disk-size=$DISK_SIZE \
        --boot-disk-type=pd-standard \
        --tags=http-server,https-server \
        --project=$PROJECT_ID
fi

# ===================================================================
# STEP 5: CONFIGURE FIREWALL RULES
# ===================================================================
echo "🔥 Configuring firewall rules..."

# Create firewall rule for HTTP
gcloud compute firewall-rules create allow-http \
    --allow tcp:80 \
    --target-tags=http-server \
    --source-ranges=0.0.0.0/0 \
    --project=$PROJECT_ID \
    --quiet 2>/dev/null || echo "Firewall rule allow-http already exists"

# Create firewall rule for HTTPS
gcloud compute firewall-rules create allow-https \
    --allow tcp:443 \
    --target-tags=https-server \
    --source-ranges=0.0.0.0/0 \
    --project=$PROJECT_ID \
    --quiet 2>/dev/null || echo "Firewall rule allow-https already exists"

# ===================================================================
# STEP 6: DEPLOY DOCKER CONTAINER
# ===================================================================
echo "🐳 Deploying Docker container..."

# Create docker-compose file on the instance
gcloud compute ssh $INSTANCE_NAME --zone=$ZONE --project=$PROJECT_ID --command="
sudo mkdir -p /opt/toko-beras
cd /opt/toko-beras

# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  toko-beras:
    image: gcr.io/$PROJECT_ID/$IMAGE_NAME:$TAG
    container_name: toko-beras-app
    ports:
      - '80:80'
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=http://$(gcloud compute instances describe $INSTANCE_NAME --zone=$ZONE --project=$PROJECT_ID --format='get(networkInterfaces[0].accessConfigs[0].natIP)')
      - DB_CONNECTION=sqlite
      - DB_DATABASE=/var/www/html/database/database.sqlite
      - CACHE_DRIVER=file
      - SESSION_DRIVER=file
      - QUEUE_CONNECTION=database
    volumes:
      - toko_beras_storage:/var/www/html/storage
      - toko_beras_database:/var/www/html/database
    restart: unless-stopped
    healthcheck:
      test: ['CMD', 'curl', '-f', 'http://localhost/health']
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

volumes:
  toko_beras_storage:
    driver: local
  toko_beras_database:
    driver: local
EOF

# Stop existing container if running
sudo docker-compose down || true

# Pull latest image
sudo docker pull gcr.io/$PROJECT_ID/$IMAGE_NAME:$TAG

# Start the application
sudo docker-compose up -d

# Wait for container to be healthy
echo 'Waiting for application to be ready...'
for i in {1..30}; do
    if sudo docker-compose ps | grep -q 'Up'; then
        echo 'Application is running!'
        break
    fi
    sleep 2
done

# Show container status
sudo docker-compose ps
"

# ===================================================================
# STEP 7: GET INSTANCE IP
# ===================================================================
INSTANCE_IP=$(gcloud compute instances describe $INSTANCE_NAME --zone=$ZONE --project=$PROJECT_ID --format='get(networkInterfaces[0].accessConfigs[0].natIP)')

echo "✅ Deployment completed!"
echo "🌐 Application URL: http://$INSTANCE_IP"
echo "🔍 Health check: http://$INSTANCE_IP/health"
echo ""
echo "📋 Next steps:"
echo "1. Wait 2-3 minutes for the application to fully start"
echo "2. Visit http://$INSTANCE_IP to access the application"
echo "3. Check logs: gcloud compute ssh $INSTANCE_NAME --zone=$ZONE --project=$PROJECT_ID --command='sudo docker-compose logs -f'"
