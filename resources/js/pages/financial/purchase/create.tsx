import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Props {
  financialAccounts: { id: number; account_name: string; account_type: string; current_balance: number }[];
}

export default function PurchaseCreate({ financialAccounts }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    financial_account_id: '',
  });

  return (
    <div className="space-y-2">
      <Label htmlFor="financial_account_id">Akun Kas/Bank *</Label>
      <Select
        value={data.financial_account_id}
        onValueChange={(value) => setData('financial_account_id', value)}
      >
        <SelectTrigger className={errors.financial_account_id ? 'border-red-500' : ''}>
          <SelectValue placeholder="Pilih akun kas/bank" />
        </SelectTrigger>
        <SelectContent>
          {financialAccounts.map((acc) => (
            <SelectItem key={acc.id} value={acc.id.toString()}>
              {acc.account_name} - {formatCurrency(acc.current_balance)}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      {errors.financial_account_id && (
        <p className="text-sm text-red-500">{errors.financial_account_id}</p>
      )}
    </div>
  );
} 