<?php

namespace app\controller;

use app\BaseController;
use app\model\IncomeExpense;
use app\model\Balance;
use think\Request;
use think\facade\Db;

class IncomeExpenseController extends BaseController
{
    /**
     * 添加收支记录
     */
    public function add(Request $request)
    {
        $data = $request->post();
        $this->validate($data, [
            'type' => 'require|in:income,expense',
            'amount' => 'require|float',
            'date' => 'require|date',
            'category' => 'require|string',
            'transaction_type' => 'require|in:single,periodic,installment',
            'account_id' => 'require|number',
        ]);

        // 可选字段验证
        if (isset($data['transaction_type'])) {
            if ($data['transaction_type'] == IncomeExpense::TRANS_TYPE_PERIODIC) {
                $this->validate($data, ['period' => 'require|in:daily,weekly,monthly,yearly']);
            } elseif ($data['transaction_type'] == IncomeExpense::TRANS_TYPE_INSTALLMENT) {
                $this->validate($data, [
                    'installment_info.total_amount' => 'require|float',
                    'installment_info.total_periods' => 'require|number',
                    'installment_info.current_period' => 'require|number',
                ]);
            }
        }

        // 开启事务
        Db::startTrans();
        try {
            // 创建收支记录
            $incomeExpense = new IncomeExpense();
            $incomeExpense->save($data);
            
            // 更新账户余额
            $account = Balance::find($data['account_id']);
            if (!$account) {
                throw new \Exception('账户不存在');
            }
            
            // 根据类型增加或减少余额
            if ($data['type'] == IncomeExpense::TYPE_INCOME) {
                $account->amount += $data['amount'];
            } else {
                $account->amount -= $data['amount'];
            }
            $account->save();
            
            // 提交事务
            Db::commit();
            return json(['message' => '收支记录添加成功', 'id' => $incomeExpense->id]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 更新收支记录
     */
    public function update(Request $request, $id)
    {
        $data = $request->put();
        $this->validate($data, [
            'type' => 'in:income,expense',
            'amount' => 'float',
            'date' => 'date',
            'category' => 'string',
            'transaction_type' => 'in:single,periodic,installment',
        ]);

        // 获取原记录
        $incomeExpense = IncomeExpense::find($id);
        if (!$incomeExpense) {
            return json(['message' => '收支记录不存在'], 404);
        }

        // 可选字段验证
        if (isset($data['transaction_type'])) {
            if ($data['transaction_type'] == IncomeExpense::TRANS_TYPE_PERIODIC) {
                $this->validate($data, ['period' => 'require|in:daily,weekly,monthly,yearly']);
            } elseif ($data['transaction_type'] == IncomeExpense::TRANS_TYPE_INSTALLMENT) {
                $this->validate($data, [
                    'installment_info.total_amount' => 'float',
                    'installment_info.total_periods' => 'number',
                    'installment_info.current_period' => 'number',
                ]);
            }
        }

        // 开启事务
        Db::startTrans();
        try {
            // 如果金额或类型有变化，需要更新账户余额
            $oldAmount = $incomeExpense->amount;
            $oldType = $incomeExpense->type;
            $newAmount = isset($data['amount']) ? $data['amount'] : $oldAmount;
            $newType = isset($data['type']) ? $data['type'] : $oldType;
            
            if ($oldAmount != $newAmount || $oldType != $newType) {
                $account = Balance::find($incomeExpense->account_id);
                
                // 先恢复之前的余额变动
                if ($oldType == IncomeExpense::TYPE_INCOME) {
                    $account->amount -= $oldAmount;
                } else {
                    $account->amount += $oldAmount;
                }
                
                // 应用新的变动
                if ($newType == IncomeExpense::TYPE_INCOME) {
                    $account->amount += $newAmount;
                } else {
                    $account->amount -= $newAmount;
                }
                
                $account->save();
            }
            
            // 更新收支记录
            $incomeExpense->save($data);
            
            // 提交事务
            Db::commit();
            return json(['message' => '收支记录更新成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 删除收支记录
     */
    public function delete($id)
    {
        $incomeExpense = IncomeExpense::find($id);
        if (!$incomeExpense) {
            return json(['message' => '收支记录不存在'], 404);
        }

        // 开启事务
        Db::startTrans();
        try {
            // 恢复账户余额
            $account = Balance::find($incomeExpense->account_id);
            if ($incomeExpense->type == IncomeExpense::TYPE_INCOME) {
                $account->amount -= $incomeExpense->amount;
            } else {
                $account->amount += $incomeExpense->amount;
            }
            $account->save();
            
            // 删除记录
            $incomeExpense->delete();
            
            // 提交事务
            Db::commit();
            return json(['message' => '收支记录删除成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 获取收支记录列表
     */
    public function list(Request $request)
    {
        $page = $request->param('page', 1);
        $pageSize = $request->param('page_size', 10);
        $startDate = $request->param('start_date');
        $endDate = $request->param('end_date');
        $type = $request->param('type');
        $category = $request->param('category');
        $accountId = $request->param('account_id');
        
        $query = IncomeExpense::order('date', 'desc');
        
        // 根据条件筛选
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($category) {
            $query->where('category', $category);
        }
        
        if ($accountId) {
            $query->where('account_id', $accountId);
        }
        
        // 分页查询
        $result = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
        
        return json($result);
    }

    /**
     * 获取分类统计数据
     */
    public function categoryStats(Request $request)
    {
        $startDate = $request->param('start_date', date('Y-m-01'));
        $endDate = $request->param('end_date', date('Y-m-d'));
        $type = $request->param('type');
        
        $model = new IncomeExpense();
        $stats = $model->statisticsByCategory($startDate, $endDate, $type);
        
        return json($stats);
    }

    /**
     * 获取收支趋势数据
     */
    public function trendStats(Request $request)
    {
        $startDate = $request->param('start_date', date('Y-m-01'));
        $endDate = $request->param('end_date', date('Y-m-d'));
        $groupBy = $request->param('group_by', 'month'); // month 或 day
        $type = $request->param('type');
        
        $model = new IncomeExpense();
        $trends = $model->getTrend($startDate, $endDate, $groupBy, $type);
        
        return json($trends);
    }

    /**
     * 获取收支概况
     */
    public function summary(Request $request)
    {
        $period = $request->param('period', 'month'); // month, year, custom
        
        // 根据周期计算起止日期
        switch ($period) {
            case 'month':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-d');
                break;
            case 'year':
                $startDate = date('Y-01-01');
                $endDate = date('Y-m-d');
                break;
            case 'custom':
                $startDate = $request->param('start_date');
                $endDate = $request->param('end_date');
                break;
            default:
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-d');
        }
        
        // 分别计算收入和支出总额
        $income = IncomeExpense::where('type', IncomeExpense::TYPE_INCOME)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        $expense = IncomeExpense::where('type', IncomeExpense::TYPE_EXPENSE)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        // 计算余额
        $balance = $income - $expense;
        
        // 获取支出最高的三个分类
        $topCategories = IncomeExpense::field('category, sum(amount) as total')
            ->where('type', IncomeExpense::TYPE_EXPENSE)
            ->whereBetween('date', [$startDate, $endDate])
            ->group('category')
            ->order('total', 'desc')
            ->limit(3)
            ->select();
        
        return json([
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'top_expense_categories' => $topCategories,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }
}
