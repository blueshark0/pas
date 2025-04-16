<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 预设交易模型
 */
class PresetTransaction extends Model
{
    // 设置表名
    protected $name = 'preset_transaction';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 设置字段信息
    protected $schema = [
        'id'                  => 'int',
        'type'                => 'tinyint',
        'amount'              => 'decimal:2',
        'execution_date'      => 'date',
        'description'         => 'string',
        'status'              => 'tinyint',
        'is_recurring'        => 'tinyint',
        'recurrence_type'     => 'tinyint',
        'recurrence_interval' => 'int',
        'recurrence_end_date' => 'date',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];
    
    // 类型常量
    const TYPE_INCOME = 1;    // 收入
    const TYPE_EXPENSE = 2;   // 支出
    
    // 状态常量
    const STATUS_PENDING = 1;    // 待执行
    const STATUS_EXECUTING = 2;  // 执行中
    const STATUS_EXECUTED = 3;   // 已执行
    const STATUS_TERMINATED = 4; // 终止
    
    // 周期类型常量
    const RECURRENCE_NONE = 0;   // 无周期
    const RECURRENCE_DAILY = 1;  // 每日
    const RECURRENCE_WEEKLY = 2; // 每周
    const RECURRENCE_MONTHLY = 3;// 每月
    const RECURRENCE_YEARLY = 4; // 每年
    
    /**
     * 获取待执行的交易列表
     *
     * @param string|null $date 指定日期，默认为当前日期
     * @return array
     */
    public static function getPendingTransactions(?string $date = null): array
    {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        
        // 查找执行日期小于等于指定日期且状态为待执行的交易
        return self::where('execution_date', '<=', $date)
            ->where('status', self::STATUS_PENDING)
            ->select()
            ->toArray();
    }
    
    /**
     * 更新交易状态
     *
     * @param int $id 交易ID
     * @param int $status 新状态
     * @return bool
     */
    public static function updateStatus(int $id, int $status): bool
    {
        $transaction = self::find($id);
        if (!$transaction) {
            return false;
        }
        
        $transaction->status = $status;
        return $transaction->save();
    }
    
    /**
     * 处理周期性交易
     * 
     * @param int $id 交易ID
     * @return bool|array 处理失败返回false，成功返回新创建的交易数据
     */
    public static function processRecurringTransaction(int $id)
    {
        $transaction = self::find($id);
        if (!$transaction || !$transaction->is_recurring || $transaction->status != self::STATUS_EXECUTED) {
            return false;
        }
        
        // 检查是否已达到结束日期
        if ($transaction->recurrence_end_date && strtotime($transaction->recurrence_end_date) < time()) {
            return false;
        }
        
        // 计算下一次执行日期
        $nextDate = self::calculateNextExecutionDate(
            $transaction->execution_date,
            $transaction->recurrence_type,
            $transaction->recurrence_interval
        );
        
        if (!$nextDate) {
            return false;
        }
        
        // 创建新的周期交易
        $newTransaction = new self();
        $newTransaction->type = $transaction->type;
        $newTransaction->amount = $transaction->amount;
        $newTransaction->execution_date = $nextDate;
        $newTransaction->description = $transaction->description;
        $newTransaction->status = self::STATUS_PENDING;
        $newTransaction->is_recurring = $transaction->is_recurring;
        $newTransaction->recurrence_type = $transaction->recurrence_type;
        $newTransaction->recurrence_interval = $transaction->recurrence_interval;
        $newTransaction->recurrence_end_date = $transaction->recurrence_end_date;
        $newTransaction->save();
        
        return $newTransaction->toArray();
    }
    
    /**
     * 计算下一次执行日期
     *
     * @param string $currentDate 当前执行日期
     * @param int $recurrenceType 周期类型
     * @param int $interval 间隔
     * @return string|null 下一次执行日期，格式：Y-m-d
     */
    private static function calculateNextExecutionDate(string $currentDate, int $recurrenceType, int $interval): ?string
    {
        $date = strtotime($currentDate);
        
        switch ($recurrenceType) {
            case self::RECURRENCE_DAILY:
                $nextDate = strtotime("+{$interval} day", $date);
                break;
                
            case self::RECURRENCE_WEEKLY:
                $nextDate = strtotime("+{$interval} week", $date);
                break;
                
            case self::RECURRENCE_MONTHLY:
                $nextDate = strtotime("+{$interval} month", $date);
                break;
                
            case self::RECURRENCE_YEARLY:
                $nextDate = strtotime("+{$interval} year", $date);
                break;
                
            default:
                return null;
        }
        
        return date('Y-m-d', $nextDate);
    }
}
