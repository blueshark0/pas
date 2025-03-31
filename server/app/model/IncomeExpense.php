<?php

namespace app\model;

use think\Model;

class IncomeExpense extends Model
{
    protected $table = 'income_expense';
    protected $fillable = [
        'type',       // 收入/支出
        'amount',     // 金额
        'date',       // 日期
        'category',   // 分类（食品、交通等）
        'description',// 详细描述
        'tags',       // 标签，JSON格式存储
        'transaction_type', // 交易类型：一次性、周期性、分期
        'period',     // 周期：daily, weekly, monthly等
        'installment_info', // 分期信息，JSON格式存储
        'account_id', // 关联账户ID
        'status'      // 状态（已结算/未结算等）
    ];

    // 自动转换JSON字段
    protected $json = ['tags', 'installment_info'];
    
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
    
    // 获取按日期范围的收支记录
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
        
        return $query->select();
    }
    
    // 按类别统计
    public function statisticsByCategory($startDate, $endDate, $type = null)
    {
        $query = $this->field('category, sum(amount) as total')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->group('category');
                    
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->select();
    }
    
    // 获取趋势数据（按月或按日）
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
    
    // 关联账户
    public function account()
    {
        return $this->belongsTo(Balance::class, 'account_id');
    }
}
