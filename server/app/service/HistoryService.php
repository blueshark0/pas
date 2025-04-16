<?php
declare(strict_types=1);

namespace app\service;

use app\model\BalanceHistory;

/**
 * 历史记录服务类
 */
class HistoryService
{
    /**
     * 获取历史记录列表
     *
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array
     */
    public function getHistoryList(int $page = 1, int $pageSize = 20): array
    {
        $result = BalanceHistory::getHistoryList($page, $pageSize);
        
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => $result
        ];
    }
    
    /**
     * 获取特定变更类型的历史记录
     *
     * @param int $changeType 变更类型
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array
     */
    public function getHistoryByType(int $changeType, int $page = 1, int $pageSize = 20): array
    {
        $query = BalanceHistory::where('change_type', $changeType);
        
        $list = $query->order('timestamp', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
            
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
     * 获取指定时间段内的历史记录
     *
     * @param string $startDate 开始日期 (Y-m-d)
     * @param string $endDate 结束日期 (Y-m-d)
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array
     */
    public function getHistoryByDateRange(
        string $startDate, 
        string $endDate, 
        int $page = 1, 
        int $pageSize = 20
    ): array {
        // 将日期转换为完整的时间格式
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';
        
        $query = BalanceHistory::where('timestamp', '>=', $startDateTime)
            ->where('timestamp', '<=', $endDateTime);
        
        $list = $query->order('timestamp', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
            
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
     * 获取相关交易的历史记录
     *
     * @param int $transactionId 交易ID
     * @return array
     */
    public function getHistoryByTransaction(int $transactionId): array
    {
        $list = BalanceHistory::where('related_transaction_id', $transactionId)
            ->order('timestamp', 'desc')
            ->select()
            ->toArray();
        
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $list,
                'total' => count($list)
            ]
        ];
    }
}
