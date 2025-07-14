import React from 'react';
import { Head } from '@inertiajs/react';

interface Props {
    reports?: any;
    filters?: any;
    todayStats?: any;
}

export default function KaryawanDailySimple({ reports, filters, todayStats }: Props) {
    return (
        <div>
            <Head title="Laporan Stok Harian - Simple" />
            <div style={{ padding: '20px', fontFamily: 'Arial, sans-serif' }}>
                <h1>Laporan Stok Harian (Simple Version)</h1>
                
                <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: '#f5f5f5', borderRadius: '5px' }}>
                    <h2>Today's Stats</h2>
                    {todayStats ? (
                        <div>
                            <p><strong>Total Movements:</strong> {todayStats.total_movements}</p>
                            <p><strong>Total Stock Value:</strong> {formatCurrency(todayStats.total_stock_value || 0)}</p>
                            <p><strong>Items Affected:</strong> {todayStats.items_affected}</p>
                        </div>
                    ) : (
                        <p>No stats available</p>
                    )}
                </div>

                <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: '#f0f8ff', borderRadius: '5px' }}>
                    <h2>Reports</h2>
                    {reports && reports.data ? (
                        <div>
                            <p><strong>Total Reports:</strong> {reports.total}</p>
                            <p><strong>Reports on this page:</strong> {reports.data.length}</p>
                            {reports.data.length > 0 ? (
                                <div>
                                    <h3>Report List:</h3>
                                    {reports.data.map((report: any, index: number) => (
                                        <div key={report.id || index} style={{ 
                                            padding: '10px', 
                                            margin: '5px 0', 
                                            backgroundColor: 'white', 
                                            border: '1px solid #ddd',
                                            borderRadius: '3px'
                                        }}>
                                            <p><strong>Date:</strong> {report.report_date}</p>
                                            <p><strong>Movements:</strong> {report.total_stock_movements}</p>
                                            <p><strong>Value:</strong> {formatCurrency(report.total_stock_value || 0)}</p>
                                            <p><strong>Status:</strong> {report.status}</p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p>No reports found</p>
                            )}
                        </div>
                    ) : (
                        <p>No reports data available</p>
                    )}
                </div>

                <div style={{ marginTop: '20px', padding: '15px', backgroundColor: '#fff3cd', borderRadius: '5px' }}>
                    <h2>Debug Info</h2>
                    <p><strong>Reports type:</strong> {typeof reports}</p>
                    <p><strong>TodayStats type:</strong> {typeof todayStats}</p>
                    <p><strong>Filters type:</strong> {typeof filters}</p>
                    
                    <details style={{ marginTop: '10px' }}>
                        <summary>Raw Data (click to expand)</summary>
                        <pre style={{ fontSize: '12px', overflow: 'auto', maxHeight: '200px' }}>
                            {JSON.stringify({ reports, todayStats, filters }, null, 2)}
                        </pre>
                    </details>
                </div>
            </div>
        </div>
    );
}
