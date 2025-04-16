<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\TransactionService;
use think\Request;
use think\Response;

class Transaction extends BaseController
{
    protected $transactionService;
    
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }
    
    /**
     * 获取交易列表
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $status = $request->param('status/d');
        $page = $request->param('page/d', 1);
        $pageSize = $request->param('page_size/d', 20);
        
        $result = $this->transactionService->getTransactionList($status, $page, $pageSize);
        return json($result);
    }
    
    /**
     * 添加预设交易
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        $data = [
            'type' => $request->post('type/d'),
            'amount' => $request->post('amount/f'),
            'execution_date' => $request->post('execution_date/s'),
            'description' => $request->post('description/s', ''),
            'is_recurring' => $request->post('is_recurring/d', 0),
        ];
        
        // 如果是周期性交易，获取相关字段
        if ($data['is_recurring'] == 1) {
            $data['recurrence_type'] = $request->post('recurrence_type/d', 0);
            $data['recurrence_interval'] = $request->post('recurrence_interval/d', 1);
            $data['recurrence_end_date'] = $request->post('recurrence_end_date/s', '');
        }
        
        $result = $this->transactionService->addTransaction($data);
        return json($result);
    }
    
    /**
     * 修改预设交易
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, int $id): Response
    {
        $data = [];
        
        // 判断请求中是否包含这些字段，包含则添加到更新数据中
        if ($request->has('amount')) {
            $data['amount'] = $request->post('amount/f');
        }
        
        if ($request->has('execution_date')) {
            $data['execution_date'] = $request->post('execution_date/s');
        }
        
        if ($request->has('description')) {
            $data['description'] = $request->post('description/s');
        }
        
        if ($request->has('is_recurring')) {
            $data['is_recurring'] = $request->post('is_recurring/d');
            
            if ($data['is_recurring'] == 1) {
                if ($request->has('recurrence_type')) {
                    $data['recurrence_type'] = $request->post('recurrence_type/d');
                }
                
                if ($request->has('recurrence_interval')) {
                    $data['recurrence_interval'] = $request->post('recurrence_interval/d');
                }
                
                if ($request->has('recurrence_end_date')) {
                    $data['recurrence_end_date'] = $request->post('recurrence_end_date/s');
                }
            }
        }
        
        $result = $this->transactionService->updateTransaction($id, $data);
        return json($result);
    }
    
    /**
     * 删除预设交易
     *
     * @param int $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        $result = $this->transactionService->deleteTransaction($id);
        return json($result);
    }
    
    /**
     * 执行待处理的交易
     *
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request): Response
    {
        $date = $request->param('date/s', date('Y-m-d'));
        $result = $this->transactionService->executeTransactions($date);
        return json($result);
    }
}
