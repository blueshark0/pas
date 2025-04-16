<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\HistoryService;
use think\Request;
use think\Response;

class History extends BaseController
{
    protected $historyService;
    
    public function __construct(HistoryService $historyService)
    {
        $this->historyService = $historyService;
    }
    
    /**
     * 获取历史记录列表
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $page = $request->param('page/d', 1);
        $pageSize = $request->param('page_size/d', 20);
        
        $result = $this->historyService->getHistoryList($page, $pageSize);
        return json($result);
    }
    
    /**
     * 获取特定变更类型的历史记录
     *
     * @param Request $request
     * @param int $type
     * @return Response
     */
    public function byType(Request $request, int $type): Response
    {
        $page = $request->param('page/d', 1);
        $pageSize = $request->param('page_size/d', 20);
        
        $result = $this->historyService->getHistoryByType($type, $page, $pageSize);
        return json($result);
    }
    
    /**
     * 根据日期范围获取历史记录
     *
     * @param Request $request
     * @return Response
     */
    public function byDateRange(Request $request): Response
    {
        $startDate = $request->param('start_date/s', '');
        $endDate = $request->param('end_date/s', '');
        $page = $request->param('page/d', 1);
        $pageSize = $request->param('page_size/d', 20);
        
        // 参数验证
        if (empty($startDate) || empty($endDate)) {
            return json([
                'code' => 1,
                'msg' => '开始日期和结束日期不能为空',
                'data' => null
            ]);
        }
        
        $result = $this->historyService->getHistoryByDateRange($startDate, $endDate, $page, $pageSize);
        return json($result);
    }
    
    /**
     * 获取相关交易的历史记录
     *
     * @param int $transactionId
     * @return Response
     */
    public function byTransaction(int $transactionId): Response
    {
        $result = $this->historyService->getHistoryByTransaction($transactionId);
        return json($result);
    }
}
