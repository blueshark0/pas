<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 基础测试路由
Route::get('think', function () {
    return 'hello,ThinkPHP8!';
});

// API路由组
Route::group('api', function () {
    // 账户相关接口
    Route::group('account', function () {
        Route::get('balance', 'account/balance');        // 获取当前余额
        Route::post('init', 'account/init');             // 设置初始余额
        Route::post('edit', 'account/edit');             // 手动编辑余额
    });
    
    // 交易相关接口
    Route::group('transaction', function () {
        Route::get('list', 'transaction/list');           // 获取交易列表
        Route::post('add', 'transaction/add');            // 添加预设交易
        Route::put(':id', 'transaction/update');          // 修改预设交易
        Route::delete(':id', 'transaction/delete');       // 删除预设交易
        Route::post('execute', 'transaction/execute');    // 执行待处理的交易
    });
    
    // 历史记录相关接口
    Route::group('history', function () {
        Route::get('list', 'history/list');                     // 获取历史记录列表
        Route::get('type/:type', 'history/byType');             // 获取特定变更类型的历史记录
        Route::get('date-range', 'history/byDateRange');        // 按日期范围获取历史记录
        Route::get('transaction/:transactionId', 'history/byTransaction'); // 获取特定交易的历史记录
    });
});
