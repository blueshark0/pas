<?php
declare(strict_types=1);

namespace app\service;

use app\model\Account;
use app\model\BalanceHistory;
use app\model\PresetTransaction;
use think\Exception;
use think\facade\Db;

/**
 * 交易服务类
 */
class TransactionService
{
    /**
     * 获取交易列表
     *
     * @param int|null $status 状态筛选
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array
     */
    public function getTransactionList(?int $status = null, int $page = 1, int $pageSize = 20): array
    {
        $query = PresetTransaction::order('execution_date', 'asc');
        
        if ($status !== null) {
            $query->where('status', $status);
        }
        
        $list = $query->page($page, $pageSize)->select()->toArray();
        $total = $query->count();
        
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total
            ]
        ];
    }
    
    /**
     * 添加预设交易
     *
     * @param array $data 交易数据
     * @return array
     */
    public function addTransaction(array $data): array
    {
        // 基础字段验证
        if (empty($data['type']) || empty($data['amount']) || empty($data['execution_date'])) {
            return [
                'code' => 1,
                'msg' => '缺少必要参数',
                'data' => null
            ];
        }
        
        // 金额必须为正数
        if ($data['amount'] <= 0) {
            return [
                'code' => 1,
                'msg' => '金额必须为正数',
                'data' => null
            ];
        }
        
        try {
            $transaction = new PresetTransaction();
            $transaction->type = $data['type'];
            $transaction->amount = $data['amount'];
            $transaction->execution_date = $data['execution_date'];
            $transaction->description = $data['description'] ?? '';
            $transaction->status = PresetTransaction::STATUS_PENDING;
            
            // 设置周期性相关字段（如果有）
            if (!empty($data['is_recurring']) && $data['is_recurring'] == 1) {
                $transaction->is_recurring = 1;
                $transaction->recurrence_type = $data['recurrence_type'] ?? 0;
                $transaction->recurrence_interval = $data['recurrence_interval'] ?? 1;
                $transaction->recurrence_end_date = $data['recurrence_end_date'] ?? null;
            } else {
                $transaction->is_recurring = 0;
            }
            
            $transaction->save();
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'id' => $transaction->id
                ]
            ];
        } catch (Exception $e) {
            return [
                'code' => 1,
                'msg' => '添加交易失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 修改预设交易
     *
     * @param int $id 交易ID
     * @param array $data 更新数据
     * @return array
     */
    public function updateTransaction(int $id, array $data): array
    {
        $transaction = PresetTransaction::find($id);
        if (!$transaction) {
            return [
                'code' => 1,
                'msg' => '交易不存在',
                'data' => null
            ];
        }
        
        // 已执行的交易不允许修改
        if ($transaction->status == PresetTransaction::STATUS_EXECUTED) {
            return [
                'code' => 1,
                'msg' => '已执行的交易不能修改',
                'data' => null
            ];
        }
        
        try {
            // 更新允许修改的字段
            if (isset($data['amount']) && $data['amount'] > 0) {
                $transaction->amount = $data['amount'];
            }
            
            if (isset($data['execution_date'])) {
                $transaction->execution_date = $data['execution_date'];
            }
            
            if (isset($data['description'])) {
                $transaction->description = $data['description'];
            }
            
            // 更新周期性相关字段（如果有）
            if (isset($data['is_recurring'])) {
                $transaction->is_recurring = $data['is_recurring'];
                
                if ($data['is_recurring'] == 1) {
                    $transaction->recurrence_type = $data['recurrence_type'] ?? $transaction->recurrence_type;
                    $transaction->recurrence_interval = $data['recurrence_interval'] ?? $transaction->recurrence_interval;
                    $transaction->recurrence_end_date = $data['recurrence_end_date'] ?? $transaction->recurrence_end_date;
                }
            }
            
            $transaction->save();
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => null
            ];
        } catch (Exception $e) {
            return [
                'code' => 1,
                'msg' => '修改交易失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 删除预设交易
     *
     * @param int $id 交易ID
     * @return array
     */
    public function deleteTransaction(int $id): array
    {
        $transaction = PresetTransaction::find($id);
        if (!$transaction) {
            return [
                'code' => 1,
                'msg' => '交易不存在',
                'data' => null
            ];
        }
        
        // 已执行的交易不允许删除
        if ($transaction->status == PresetTransaction::STATUS_EXECUTED) {
            return [
                'code' => 1,
                'msg' => '已执行的交易不能删除',
                'data' => null
            ];
        }
        
        try {
            $transaction->delete();
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => null
            ];
        } catch (Exception $e) {
            return [
                'code' => 1,
                'msg' => '删除交易失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 执行待处理的交易
     *
     * @param string|null $date 指定日期，默认为当前日期
     * @return array
     */
    public function executeTransactions(?string $date = null): array
    {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        
        // 获取待执行的交易
        $pendingTransactions = PresetTransaction::getPendingTransactions($date);
        if (empty($pendingTransactions)) {
            return [
                'code' => 0,
                'msg' => '没有待执行的交易',
                'data' => []
            ];
        }
        
        $executedTransactions = [];
        $failedTransactions = [];
        
        Db::startTrans();
        try {
            foreach ($pendingTransactions as $transaction) {
                // 根据交易类型计算余额变更
                $changeAmount = $transaction['type'] == PresetTransaction::TYPE_INCOME
                    ? $transaction['amount']
                    : -$transaction['amount'];
                
                // 更新账户余额
                $newBalance = Account::updateBalance($changeAmount);
                
                // 更新交易状态为已执行
                PresetTransaction::updateStatus($transaction['id'], PresetTransaction::STATUS_EXECUTED);
                
                // 创建余额变更历史
                $changeType = $transaction['type'] == PresetTransaction::TYPE_INCOME
                    ? BalanceHistory::CHANGE_TYPE_INCOME
                    : BalanceHistory::CHANGE_TYPE_EXPENSE;
                
                BalanceHistory::createRecord(
                    $changeType,
                    $changeAmount,
                    $newBalance,
                    $transaction['id']
                );
                
                // 如果是周期性交易，创建下一期交易
                if ($transaction['is_recurring']) {
                    PresetTransaction::processRecurringTransaction($transaction['id']);
                }
                
                $executedTransactions[] = $transaction;
            }
            
            Db::commit();
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'executed' => $executedTransactions,
                    'failed' => $failedTransactions,
                    'current_balance' => Account::getCurrentBalance()
                ]
            ];
        } catch (Exception $e) {
            Db::rollback();
            return [
                'code' => 1,
                'msg' => '执行交易失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
