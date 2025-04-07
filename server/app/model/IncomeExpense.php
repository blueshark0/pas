<?php

namespace app\model;

use think\Model;
use think\facade\Db;

class IncomeExpense extends Model
{
    protected $table = 'income_expense';
    protected $fillable = [
        'type',       // 收入/支出
        'amount',     // 金额
        'date',       // 日期
        'category_id',// 分类ID
        'description',// 详细描述
        'source',     // 收入来源或商家信息
        'payment_method', // 支付方式
        'tags',       // 标签，JSON格式存储
        'transaction_type', // 交易类型：一次性、周期性、分期
        'period',     // 周期：daily, weekly, monthly等
        'installment_info', // 分期信息，JSON格式存储
        'account_id', // 关联账户ID
        'status',     // 状态（已结算/未结算等）
        'attachment'  // 附件路径
    ];

    // 自动转换JSON字段
    protected $json = ['tags', 'installment_info'];
    
    // 设置自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 类型常量
    const TYPE_INCOME = 'income';    // 收入
    const TYPE_EXPENSE = 'expense';  // 支出
    
    // 交易类型常量
    const TRANS_TYPE_SINGLE = 'single';     // 一次性
    const TRANS_TYPE_PERIODIC = 'periodic'; // 周期性
    const TRANS_TYPE_INSTALLMENT = 'installment'; // 分期付款

    // 状态常量
    const STATUS_SETTLED = 'settled';       // 已结算
    const STATUS_UNSETTLED = 'unsettled';   // 未结算

    /**
     * 添加收支记录
     * @param array $data 收支数据
     * @return IncomeExpense
     */
    public function addIncomeExpense($data)
    {
        // 如果没有设置日期，使用当前日期
        if (!isset($data['date'])) {
            $data['date'] = date('Y-m-d');
        }
        
        // 如果没有设置交易类型，默认为一次性
        if (!isset($data['transaction_type'])) {
            $data['transaction_type'] = self::TRANS_TYPE_SINGLE;
        }
        
        // 如果没有设置状态，默认为已结算
        if (!isset($data['status'])) {
            $data['status'] = self::STATUS_SETTLED;
        }
        
        return $this->create($data);
    }

    /**
     * 更新收支记录
     * @param int $id 记录ID
     * @param array $data 更新数据
     * @return IncomeExpense|null
     */
    public function updateIncomeExpense($id, $data)
    {
        $incomeExpense = $this->find($id);
        if ($incomeExpense) {
            $incomeExpense->update($data);
            return $incomeExpense;
        }
        return null;
    }

    /**
     * 删除收支记录
     * @param int $id 记录ID
     * @return bool
     */
    public function deleteIncomeExpense($id)
    {
        $incomeExpense = $this->find($id);
        if ($incomeExpense) {
            $incomeExpense->delete();
            return true;
        }
        return false;
    }
    
    /**
     * 获取按日期范围的收支记录
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param array $conditions 其他查询条件
     * @return \think\Collection
     */
    public function getByDateRange($startDate, $endDate, $conditions = [])
    {
        $query = $this->whereBetween('date', [$startDate, $endDate]);
        
        // 应用其他条件
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->order('date desc, id desc')->select();
    }
    
    /**
     * 按类别统计
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param string|null $type 收入或支出类型
     * @return \think\Collection
     */
    public function statisticsByCategory($startDate, $endDate, $type = null)
    {
        $query = $this->field('category_id, sum(amount) as total')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->group('category_id');
                    
        if ($type) {
            $query->where('type', $type);
        }
        
        $result = $query->select();
        
        // 关联分类名称
        foreach ($result as &$item) {
            if ($type == self::TYPE_INCOME) {
                $category = Db::name('income_categories')->where('id', $item['category_id'])->find();
            } else {
                $category = Db::name('expense_categories')->where('id', $item['category_id'])->find();
            }
            
            if ($category) {
                $item['category_name'] = $category['name'];
                $item['category_color'] = $category['color'];
                $item['category_icon'] = $category['icon'];
            } else {
                $item['category_name'] = '未分类';
                $item['category_color'] = '#999999';
                $item['category_icon'] = 'question-circle';
            }
        }
        
        return $result;
    }
    
    /**
     * 获取趋势数据（按月或按日）
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param string $groupBy 分组方式(month/day)
     * @param string|null $type 收入或支出类型
     * @return \think\Collection
     */
    public function getTrend($startDate, $endDate, $groupBy = 'month', $type = null)
    {
        $dateFmt = $groupBy == 'month' ? '%Y-%m' : '%Y-%m-%d';
        
        $query = $this->field("DATE_FORMAT(date, '{$dateFmt}') as date_group, sum(amount) as total")
                    ->whereBetween('date', [$startDate, $endDate])
                    ->group('date_group')
                    ->order('date_group');
                    
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->select();
    }
    
    /**
     * 获取收支汇总
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     */
    public function getSummary($startDate, $endDate)
    {
        // 获取收入总额
        $income = $this->where('type', self::TYPE_INCOME)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('amount');
        
        // 获取支出总额
        $expense = $this->where('type', self::TYPE_EXPENSE)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('amount');
        
        // 计算结余
        $balance = $income - $expense;
        
        // 获取支出最高的三个分类
        $topExpenseCategories = $this->field('category_id, sum(amount) as total')
                            ->where('type', self::TYPE_EXPENSE)
                            ->whereBetween('date', [$startDate, $endDate])
                            ->group('category_id')
                            ->order('total desc')
                            ->limit(3)
                            ->select()
                            ->toArray();
        
        // 关联分类名称
        foreach ($topExpenseCategories as &$item) {
            $category = Db::name('expense_categories')->where('id', $item['category_id'])->find();
            if ($category) {
                $item['category_name'] = $category['name'];
                $item['category_color'] = $category['color'];
                $item['category_icon'] = $category['icon'];
            } else {
                $item['category_name'] = '未分类';
                $item['category_color'] = '#999999';
                $item['category_icon'] = 'question-circle';
            }
        }
        
        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'top_expense_categories' => $topExpenseCategories
        ];
    }
    
    /**
     * 生成周期性交易记录
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return int 生成的记录数量
     */
    public function generatePeriodicTransactions($startDate, $endDate)
    {
        // 获取所有周期性交易记录
        $periodicTransactions = $this->where('transaction_type', self::TRANS_TYPE_PERIODIC)
                                ->where('status', self::STATUS_SETTLED)
                                ->select();
        
        $count = 0;
        foreach ($periodicTransactions as $transaction) {
            $period = $transaction['period'] ?? 'monthly';
            $lastDate = $transaction['date'];
            
            // 计算下一个日期
            while (true) {
                $nextDate = $this->calculateNextDate($lastDate, $period);
                
                // 如果下一个日期超出了结束日期，则停止
                if ($nextDate > $endDate) {
                    break;
                }
                
                // 如果下一个日期在开始日期之前，继续计算下一个
                if ($nextDate < $startDate) {
                    $lastDate = $nextDate;
                    continue;
                }
                
                // 检查该日期是否已经存在同样的交易
                $exists = $this->where('type', $transaction['type'])
                            ->where('category_id', $transaction['category_id'])
                            ->where('amount', $transaction['amount'])
                            ->where('account_id', $transaction['account_id'])
                            ->where('date', $nextDate)
                            ->find();
                
                // 如果不存在，则创建新记录
                if (!$exists) {
                    $newData = $transaction->toArray();
                    unset($newData['id']);
                    $newData['date'] = $nextDate;
                    $newData['description'] = ($newData['description'] ?? '') . '（自动生成）';
                    $newData['transaction_type'] = self::TRANS_TYPE_SINGLE;
                    
                    $this->create($newData);
                    $count++;
                }
                
                $lastDate = $nextDate;
            }
        }
        
        return $count;
    }
    
    /**
     * 计算下一个日期
     * @param string $date 当前日期
     * @param string $period 周期类型
     * @return string
     */
    private function calculateNextDate($date, $period)
    {
        $dateObj = new \DateTime($date);
        
        switch ($period) {
            case 'daily':
                $dateObj->modify('+1 day');
                break;
            case 'weekly':
                $dateObj->modify('+1 week');
                break;
            case 'monthly':
                $dateObj->modify('+1 month');
                break;
            case 'quarterly':
                $dateObj->modify('+3 months');
                break;
            case 'yearly':
                $dateObj->modify('+1 year');
                break;
            default:
                $dateObj->modify('+1 month');
        }
        
        return $dateObj->format('Y-m-d');
    }
    
    /**
     * 关联账户
     * @return \think\model\relation\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Balance::class, 'account_id');
    }
    
    /**
     * 关联收入分类
     * @return \think\model\relation\BelongsTo
     */
    public function incomeCategory()
    {
        // 假设收入分类使用的表是income_categories
        return $this->belongsTo('\think\model\Pivot', 'category_id', 'id', [], 'income_categories');
    }
    
    /**
     * 关联支出分类
     * @return \think\model\relation\BelongsTo
     */
    public function expenseCategory()
    {
        // 假设支出分类使用的表是expense_categories
        return $this->belongsTo('\think\model\Pivot', 'category_id', 'id', [], 'expense_categories');
    }
}
