import React from 'react';
import { Head } from '@inertiajs/react';
import { PageProps, Barang } from '@/types';
import { ProductImage } from '@/utils/formatters';

interface TestImagesProps extends PageProps {
    barangs: Barang[];
}

export default function TestImages({ barangs }: TestImagesProps) {
    return (
        <>
            <Head title="Test Product Images" />
            
            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900">Test Product Images</h1>
                        <p className="mt-2 text-gray-600">
                            Verifying that all product images are loading correctly
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {barangs.map((barang) => (
                            <div key={barang.id} className="bg-white rounded-lg shadow-md overflow-hidden">
                                <div className="aspect-square">
                                    <ProductImage
                                        src={barang.gambar}
                                        alt={barang.nama}
                                        className="w-full h-full object-cover"
                                    />
                                </div>
                                
                                <div className="p-4">
                                    <h3 className="font-semibold text-gray-900 mb-2">
                                        {barang.nama}
                                    </h3>
                                    
                                    <div className="space-y-1 text-sm text-gray-600">
                                        <p><strong>Image Path:</strong> {barang.gambar || 'No image'}</p>
                                        <p><strong>Category:</strong> {barang.kategori}</p>
                                        <p><strong>Stock:</strong> {barang.stok} {barang.satuan}</p>
                                    </div>

                                    {barang.gambar && (
                                        <div className="mt-3">
                                            <a 
                                                href={`/storage/${barang.gambar}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 hover:text-blue-800 text-sm"
                                            >
                                                View Full Image →
                                            </a>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    {barangs.length === 0 && (
                        <div className="text-center py-12">
                            <p className="text-gray-500 text-lg">No products found</p>
                        </div>
                    )}

                    <div className="mt-8 bg-white rounded-lg shadow p-6">
                        <h2 className="text-xl font-semibold mb-4">Image Statistics</h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div className="text-center">
                                <div className="text-2xl font-bold text-green-600">
                                    {barangs.length}
                                </div>
                                <div className="text-sm text-gray-600">Total Products</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-blue-600">
                                    {barangs.filter(b => b.gambar).length}
                                </div>
                                <div className="text-sm text-gray-600">With Images</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-orange-600">
                                    {barangs.filter(b => !b.gambar).length}
                                </div>
                                <div className="text-sm text-gray-600">Without Images</div>
                            </div>
                            <div className="text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {Math.round((barangs.filter(b => b.gambar).length / barangs.length) * 100)}%
                                </div>
                                <div className="text-sm text-gray-600">Coverage</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
