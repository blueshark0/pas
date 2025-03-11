<?php

namespace app\controller;

use app\BaseController;
use app\model\Balance;
use think\Request;

class BalanceController extends BaseController
{
    public function add(Request $request)
    {
        $data = $request->post();
        $this->validate($data, [
            'initial_balance' => 'require|float',
            'date' => 'require|date',
        ]);

        $balance = new Balance();
        $balance->initial_balance = $data['initial_balance'];
        $balance->date = $data['date'];
        $balance->save();

        return json(['message' => 'Balance entry added successfully']);
    }

    public function update(Request $request, $id)
    {
        $data = $request->put();
        $this->validate($data, [
            'initial_balance' => 'require|float',
            'date' => 'require|date',
        ]);

        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => 'Balance entry not found'], 404);
        }

        $balance->initial_balance = $data['initial_balance'];
        $balance->date = $data['date'];
        $balance->save();

        return json(['message' => 'Balance entry updated successfully']);
    }

    public function delete($id)
    {
        $balance = Balance::find($id);
        if (!$balance) {
            return json(['message' => 'Balance entry not found'], 404);
        }

        $balance->delete();

        return json(['message' => 'Balance entry deleted successfully']);
    }
}
