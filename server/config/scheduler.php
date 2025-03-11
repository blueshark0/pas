<?php

use think\facade\Db;
use think\facade\Log;
use think\facade\Env;
use think\facade\Config;
use think\facade\Console;
use think\facade\Cache;
use think\facade\Hook;
use think\facade\Route;
use think\facade\Validate;
use think\facade\View;
use think\facade\Request;
use think\facade\Response;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Lang;
use think\facade\Url;
use think\facade\Filesystem;
use think\facade\Env;
use think\facade\Config;
use think\facade\Console;
use think\facade\Cache;
use think\facade\Hook;
use think\facade\Route;
use think\facade\Validate;
use think\facade\View;
use think\facade\Request;
use think\facade\Response;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Lang;
use think\facade\Url;
use think\facade\Filesystem;

return [
    'schedule' => [
        'recurring_expenses' => [
            'cron' => '0 0 * * *', // Run daily at midnight
            'command' => function () {
                $recurringExpenses = Db::table('income_expense')
                    ->where('period', 'monthly')
                    ->whereDay('date', date('d'))
                    ->get();

                foreach ($recurringExpenses as $expense) {
                    Db::table('income_expense')->insert([
                        'period' => $expense->period,
                        'amount' => $expense->amount,
                        'date' => date('Y-m-d'),
                    ]);
                }

                Log::info('Recurring expenses added successfully');
            },
        ],
    ],
];
