<?php

namespace app\model;

use think\Model;

class Balance extends Model
{
    protected $table = 'balance';
    protected $fillable = ['initial_balance', 'date'];

    public function addBalance($data)
    {
        return $this->create($data);
    }

    public function updateBalance($id, $data)
    {
        $balance = $this->find($id);
        if ($balance) {
            $balance->update($data);
            return $balance;
        }
        return null;
    }

    public function deleteBalance($id)
    {
        $balance = $this->find($id);
        if ($balance) {
            $balance->delete();
            return true;
        }
        return false;
    }
}
