<?php

return [
    'enable'   => true,
    'mailer'    => [
        'scheme'   => 'smtp', // "smtps": using TLS, "smtp": without using TLS.
        'host'     => 'smtp.qq.com', // 服务器地址
        'username' => '992182040@qq.com', //用户名
        'password' => 'ozavctsqobsnbdia', // 密码
        'port'     => 465, // SMTP服务器端口号,一般为25
        'options'  => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification
    ],
    'from'   => [
        'address' => 'junjie.he.925@gmail.com',
        'name'    => '30118851-温以凝直播间状态变更',
    ],
];
