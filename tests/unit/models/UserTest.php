<?php

namespace tests\unit\models;

use app\models\User;
use Codeception\Test\Unit;
use Yii;

class UserTest extends Unit
{
    public function testSetPasswordAndValidate()
    {
        $u = new User();
        $u->setPassword('secr3t');
        verify($u->validatePassword('secr3t'))->true();
        verify($u->validatePassword('wrong'))->false();
    }

    public function testFindByUsernameAfterHaveRecord()
    {
        $hash = Yii::$app->security->generatePasswordHash('pw');
        $this->tester->haveRecord(User::class, [
            'username' => 'unit_user_api',
            'auth_key' => 'test-auth-key-xxxxxxxxxx',
            'password_hash' => $hash,
            'first_name' => 'Unit',
            'last_name' => 'User',
        ]);

        $found = User::findByUsername('unit_user_api');
        verify($found)->notEmpty();
        verify($found->first_name)->equals('Unit');
        verify($found->last_name)->equals('User');
    }
}
