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

namespace app\middleware;

use app\model\QuestionAdmin;
use app\model\Users;
use Carbon\Carbon;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Redis;

/**
 * Api鉴权
 * @package app\middleware
 */
class LotteryAuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 获取路由数据
        $route = $request->route;
        $path = $route->getPath();
        $methods = $request->method();
        if (empty($methods)) {
            $methods = 'UNKNOWN';
        }
        Redis::hIncrBy(config('app')['app_name'] . ':lottery:request', $path, 1);
        $param = $request->all();
        // 验证时间是否正确
        $difference = Carbon::now()->timezone(config('app')['default_timezone'])->diffInSeconds(Carbon::parse($param['timestamp'])->timezone(config('app')['default_timezone']));
        if ($difference > 60) {
            return fail($request, 900001);
        }
        // 验证签名
        if (md5(config('app')['key'] . $param['timestamp']) != $param['sign']) {
            return fail($request, 900002);
        }
        // 解密数据
        $data = openssl_decrypt($param['en_data'], 'aes-128-cbc', config('app')['aes_key'], 0, config('app')['aes_iv']);
        if (!$data) {
            return fail($request, 900003);
        }
        // 完成签名验证，没问题
        $request->data = json_decode($data, true);
        // 获取ip
        $request->user_ip = isset($param['user_ip']) ? $param['user_ip'] : null;
        // 验证用户登录
        if (in_array($path, [
            'lujing/lujingaaa'
        ])) {
            $token = isset($param['token']) ? $param['token'] : null;
            if (empty($token)) {
                return fail($request, 800004);
            }
            $admin_id = Redis::get(config('app')['app_name'] . ':token_to_admin:' . $token);
            if (empty($admin_id)) {
                return fail($request, 800004);
            }
            $last_token = Redis::get(config('app')['app_name'] . ':admin_to_token:' . $admin_id);
            if ($last_token != $token) {
                return fail($request, 800004);
            }
            $admins = QuestionAdmin::where('admin_id', $admin_id)->first();
            if (empty($admins)) {
                return fail($request, 800004);
            }
            $request->admins = $admins;
        }
        // 继续处理
        $response = $next($request);
        Redis::hIncrBy(config('app')['app_name'] . ':lottery:success', $path, 1);
        // 处理成功，返回阶段
        if (isset($request->res['code']) && $request->res['code'] == 0) {
            Redis::hIncrBy(config('app')['app_name'] . ':lottery:error', $path, 1);
        }
        return $response;
    }
}
