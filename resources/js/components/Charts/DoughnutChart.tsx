import React from 'react';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';
import { Doughnut } from 'react-chartjs-2';

ChartJS.register(ArcElement, Tooltip, Legend);

interface DoughnutChartProps {
    data: {
        labels: string[];
        datasets: Array<{
            data: number[];
            backgroundColor?: string[];
            borderColor?: string[];
            borderWidth?: number;
        }>;
    };
    options?: any;
    height?: number;
}

export default function DoughnutChart({ data, options = {}, height = 300 }: DoughnutChartProps) {
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    padding: 20,
                    usePointStyle: true,
                },
            },
            tooltip: {
                callbacks: {
                    label: function(context: any) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value} (${percentage}%)`;
                    },
                },
            },
        },
        cutout: '60%',
        ...options,
    };

    return (
        <div style={{ height: `${height}px` }}>
            <Doughnut data={data} options={defaultOptions} />
        </div>
    );
}
