<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\AccountService;
use think\Request;
use think\Response;

class Account extends BaseController
{
    protected $accountService;
    
    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }
    
    /**
     * 获取当前余额
     *
     * @return Response
     */
    public function balance(): Response
    {
        $result = $this->accountService->getBalance();
        return json($result);
    }
    
    /**
     * 设置初始余额
     *
     * @param Request $request
     * @return Response
     */
    public function init(Request $request): Response
    {
        $initialBalance = $request->post('initial_balance/f', 0);
        $result = $this->accountService->initBalance($initialBalance);
        return json($result);
    }
    
    /**
     * 手动编辑余额
     *
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request): Response
    {
        $newBalance = $request->post('new_balance/f', 0);
        $result = $this->accountService->editBalance($newBalance);
        return json($result);
    }
}
