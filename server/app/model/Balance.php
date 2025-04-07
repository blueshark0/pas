<?php

namespace app\model;

use think\Model;

class Balance extends Model
{
    protected $table = 'balance';
    
    // 设置允许批量赋值的字段
    protected $fillable = ['name', 'type', 'amount', 'initial_balance', 'date', 'description', 'icon', 'color'];
    
    // 设置自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 账户类型常量
    const TYPE_CASH = 'cash';        // 现金
    const TYPE_CARD = 'card';        // 银行卡
    const TYPE_CREDIT = 'credit';    // 信用卡
    const TYPE_VIRTUAL = 'virtual';  // 虚拟账户（支付宝、微信等）
    const TYPE_OTHER = 'other';      // 其他
    
    /**
     * 获取账户类型列表
     * @return array
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_CASH => '现金',
            self::TYPE_CARD => '银行卡',
            self::TYPE_CREDIT => '信用卡',
            self::TYPE_VIRTUAL => '虚拟账户',
            self::TYPE_OTHER => '其他'
        ];
    }
    
    /**
     * 添加账户
     * @param array $data 账户数据
     * @return Balance
     */
    public function addBalance($data)
    {
        // 设置初始余额等于当前余额
        if (!isset($data['initial_balance']) && isset($data['amount'])) {
            $data['initial_balance'] = $data['amount'];
        }
        
        // 如果没有设置日期，使用当前日期
        if (!isset($data['date'])) {
            $data['date'] = date('Y-m-d');
        }
        
        return $this->create($data);
    }

    /**
     * 更新账户
     * @param int $id 账户ID
     * @param array $data 更新数据
     * @return Balance|null
     */
    public function updateBalance($id, $data)
    {
        $balance = $this->find($id);
        if ($balance) {
            $balance->update($data);
            return $balance;
        }
        return null;
    }

    /**
     * 删除账户
     * @param int $id 账户ID
     * @return bool
     */
    public function deleteBalance($id)
    {
        $balance = $this->find($id);
        if ($balance) {
            $balance->delete();
            return true;
        }
        return false;
    }
    
    /**
     * 获取所有账户总余额
     * @return float
     */
    public static function getTotalBalance()
    {
        return self::sum('amount');
    }
    
    /**
     * 获取账户列表（根据类型分组）
     * @return array
     */
    public static function getBalanceListByType()
    {
        $result = [];
        $typeList = self::getTypeList();
        
        foreach ($typeList as $type => $typeName) {
            $accounts = self::where('type', $type)->select();
            if (count($accounts) > 0) {
                $result[$type] = [
                    'name' => $typeName,
                    'accounts' => $accounts,
                    'total' => self::where('type', $type)->sum('amount')
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * 与收支记录的关联
     * @return \think\model\relation\HasMany
     */
    public function incomeExpenses()
    {
        return $this->hasMany(IncomeExpense::class, 'account_id');
    }
}
