<?php
return [
    'adminEmail' => 'admin@example.com',
    
    //启用自定义捕获异常
    'enableCustomCatchException' => true,

    //token配置
    'token' => [
        'key' => 'zV6ag0O1RSn5e6a03Te5',
        'issuer' => 'http://yii2.me',
        'audience' => 'http://yii2.me',
        'expiration' => 60 * 60 * 24,
    ],
];
