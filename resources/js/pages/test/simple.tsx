import React from 'react';
import { Head } from '@inertiajs/react';

interface Props {
    message: string;
    timestamp: string;
}

export default function SimpleTest({ message, timestamp }: Props) {
    return (
        <div>
            <Head title="Tes Sederhana" />
            <div style={{ padding: '20px', fontFamily: 'Arial, sans-serif' }}>
                <h1>Tes Inertia Sederhana</h1>
                <p><strong>Pesan:</strong> {message}</p>
                <p><strong>Waktu:</strong> {timestamp}</p>
                <p>Jika Anda dapat melihat ini, Inertia.js bekerja dengan benar!</p>
            </div>
        </div>
    );
}
