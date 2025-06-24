import React from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Bar } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);

interface BarChartProps {
    data: {
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            backgroundColor?: string | string[];
            borderColor?: string | string[];
            borderWidth?: number;
        }>;
    };
    options?: any;
    height?: number;
}

export default function BarChart({ data, options = {}, height = 300 }: BarChartProps) {
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
            },
            tooltip: {
                mode: 'index' as const,
                intersect: false,
            },
        },
        scales: {
            x: {
                display: true,
                grid: {
                    display: false,
                },
            },
            y: {
                display: true,
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)',
                },
            },
        },
        ...options,
    };

    return (
        <div style={{ height: `${height}px` }}>
            <Bar data={data} options={defaultOptions} />
        </div>
    );
}
