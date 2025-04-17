<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 余额变更历史模型
 */
class BalanceHistory extends Model
{
    // 设置表名
    protected $name = 'balance_history';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳（创建时间）
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false; // 不需要更新时间
    
    // 设置字段信息
    protected $schema = [
        'id'                    => 'int',
        'timestamp'             => 'datetime',
        'change_type'           => 'tinyint',
        'related_transaction_id' => 'int',
        'amount_change'         => 'decimal:2',
        'balance_after'         => 'decimal:2',
        'created_at'            => 'datetime',
    ];
    
    // 变更类型常量
    const CHANGE_TYPE_INIT = 1;      // 初始设置
    const CHANGE_TYPE_INCOME = 2;    // 收入执行
    const CHANGE_TYPE_EXPENSE = 3;   // 支出执行
    const CHANGE_TYPE_MANUAL = 4;    // 手动编辑
    
    /**
     * 创建历史记录
     *
     * @param int $changeType 变更类型
     * @param float $amountChange 变更金额（可正可负）
     * @param float $balanceAfter 变更后余额
     * @param int|null $relatedTransactionId 关联交易ID
     * @return bool
     */
    public static function createRecord(
        int $changeType, 
        float $amountChange, 
        float $balanceAfter, 
        ?int $relatedTransactionId = null
    ): bool {
        $history = new self();
        $history->timestamp = date('Y-m-d H:i:s');
        $history->change_type = $changeType;
        $history->related_transaction_id = $relatedTransactionId;
        $history->amount_change = $amountChange;
        $history->balance_after = $balanceAfter;
        return $history->save();
    }
    
    /**
     * 获取历史记录列表
     *
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array 包含 list 和 total 的数组
     */
    public static function getHistoryList(int $page = 1, int $pageSize = 20): array
    {
        $list = self::order('timestamp', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
            
        $total = self::count();
        
        return ['list' => $list, 'total' => $total];
    }
    
    /**
     * 获取关联交易
     */
    public function transaction()
    {
        return $this->belongsTo(PresetTransaction::class, 'related_transaction_id');
    }
}
