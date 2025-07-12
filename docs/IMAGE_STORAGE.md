# 📸 Image Storage System - Toko Beras

## 📁 Directory Structure

```
public/storage/
├── beras-beras/                    # Main product images directory
│   ├── *.webp, *.jpg, *.jpeg      # Original product images
│   └── thumbnails/                 # Thumbnail versions
│       └── *.webp, *.jpg, *.jpeg   # Compressed thumbnails (150x150)
└── payment-proofs/                 # Payment proof uploads
    └── *.jpg, *.png, *.pdf
```

## 🎯 Image Categories

### Product Images (`beras-beras/`)
- **Purpose**: Main product photos for rice varieties
- **Size**: Max 800x600px, compressed to 80% quality
- **Formats**: WEBP (preferred), JPEG, PNG
- **Naming**: Auto-generated unique filenames

### Thumbnails (`beras-beras/thumbnails/`)
- **Purpose**: Small preview images for listings
- **Size**: 150x150px (square crop)
- **Quality**: 70% compression for faster loading
- **Auto-generated**: Created automatically when uploading main image

## 🔧 Technical Implementation

### Image Compression Service
```php
// Default settings for rice product images
$imageService->compressAndStore(
    $file, 
    'beras-beras',    // Directory
    800,              // Max width
    600,              // Max height  
    80                // Quality %
);
```

### Storage Configuration
- **Disk**: `public` (Laravel storage)
- **Path**: `storage/app/public/beras-beras/`
- **URL**: `/storage/beras-beras/filename.webp`
- **Symlink**: `public/storage` → `storage/app/public`

## 📋 Usage Guidelines

### For Developers

1. **Upload New Images**:
   ```php
   // In controller
   if ($request->hasFile('gambar')) {
       $imagePath = $this->imageService->compressAndStore(
           $request->file('gambar'), 
           'beras-beras'
       );
       $this->imageService->createThumbnail($request->file('gambar'));
   }
   ```

2. **Display Images**:
   ```tsx
   // In React component
   <img 
       src={`/storage/${barang.gambar}`} 
       alt={barang.nama}
       className="w-full h-64 object-cover"
   />
   ```

3. **Display Thumbnails**:
   ```tsx
   // For listings/cards
   <img 
       src={`/storage/beras-beras/thumbnails/${filename}`}
       alt={barang.nama}
       className="w-16 h-16 object-cover rounded"
   />
   ```

### For Content Managers

1. **Image Requirements**:
   - **Format**: WEBP, JPEG, or PNG
   - **Size**: Will be auto-resized to 800x600px
   - **File Size**: Max 2MB (will be compressed)
   - **Content**: Clear product photos with good lighting

2. **Best Practices**:
   - Use high-quality source images
   - Ensure good lighting and clear product visibility
   - Avoid watermarks or text overlays
   - Use consistent background/styling

## 🚀 Performance Features

### Automatic Optimization
- **Compression**: Images compressed to optimal size/quality ratio
- **Format**: WEBP preferred for better compression
- **Thumbnails**: Auto-generated for fast loading in lists
- **Lazy Loading**: Implemented in frontend components

### Storage Efficiency
- **Compression**: 80% quality for main images, 70% for thumbnails
- **Size Limits**: Max 800x600 for main images, 150x150 for thumbnails
- **Format Optimization**: WEBP format reduces file size by ~30%

## 🛠️ Maintenance Commands

### Organize Existing Images
```bash
# Check what would be organized (dry run)
php artisan images:organize --dry-run

# Actually organize images
php artisan images:organize
```

### Storage Link
```bash
# Create storage symlink (if not exists)
php artisan storage:link
```

### Clear Storage Cache
```bash
# Clear cached files
php artisan cache:clear
php artisan config:clear
```

## 📊 Current Image Inventory

### Available Rice Product Images:
- `1687423132-beras merah.webp` - Red rice variety
- `ber-00_76a2a221-48f5-4a33-93c4-0632bcca1e74_1200x1200.webp` - Premium rice
- `berasmerah.webp` - Red rice close-up
- `download.jpeg` - Generic rice image
- `images (1).jpeg` - Rice grains
- `images.jpeg` - Rice texture
- `niat_zakat_fitrah_1682047693.webp` - Zakat rice
- `rice-3997767_1920.jpg` - High-quality rice photo

### Thumbnails:
All main images have corresponding thumbnails in `/thumbnails/` directory.

## 🔒 Security Considerations

### File Validation
- **MIME Type**: Validated on upload
- **File Extension**: Restricted to image formats
- **File Size**: Limited to 2MB max
- **Content Scanning**: Basic image validation

### Access Control
- **Public Access**: Images are publicly accessible via URL
- **Upload Permissions**: Only authenticated users with proper roles
- **Directory Protection**: No direct directory listing

## 🎨 Frontend Integration

### ProductImage Component
```tsx
import { ProductImage } from '@/utils/formatters';

<ProductImage
    src={barang.gambar}
    alt={barang.nama}
    className="w-full h-96 object-cover rounded-lg"
/>
```

### Fallback Handling
- Automatic fallback to placeholder if image missing
- Graceful error handling for broken image links
- Loading states for better UX

---

## 📝 Notes

- All new product images will be stored in `beras-beras/` directory
- Thumbnails are automatically generated for performance
- Images are compressed for optimal web delivery
- Storage is organized for easy maintenance and backup
