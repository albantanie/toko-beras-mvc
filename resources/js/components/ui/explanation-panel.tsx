import React from 'react';
import { AlertCircle, Info, TrendingDown, TrendingUp } from 'lucide-react';

interface ExplanationPanelProps {
    title: string;
    type?: 'info' | 'warning' | 'error' | 'success';
    children: React.ReactNode;
}

export function ExplanationPanel({ title, type = 'info', children }: ExplanationPanelProps) {
    const getStyles = () => {
        switch (type) {
            case 'warning':
                return {
                    container: 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    icon: <AlertCircle className="w-4 h-4" />,
                    iconColor: 'text-yellow-600'
                };
            case 'error':
                return {
                    container: 'bg-red-50 border-red-200 text-red-800',
                    icon: <TrendingDown className="w-4 h-4" />,
                    iconColor: 'text-red-600'
                };
            case 'success':
                return {
                    container: 'bg-green-50 border-green-200 text-green-800',
                    icon: <TrendingUp className="w-4 h-4" />,
                    iconColor: 'text-green-600'
                };
            default:
                return {
                    container: 'bg-blue-50 border-blue-200 text-blue-800',
                    icon: <Info className="w-4 h-4" />,
                    iconColor: 'text-blue-600'
                };
        }
    };

    const styles = getStyles();

    return (
        <div className={`p-3 border rounded-lg ${styles.container}`}>
            <div className="flex items-start space-x-2">
                <div className={styles.iconColor}>
                    {styles.icon}
                </div>
                <div className="flex-1">
                    <div className="font-semibold text-sm mb-1">{title}</div>
                    <div className="text-xs">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}

interface BalanceExplanationProps {
    isBalanced: boolean;
    actualValue: number;
    expectedValue: number;
    valueName: string;
    explanation: string;
}

export function BalanceExplanation({ 
    isBalanced, 
    actualValue, 
    expectedValue, 
    valueName, 
    explanation 
}: BalanceExplanationProps) {
    return (
        <ExplanationPanel 
            title={isBalanced ? "✅ Data Seimbang" : "⚠️ Data Tidak Seimbang"} 
            type={isBalanced ? "success" : "warning"}
        >
            <div className="space-y-2">
                <div>
                    <strong>{valueName}:</strong> {actualValue.toLocaleString('id-ID')}
                </div>
                {!isBalanced && (
                    <div>
                        <strong>Seharusnya:</strong> {expectedValue.toLocaleString('id-ID')}
                    </div>
                )}
                <div className="mt-2 text-xs">
                    {explanation}
                </div>
                {!isBalanced && (
                    <div className="mt-2 p-2 bg-white bg-opacity-50 rounded text-xs">
                        <strong>🔧 Cara Mengatasi:</strong>
                        <ul className="mt-1 space-y-1">
                            <li>• Periksa apakah ada transaksi yang belum tercatat</li>
                            <li>• Regenerate laporan untuk memperbarui data</li>
                            <li>• Hubungi admin jika masalah berlanjut</li>
                        </ul>
                    </div>
                )}
            </div>
        </ExplanationPanel>
    );
}

interface ValueExplanationProps {
    value: number;
    label: string;
    isNegative?: boolean;
    explanation: string;
    calculation?: string;
}

export function ValueExplanation({ 
    value, 
    label, 
    isNegative, 
    explanation, 
    calculation 
}: ValueExplanationProps) {
    const formatValue = (val: number) => {
        if (Math.abs(val) >= 1000000) {
            return `Rp ${(val / 1000000).toFixed(1)}M`;
        } else if (Math.abs(val) >= 1000) {
            return `Rp ${(val / 1000).toFixed(1)}K`;
        }
        return `Rp ${val.toLocaleString('id-ID')}`;
    };

    return (
        <ExplanationPanel 
            title={`💰 ${label}`} 
            type={isNegative ? "error" : "success"}
        >
            <div className="space-y-2">
                <div>
                    <strong>Nilai:</strong> {formatValue(value)}
                </div>
                <div>
                    {explanation}
                </div>
                {calculation && (
                    <div className="mt-2 p-2 bg-white bg-opacity-50 rounded text-xs">
                        <strong>📊 Cara Hitung:</strong> {calculation}
                    </div>
                )}
                {isNegative && (
                    <div className="mt-2 p-2 bg-white bg-opacity-50 rounded text-xs">
                        <strong>❓ Mengapa Minus?</strong>
                        <ul className="mt-1 space-y-1">
                            <li>• Lebih banyak barang keluar daripada masuk</li>
                            <li>• Pengeluaran lebih besar dari pemasukan</li>
                            <li>• Normal jika ada penjualan atau pengeluaran</li>
                        </ul>
                    </div>
                )}
            </div>
        </ExplanationPanel>
    );
}
