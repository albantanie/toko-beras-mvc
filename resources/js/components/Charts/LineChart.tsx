import React from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

interface LineChartProps {
    data: {
        labels: string[];
        datasets: Array<{
            label: string;
            data: number[];
            borderColor?: string;
            backgroundColor?: string;
            fill?: boolean;
            tension?: number;
        }>;
    };
    options?: any;
    height?: number;
}

export default function LineChart({ data, options = {}, height = 300 }: LineChartProps) {
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
        interaction: {
            mode: 'nearest' as const,
            axis: 'x' as const,
            intersect: false,
        },
        ...options,
    };

    return (
        <div style={{ height: `${height}px` }}>
            <Line data={data} options={defaultOptions} />
        </div>
    );
}
