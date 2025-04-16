<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 账户模型
 */
class Account extends Model
{
    // 设置表名
    protected $name = 'account';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'current_balance' => 'decimal:2',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];
    
    /**
     * 获取当前账户余额
     *
     * @return float|null
     */
    public static function getCurrentBalance()
    {
        $account = self::find(1);
        return $account ? $account->current_balance : null;
    }
    
    /**
     * 初始化或更新账户余额
     *
     * @param float $balance
     * @return float 更新后的余额
     */
    public static function initOrUpdateBalance(float $balance): float
    {
        $account = self::find(1);
        
        if (!$account) {
            $account = new self();
            $account->current_balance = $balance;
            $account->save();
        } else {
            $account->current_balance = $balance;
            $account->save();
        }
        
        return $account->current_balance;
    }
    
    /**
     * 更新余额
     *
     * @param float $changeAmount 变更金额（可正可负）
     * @return float 更新后的余额
     */
    public static function updateBalance(float $changeAmount): float
    {
        $account = self::find(1);
        
        if (!$account) {
            $account = new self();
            $account->current_balance = $changeAmount;
            $account->save();
        } else {
            $account->current_balance += $changeAmount;
            $account->save();
        }
        
        return $account->current_balance;
    }
}
