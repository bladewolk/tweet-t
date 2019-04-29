<?php

namespace app\models;

use yii\db\ActiveRecord;

class User extends ActiveRecord
{
//    public function setUsername($value)
//    {
//        $this->username = $value;
//
//        return $this;
//    }

    public static function tableName()
    {
        return 'users';
    }

    public function rules()
    {
        return [
            [['user'], 'required']
        ];
    }
}
