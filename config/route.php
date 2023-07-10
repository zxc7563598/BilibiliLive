<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// Route::options('[{path:.+}]', function (){
//     return response('');
// });
Route::any('/testPage', [app\controller\box\UsersController::class, 'testPage']); // 用户投稿

route::any('/user/user_img_upload', [app\controller\LiveInstructionController::class, 'imageUpload']); // %E5%9B%BE%E7%89%87%E4%B8%8A%E4%BC%A0

Route::any('/live-instruction/live-info', [app\controller\LiveInstructionController::class, 'liveInfo']); // 直播信息接口 - 获取指定时间，指定房间的直播信息
Route::any('/live-instruction/live-status', [app\controller\LiveInstructionController::class, 'liveStatus']); // 直播信息接口 - 获取指定房间的直播状态

Route::any('/recorder/webhook', [app\controller\recorder\CallbackController::class, 'webHook']); // 录播姬 webhook 通知
Route::any('/recorder/webhook/test', [app\controller\recorder\CallbackController::class, 'webHookTest']); // 录播姬 webhook 通知测试
Route::any('/recorder/file-callback', [app\controller\recorder\CallbackController::class, 'fileCallback']); // 录播姬录制视频上传回调

// 投稿箱
Route::group('/box', function () {
    Route::post('/contribute', [app\controller\box\UsersController::class, 'contribute']); // 用户投稿
    Route::post('/login', [app\controller\box\MainController::class, 'login']); // 管理员登陆
    Route::post('/main', [app\controller\box\MainController::class, 'main']); // 管理员查看投稿列表
    Route::post('/read', [app\controller\box\MainController::class, 'read']); // 标记已读
})->middleware([
    app\middleware\BoxAuthCheck::class
]);

// 抽奖
Route::group('/lottery', function () {
    Route::post('/user/list', [app\controller\lottery\UserController::class, 'main']); // 用户端获取列表
    Route::post('/user/details', [app\controller\lottery\UserController::class, 'details']); // 用户端获取详情
    Route::post('/user/bind-uid', [app\controller\lottery\UserController::class, 'bindUid']); // 用户端绑定盲盒 
    Route::post('/user/random-box', [app\controller\lottery\UserController::class, 'randomBox']); // 打乱盲盒
    Route::post('/user/get-prize', [app\controller\lottery\UserController::class, 'getPrize']); // 获取中奖信息
    Route::post('/main/list', [app\controller\lottery\MainController::class, 'main']); // 管理端查看列表
    Route::post('/main/details', [app\controller\lottery\MainController::class, 'details']); // 管理段获取信息
    Route::post('/main/set', [app\controller\lottery\MainController::class, 'setLottery']); // 管理端变更信息
    Route::post('/main/get-box', [app\controller\lottery\MainController::class, 'getBox']); // 管理端获取盒子
    Route::post('/main/set/box', [app\controller\lottery\MainController::class, 'setBox']); // 配置盒子
})->middleware([
    app\middleware\LotteryAuthCheck::class
]);

// 小工具接口
Route::group('/tools', function () {
    Route::post('/login', [app\controller\ToolsController::class, 'login']); // 登陆
    // 投稿箱
    Route::post('/box/login', [app\controller\box\MainController::class, 'login']); // 管理员登陆
    Route::post('/box/main', [app\controller\box\MainController::class, 'main']); // 管理员查看投稿列表
    Route::post('/box/read', [app\controller\box\MainController::class, 'read']); // 标记已读
    // 抽奖
    Route::post('/lottery/user/list', [app\controller\lottery\UserController::class, 'main']); // 用户端获取列表
    Route::post('/lottery/user/details', [app\controller\lottery\UserController::class, 'details']); // 用户端获取详情
    Route::post('/lottery/user/bind-uid', [app\controller\lottery\UserController::class, 'bindUid']); // 用户端绑定盲盒 
    Route::post('/lottery/user/random-box', [app\controller\lottery\UserController::class, 'randomBox']); // 打乱盲盒
    Route::post('/lottery/user/get-prize', [app\controller\lottery\UserController::class, 'getPrize']); // 获取中奖信息
    Route::post('/lottery/main/list', [app\controller\lottery\MainController::class, 'main']); // 管理端查看列表
    Route::post('/lottery/main/details', [app\controller\lottery\MainController::class, 'details']); // 管理段获取信息
    Route::post('/lottery/main/set', [app\controller\lottery\MainController::class, 'setLottery']); // 管理端变更信息
    Route::post('/lottery/main/get-box', [app\controller\lottery\MainController::class, 'getBox']); // 管理端获取盒子
    Route::post('/lottery/main/set/box', [app\controller\lottery\MainController::class, 'setBox']); // 配置盒子
})->middleware([
    app\middleware\ToolsAuthCheck::class
]);


Route::disableDefaultRoute();
