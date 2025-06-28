#!/bin/bash

# ===================================================================
# DEPLOYMENT SCRIPT UNTUK APLIKASI TOKO BERAS
# ===================================================================
#
# Script ini menyediakan interface yang mudah untuk mengelola
# deployment aplikasi Toko Beras menggunakan Docker
#
# Fitur yang tersedia:
# - start: Menjalankan aplikasi
# - stop: Menghentikan aplikasi
# - restart: Restart aplikasi
# - logs: Melihat logs aplikasi
# - status: Cek status aplikasi
#
# Penggunaan: ./deploy.sh [start|stop|restart|logs|status]
# ===================================================================

# Keluar jika ada error (fail-fast approach)
set -e

# ===================================================================
# KONFIGURASI DEPLOYMENT
# ===================================================================
IMAGE_NAME="toko-beras:latest"    # Nama Docker image
CONTAINER_NAME="toko-beras-app"   # Nama container yang akan dibuat
PORT="8080"                       # Port yang akan di-expose

# ===================================================================
# KONFIGURASI WARNA OUTPUT
# ===================================================================
# Warna untuk output yang lebih mudah dibaca
RED='\033[0;31m'      # Merah untuk error
GREEN='\033[0;32m'    # Hijau untuk success
YELLOW='\033[1;33m'   # Kuning untuk warning
BLUE='\033[0;34m'     # Biru untuk info
NC='\033[0m'          # No Color (reset)

# ===================================================================
# UTILITY FUNCTIONS
# ===================================================================

# Function untuk menampilkan pesan informasi
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Function untuk menampilkan pesan sukses
print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Function untuk menampilkan pesan warning
print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Function untuk menampilkan pesan error
print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ===================================================================
# VALIDATION FUNCTIONS
# ===================================================================

# Cek apakah Docker sudah terinstall dan bisa diakses
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed or not in PATH"
        exit 1
    fi
}

# Cek apakah Docker image sudah tersedia
check_image() {
    if ! docker image inspect $IMAGE_NAME &> /dev/null; then
        print_error "Docker image $IMAGE_NAME not found"
        print_status "Please build the image first with: docker build -t $IMAGE_NAME ."
        exit 1
    fi
}

start_container() {
    print_status "Starting Toko Beras application..."
    
    # Stop existing container if running
    if docker ps -q -f name=$CONTAINER_NAME | grep -q .; then
        print_warning "Container $CONTAINER_NAME is already running. Stopping it first..."
        docker stop $CONTAINER_NAME
        docker rm $CONTAINER_NAME
    fi
    
    # Remove stopped container if exists
    if docker ps -aq -f name=$CONTAINER_NAME | grep -q .; then
        print_status "Removing existing container..."
        docker rm $CONTAINER_NAME
    fi
    
    # Start new container
    docker run -d \
        --name $CONTAINER_NAME \
        -p $PORT:80 \
        -e APP_ENV=production \
        -e APP_DEBUG=false \
        -e APP_URL=http://localhost:$PORT \
        --restart unless-stopped \
        $IMAGE_NAME
    
    print_success "Container started successfully!"
    print_status "Application is available at: http://localhost:$PORT"
    
    # Wait for health check
    print_status "Waiting for application to be ready..."
    sleep 10
    
    if curl -f http://localhost:$PORT/health &> /dev/null; then
        print_success "Application is healthy and ready!"
    else
        print_warning "Application may still be starting up. Check logs with: $0 logs"
    fi
}

stop_container() {
    print_status "Stopping Toko Beras application..."
    
    if docker ps -q -f name=$CONTAINER_NAME | grep -q .; then
        docker stop $CONTAINER_NAME
        docker rm $CONTAINER_NAME
        print_success "Container stopped and removed successfully!"
    else
        print_warning "Container $CONTAINER_NAME is not running"
    fi
}

restart_container() {
    print_status "Restarting Toko Beras application..."
    stop_container
    sleep 2
    start_container
}

show_logs() {
    if docker ps -q -f name=$CONTAINER_NAME | grep -q .; then
        print_status "Showing logs for $CONTAINER_NAME (Press Ctrl+C to exit)..."
        docker logs -f $CONTAINER_NAME
    else
        print_error "Container $CONTAINER_NAME is not running"
        exit 1
    fi
}

show_status() {
    print_status "Checking Toko Beras application status..."
    
    if docker ps -q -f name=$CONTAINER_NAME | grep -q .; then
        print_success "Container is running"
        docker ps -f name=$CONTAINER_NAME --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
        
        # Check health
        if curl -f http://localhost:$PORT/health &> /dev/null; then
            print_success "Application is healthy"
        else
            print_warning "Application health check failed"
        fi
    else
        print_warning "Container is not running"
    fi
    
    # Show image info
    if docker image inspect $IMAGE_NAME &> /dev/null; then
        print_status "Image information:"
        docker images $IMAGE_NAME --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"
    fi
}

# Main script
case "${1:-}" in
    start)
        check_docker
        check_image
        start_container
        ;;
    stop)
        check_docker
        stop_container
        ;;
    restart)
        check_docker
        check_image
        restart_container
        ;;
    logs)
        check_docker
        show_logs
        ;;
    status)
        check_docker
        show_status
        ;;
    *)
        echo "Toko Beras Deployment Script"
        echo ""
        echo "Usage: $0 {start|stop|restart|logs|status}"
        echo ""
        echo "Commands:"
        echo "  start   - Start the Toko Beras application"
        echo "  stop    - Stop the Toko Beras application"
        echo "  restart - Restart the Toko Beras application"
        echo "  logs    - Show application logs"
        echo "  status  - Show application status"
        echo ""
        echo "Example:"
        echo "  $0 start    # Start the application"
        echo "  $0 status   # Check if application is running"
        echo "  $0 logs     # View application logs"
        exit 1
        ;;
esac
