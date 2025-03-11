<?php

namespace app\controller;

use app\BaseController;
use app\model\IncomeExpense;
use think\Request;

class IncomeExpenseController extends BaseController
{
    public function add(Request $request)
    {
        $data = $request->post();
        $this->validate($data, [
            'period' => 'require|in:daily,weekly,monthly',
            'amount' => 'require|float',
            'date' => 'require|date',
        ]);

        $incomeExpense = new IncomeExpense();
        $incomeExpense->period = $data['period'];
        $incomeExpense->amount = $data['amount'];
        $incomeExpense->date = $data['date'];
        $incomeExpense->save();

        return json(['message' => 'Income/Expense entry added successfully']);
    }

    public function update(Request $request, $id)
    {
        $data = $request->put();
        $this->validate($data, [
            'period' => 'require|in:daily,weekly,monthly',
            'amount' => 'require|float',
            'date' => 'require|date',
        ]);

        $incomeExpense = IncomeExpense::find($id);
        if (!$incomeExpense) {
            return json(['message' => 'Income/Expense entry not found'], 404);
        }

        $incomeExpense->period = $data['period'];
        $incomeExpense->amount = $data['amount'];
        $incomeExpense->date = $data['date'];
        $incomeExpense->save();

        return json(['message' => 'Income/Expense entry updated successfully']);
    }

    public function delete($id)
    {
        $incomeExpense = IncomeExpense::find($id);
        if (!$incomeExpense) {
            return json(['message' => 'Income/Expense entry not found'], 404);
        }

        $incomeExpense->delete();

        return json(['message' => 'Income/Expense entry deleted successfully']);
    }
}
