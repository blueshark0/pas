<?php

namespace app\model;

use think\Model;

class IncomeExpense extends Model
{
    protected $table = 'income_expense';
    protected $fillable = ['period', 'amount', 'date'];

    public function addIncomeExpense($data)
    {
        return $this->create($data);
    }

    public function updateIncomeExpense($id, $data)
    {
        $incomeExpense = $this->find($id);
        if ($incomeExpense) {
            $incomeExpense->update($data);
            return $incomeExpense;
        }
        return null;
    }

    public function deleteIncomeExpense($id)
    {
        $incomeExpense = $this->find($id);
        if ($incomeExpense) {
            $incomeExpense->delete();
            return true;
        }
        return false;
    }
}
