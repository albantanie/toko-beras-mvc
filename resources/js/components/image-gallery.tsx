import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Icons } from '@/utils/formatters';

interface ImageGalleryProps {
    images: string[];
    currentImage?: string;
    onImageSelect?: (image: string) => void;
    showThumbnails?: boolean;
}

export default function ImageGallery({ 
    images, 
    currentImage, 
    onImageSelect,
    showThumbnails = true 
}: ImageGalleryProps) {
    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleImageClick = (image: string) => {
        if (onImageSelect) {
            onImageSelect(image);
        } else {
            setSelectedImage(image);
            setIsModalOpen(true);
        }
    };

    const getImageUrl = (imagePath: string, thumbnail = false) => {
        if (thumbnail && imagePath.includes('beras-beras/')) {
            // Generate thumbnail path
            const parts = imagePath.split('/');
            const filename = parts.pop();
            return `/storage/beras-beras/thumbnails/${filename}`;
        }
        return `/storage/${imagePath}`;
    };

    const getImageName = (imagePath: string) => {
        const filename = imagePath.split('/').pop() || '';
        return filename.replace(/\.(webp|jpg|jpeg|png)$/i, '').replace(/[-_]/g, ' ');
    };

    if (images.length === 0) {
        return (
            <div className="text-center py-8">
                <Icons.package className="mx-auto h-12 w-12 text-gray-400" />
                <p className="mt-2 text-sm text-gray-500">No images available</p>
            </div>
        );
    }

    return (
        <>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                {images.map((image, index) => (
                    <div
                        key={index}
                        className={`relative group cursor-pointer rounded-lg overflow-hidden border-2 transition-all ${
                            currentImage === image 
                                ? 'border-green-500 ring-2 ring-green-200' 
                                : 'border-gray-200 hover:border-gray-300'
                        }`}
                        onClick={() => handleImageClick(image)}
                    >
                        <div className="aspect-square">
                            <img
                                src={getImageUrl(image, showThumbnails)}
                                alt={getImageName(image)}
                                className="w-full h-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        
                        {/* Overlay */}
                        <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                            <Icons.view className="h-6 w-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>

                        {/* Image name */}
                        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-2">
                            <p className="text-white text-xs truncate">
                                {getImageName(image)}
                            </p>
                        </div>

                        {/* Selected indicator */}
                        {currentImage === image && (
                            <div className="absolute top-2 right-2">
                                <div className="bg-green-500 text-white rounded-full p-1">
                                    <Icons.check className="h-3 w-3" />
                                </div>
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* Image Modal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedImage ? getImageName(selectedImage) : 'Image Preview'}
                        </DialogTitle>
                    </DialogHeader>
                    
                    {selectedImage && (
                        <div className="space-y-4">
                            <div className="flex justify-center">
                                <img
                                    src={getImageUrl(selectedImage)}
                                    alt={getImageName(selectedImage)}
                                    className="max-w-full max-h-96 object-contain rounded-lg"
                                />
                            </div>
                            
                            <div className="flex justify-between items-center text-sm text-gray-500">
                                <span>Path: {selectedImage}</span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        navigator.clipboard.writeText(getImageUrl(selectedImage));
                                    }}
                                >
                                    <Icons.copy className="h-4 w-4 mr-2" />
                                    Copy URL
                                </Button>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

// Available rice product images for easy reference
export const AVAILABLE_RICE_IMAGES = [
    'beras-beras/1687423132-beras merah.webp',
    'beras-beras/ber-00_76a2a221-48f5-4a33-93c4-0632bcca1e74_1200x1200.webp',
    'beras-beras/berasmerah.webp',
    'beras-beras/download.jpeg',
    'beras-beras/images (1).jpeg',
    'beras-beras/images.jpeg',
    'beras-beras/niat_zakat_fitrah_1682047693.webp',
    'beras-beras/rice-3997767_1920.jpg'
];

// Image categories for better organization
export const IMAGE_CATEGORIES = {
    'Red Rice': [
        'beras-beras/1687423132-beras merah.webp',
        'beras-beras/berasmerah.webp'
    ],
    'Premium Rice': [
        'beras-beras/ber-00_76a2a221-48f5-4a33-93c4-0632bcca1e74_1200x1200.webp',
        'beras-beras/rice-3997767_1920.jpg'
    ],
    'General Rice': [
        'beras-beras/download.jpeg',
        'beras-beras/images (1).jpeg',
        'beras-beras/images.jpeg'
    ],
    'Special Purpose': [
        'beras-beras/niat_zakat_fitrah_1682047693.webp'
    ]
};
