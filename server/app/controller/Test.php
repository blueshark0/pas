<?php

namespace app\controller;

use app\BaseController;
use app\model\TestMessage;

class Test extends BaseController {
    public function index($message)
    {
        $messageVo = new TestMessage();

        $messageVo->message = $message;
        $messageVo->save();
        return "success";
    }
}