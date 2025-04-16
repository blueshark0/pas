<?php
declare(strict_types=1);

namespace app\service;

use app\model\Account;
use app\model\BalanceHistory;
use think\Exception;
use think\facade\Db;

/**
 * 账户服务类
 */
class AccountService
{
    /**
     * 获取当前账户余额
     *
     * @return array
     */
    public function getBalance(): array
    {
        $balance = Account::getCurrentBalance();
        if ($balance === null) {
            return [
                'code' => 1, 
                'msg' => '账户未初始化', 
                'data' => null
            ];
        }
        
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'current_balance' => $balance
            ]
        ];
    }
    
    /**
     * 初始化账户余额
     *
     * @param float $initialBalance 初始余额
     * @return array
     */
    public function initBalance(float $initialBalance): array
    {
        // 参数验证
        if ($initialBalance < 0) {
            return [
                'code' => 1,
                'msg' => '初始余额不能为负数',
                'data' => null
            ];
        }
        
        Db::startTrans();
        try {
            // 更新账户余额
            $balance = Account::initOrUpdateBalance($initialBalance);
            
            // 创建历史记录
            BalanceHistory::createRecord(
                BalanceHistory::CHANGE_TYPE_INIT,
                $initialBalance,
                $balance
            );
            
            Db::commit();
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'current_balance' => $balance
                ]
            ];
        } catch (Exception $e) {
            Db::rollback();
            return [
                'code' => 1,
                'msg' => '初始化余额失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 手动编辑余额
     *
     * @param float $newBalance 新余额
     * @return array
     */
    public function editBalance(float $newBalance): array
    {
        // 参数验证
        if ($newBalance < 0) {
            return [
                'code' => 1,
                'msg' => '余额不能为负数',
                'data' => null
            ];
        }
        
        $currentBalance = Account::getCurrentBalance();
        if ($currentBalance === null) {
            return [
                'code' => 1,
                'msg' => '账户未初始化',
                'data' => null
            ];
        }
        
        $amountChange = $newBalance - $currentBalance;
        
        Db::startTrans();
        try {
            // 更新账户余额
            $balance = Account::initOrUpdateBalance($newBalance);
            
            // 创建历史记录
            BalanceHistory::createRecord(
                BalanceHistory::CHANGE_TYPE_MANUAL,
                $amountChange,
                $balance
            );
            
            Db::commit();
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'current_balance' => $balance
                ]
            ];
        } catch (Exception $e) {
            Db::rollback();
            return [
                'code' => 1,
                'msg' => '编辑余额失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
