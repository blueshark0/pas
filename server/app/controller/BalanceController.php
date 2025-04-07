<?php

namespace app\controller;

use app\BaseController;
use app\model\Balance;
use app\model\IncomeExpense;
use think\Request;
use think\facade\Db;

class BalanceController extends BaseController
{
    /**
     * 获取所有账户列表
     */
    public function index()
    {
        $accounts = Balance::order('id', 'desc')->select();
        return json($accounts);
    }
    
    /**
     * 获取账户类型列表
     */
    public function getTypes()
    {
        return json(Balance::getTypeList());
    }
    
    /**
     * 获取账户资产按类型分组的统计
     */
    public function getBalanceByType()
    {
        $balanceByType = Balance::getBalanceListByType();
        $totalBalance = Balance::getTotalBalance();
        
        return json([
            'by_type' => $balanceByType,
            'total' => $totalBalance
        ]);
    }
    
    /**
     * 获取单个账户详情
     */
    public function detail($id)
    {
        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => '账户不存在'], 404);
        }
        
        return json($balance);
    }
    
    /**
     * 添加新账户
     */
    public function add(Request $request)
    {
        $data = $request->post();
        $this->validate($data, [
            'name' => 'require|string|max:50',
            'type' => 'require|string',
            'amount' => 'require|float',
            'initial_balance' => 'float',
            'date' => 'date',
            'description' => 'string|max:200',
        ]);

        // 开启事务
        Db::startTrans();
        try {
            $balance = new Balance();
            $result = $balance->addBalance($data);
            
            // 提交事务
            Db::commit();
            return json(['message' => '账户添加成功', 'id' => $result->id]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 更新账户信息
     */
    public function update(Request $request, $id)
    {
        $data = $request->put();
        $this->validate($data, [
            'name' => 'string|max:50',
            'type' => 'string',
            'amount' => 'float',
            'initial_balance' => 'float',
            'date' => 'date',
            'description' => 'string|max:200',
        ]);

        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => '账户不存在'], 404);
        }

        // 开启事务
        Db::startTrans();
        try {
            $result = $balance->updateBalance($id, $data);
            
            // 提交事务
            Db::commit();
            return json(['message' => '账户更新成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * 删除账户
     */
    public function delete($id)
    {
        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => '账户不存在'], 404);
        }

        // 检查是否有关联的收支记录
        $hasRecords = IncomeExpense::where('account_id', $id)->count();
        if ($hasRecords > 0) {
            return json(['message' => '该账户存在关联的收支记录，无法删除'], 400);
        }

        // 开启事务
        Db::startTrans();
        try {
            $balance->deleteBalance($id);
            
            // 提交事务
            Db::commit();
            return json(['message' => '账户删除成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * 获取账户交易记录
     */
    public function transactions(Request $request, $id)
    {
        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => '账户不存在'], 404);
        }
        
        $page = $request->param('page', 1);
        $pageSize = $request->param('page_size', 10);
        $startDate = $request->param('start_date');
        $endDate = $request->param('end_date');
        
        $query = IncomeExpense::where('account_id', $id)
                            ->order('date', 'desc');
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $result = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
        
        return json($result);
    }
    
    /**
     * 账户间转账
     */
    public function transfer(Request $request)
    {
        $data = $request->post();
        $this->validate($data, [
            'from_account_id' => 'require|number',
            'to_account_id' => 'require|number',
            'amount' => 'require|float|gt:0',
            'date' => 'date',
            'description' => 'string|max:200',
        ]);
        
        if ($data['from_account_id'] == $data['to_account_id']) {
            return json(['message' => '转出账户和转入账户不能相同'], 400);
        }
        
        // 查找转出和转入账户
        $fromAccount = Balance::find($data['from_account_id']);
        $toAccount = Balance::find($data['to_account_id']);
        
        if (!$fromAccount) {
            return json(['message' => '转出账户不存在'], 404);
        }
        
        if (!$toAccount) {
            return json(['message' => '转入账户不存在'], 404);
        }
        
        // 检查转出账户余额是否充足
        if ($fromAccount->amount < $data['amount']) {
            return json(['message' => '转出账户余额不足'], 400);
        }
        
        // 设置日期，如果没有提供则使用当前日期
        $date = isset($data['date']) ? $data['date'] : date('Y-m-d');
        
        // 开启事务
        Db::startTrans();
        try {
            // 减少转出账户余额
            $fromAccount->amount -= $data['amount'];
            $fromAccount->save();
            
            // 增加转入账户余额
            $toAccount->amount += $data['amount'];
            $toAccount->save();
            
            // 创建转出记录
            $expenseData = [
                'type' => IncomeExpense::TYPE_EXPENSE,
                'amount' => $data['amount'],
                'date' => $date,
                'category_id' => 0, // 使用特殊分类ID表示转账
                'description' => ($data['description'] ?? '') . ' [转账至' . $toAccount->name . ']',
                'account_id' => $data['from_account_id'],
                'transaction_type' => IncomeExpense::TRANS_TYPE_SINGLE,
                'status' => IncomeExpense::STATUS_SETTLED
            ];
            
            $incomeExpense = new IncomeExpense();
            $expenseRecord = $incomeExpense->addIncomeExpense($expenseData);
            
            // 创建转入记录
            $incomeData = [
                'type' => IncomeExpense::TYPE_INCOME,
                'amount' => $data['amount'],
                'date' => $date,
                'category_id' => 0, // 使用特殊分类ID表示转账
                'description' => ($data['description'] ?? '') . ' [来自' . $fromAccount->name . '的转账]',
                'account_id' => $data['to_account_id'],
                'transaction_type' => IncomeExpense::TRANS_TYPE_SINGLE,
                'status' => IncomeExpense::STATUS_SETTLED
            ];
            
            $incomeRecord = $incomeExpense->addIncomeExpense($incomeData);
            
            // 提交事务
            Db::commit();
            return json([
                'message' => '转账成功',
                'expense_id' => $expenseRecord->id,
                'income_id' => $incomeRecord->id
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * 调整账户余额
     */
    public function adjustBalance(Request $request, $id)
    {
        $data = $request->post();
        $this->validate($data, [
            'amount' => 'require|float',
            'date' => 'date',
            'description' => 'string|max:200',
        ]);
        
        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => '账户不存在'], 404);
        }
        
        // 计算调整差额
        $adjustAmount = $data['amount'] - $balance->amount;
        
        // 如果调整金额为0，直接返回成功
        if ($adjustAmount == 0) {
            return json(['message' => '账户余额无需调整']);
        }
        
        // 设置日期，如果没有提供则使用当前日期
        $date = isset($data['date']) ? $data['date'] : date('Y-m-d');
        
        // 开启事务
        Db::startTrans();
        try {
            // 更新账户余额
            $balance->amount = $data['amount'];
            $balance->save();
            
            // 创建一条收支记录来记录余额调整
            $recordData = [
                'type' => $adjustAmount > 0 ? IncomeExpense::TYPE_INCOME : IncomeExpense::TYPE_EXPENSE,
                'amount' => abs($adjustAmount),
                'date' => $date,
                'category_id' => 0, // 使用特殊分类ID表示余额调整
                'description' => ($data['description'] ?? '') . ' [余额调整]',
                'account_id' => $id,
                'transaction_type' => IncomeExpense::TRANS_TYPE_SINGLE,
                'status' => IncomeExpense::STATUS_SETTLED
            ];
            
            $incomeExpense = new IncomeExpense();
            $record = $incomeExpense->addIncomeExpense($recordData);
            
            // 提交事务
            Db::commit();
            return json([
                'message' => '账户余额调整成功',
                'record_id' => $record->id
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * 获取账户余额趋势
     */
    public function balanceTrend(Request $request, $id = null)
    {
        $startDate = $request->param('start_date', date('Y-m-01'));
        $endDate = $request->param('end_date', date('Y-m-d'));
        $period = $request->param('period', 'day'); // day, week, month
        
        // 构建查询条件
        $query = new IncomeExpense();
        
        // 如果指定了账户ID，则只查询该账户的记录
        if ($id) {
            $query = $query->where('account_id', $id);
        }
        
        // 获取指定日期范围内的所有收支记录
        $records = $query->whereBetween('date', [$startDate, $endDate])
                        ->order('date', 'asc')
                        ->select()
                        ->toArray();
        
        // 获取所有账户的初始余额
        $accounts = Balance::select()->toArray();
        $initialBalances = [];
        
        foreach ($accounts as $account) {
            $initialBalances[$account['id']] = $account['initial_balance'];
        }
        
        // 计算每天的余额变化
        $dailyBalanceChanges = [];
        
        foreach ($records as $record) {
            $date = $record['date'];
            $accountId = $record['account_id'];
            
            if (!isset($dailyBalanceChanges[$date])) {
                $dailyBalanceChanges[$date] = [];
            }
            
            if (!isset($dailyBalanceChanges[$date][$accountId])) {
                $dailyBalanceChanges[$date][$accountId] = 0;
            }
            
            // 收入增加余额，支出减少余额
            if ($record['type'] == IncomeExpense::TYPE_INCOME) {
                $dailyBalanceChanges[$date][$accountId] += $record['amount'];
            } else {
                $dailyBalanceChanges[$date][$accountId] -= $record['amount'];
            }
        }
        
        // 生成日期序列
        $dateSequence = [];
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dateSequence[] = $currentDate;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        // 计算每天的累计余额
        $balanceTrend = [];
        $runningBalances = $initialBalances;
        
        foreach ($dateSequence as $date) {
            if (isset($dailyBalanceChanges[$date])) {
                foreach ($dailyBalanceChanges[$date] as $accountId => $change) {
                    if (isset($runningBalances[$accountId])) {
                        $runningBalances[$accountId] += $change;
                    }
                }
            }
            
            // 如果指定了账户ID，只返回该账户的余额趋势
            if ($id) {
                $balanceTrend[] = [
                    'date' => $date,
                    'balance' => $runningBalances[$id] ?? 0
                ];
            } else {
                // 否则，返回所有账户的总余额
                $totalBalance = array_sum($runningBalances);
                $balanceTrend[] = [
                    'date' => $date,
                    'balance' => $totalBalance
                ];
            }
        }
        
        // 根据period参数对数据进行聚合
        if ($period != 'day') {
            $groupedTrend = [];
            $format = $period == 'week' ? 'Y-W' : 'Y-m';
            
            foreach ($balanceTrend as $item) {
                $groupKey = date($format, strtotime($item['date']));
                if (!isset($groupedTrend[$groupKey])) {
                    $groupedTrend[$groupKey] = [
                        'date' => $period == 'week' ? $this->getWeekStartDate($item['date']) : date('Y-m-01', strtotime($item['date'])),
                        'balance' => 0
                    ];
                }
                
                // 使用每个周期的最后一天的余额作为该周期的余额
                $groupedTrend[$groupKey]['balance'] = $item['balance'];
            }
            
            $balanceTrend = array_values($groupedTrend);
        }
        
        return json($balanceTrend);
    }
    
    /**
     * 获取指定日期所在周的开始日期
     */
    private function getWeekStartDate($date)
    {
        $timestamp = strtotime($date);
        $weekday = date('w', $timestamp);
        $weekStartDay = date('Y-m-d', strtotime("-{$weekday} days", $timestamp));
        return $weekStartDay;
    }
}
