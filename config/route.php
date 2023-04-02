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

Route::disableDefaultRoute();
