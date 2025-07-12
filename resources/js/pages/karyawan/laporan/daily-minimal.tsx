import React from 'react';

interface Props {
    reports?: any;
    filters?: any;
    todayStats?: any;
}

export default function KaryawanDailyMinimal({ reports, filters, todayStats }: Props) {
    return (
        <div style={{ padding: '20px', fontFamily: 'Arial, sans-serif' }}>
            <h1>Karyawan Daily Reports - Minimal Version</h1>
            
            <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: '#f5f5f5', borderRadius: '5px' }}>
                <h2>Today's Stats</h2>
                {todayStats ? (
                    <div>
                        <p><strong>Total Movements:</strong> {todayStats.total_movements || 0}</p>
                        <p><strong>Total Stock Value:</strong> Rp {(todayStats.total_stock_value || 0).toLocaleString()}</p>
                        <p><strong>Items Affected:</strong> {todayStats.items_affected || 0}</p>
                    </div>
                ) : (
                    <p>No stats available</p>
                )}
            </div>

            <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: '#f0f8ff', borderRadius: '5px' }}>
                <h2>Reports</h2>
                {reports && reports.data ? (
                    <div>
                        <p><strong>Total Reports:</strong> {reports.total || 0}</p>
                        <p><strong>Reports on this page:</strong> {reports.data.length || 0}</p>
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
                                        <p><strong>Date:</strong> {report.report_date || 'N/A'}</p>
                                        <p><strong>Movements:</strong> {report.total_stock_movements || 0}</p>
                                        <p><strong>Value:</strong> Rp {(report.total_stock_value || 0).toLocaleString()}</p>
                                        <p><strong>Status:</strong> {report.status || 'N/A'}</p>
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
                <h2>System Status</h2>
                <p><strong>Component:</strong> KaryawanDailyMinimal</p>
                <p><strong>Timestamp:</strong> {new Date().toLocaleString()}</p>
                <p><strong>Props received:</strong> {Object.keys({ reports, filters, todayStats }).join(', ')}</p>
                <p style={{ color: 'green', fontWeight: 'bold' }}>✅ Component rendered successfully!</p>
            </div>
        </div>
    );
}
