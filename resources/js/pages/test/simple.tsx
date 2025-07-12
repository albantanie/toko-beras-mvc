import React from 'react';
import { Head } from '@inertiajs/react';

interface Props {
    message: string;
    timestamp: string;
}

export default function SimpleTest({ message, timestamp }: Props) {
    return (
        <div>
            <Head title="Simple Test" />
            <div style={{ padding: '20px', fontFamily: 'Arial, sans-serif' }}>
                <h1>Simple Inertia Test</h1>
                <p><strong>Message:</strong> {message}</p>
                <p><strong>Timestamp:</strong> {timestamp}</p>
                <p>If you can see this, Inertia.js is working correctly!</p>
            </div>
        </div>
    );
}
