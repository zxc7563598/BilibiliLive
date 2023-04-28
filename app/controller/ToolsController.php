<?php

namespace app\controller;


use app\model\QuestionAdmin;
use Carbon\Carbon;
use support\Redis;
use support\Request;
use Webman\Http\Response;
use resource\enums\QuestionAdminEnums;

class ToolsController
{
    /**
     * 用户登录
     *
     * @param string $account 账号
     * @param string $password 密码
     * 
     * @return Response
     */
    public function login(Request $request): Response
    {
        $param = $request->data;
        sublog('小工具', '用户登录', $param);
        // 获取数据
        $account = $param['account'];
        $password = $param['password'];
        $ip = $request->user_ip;
        // 执行登陆
        $question_admin = QuestionAdmin::where('account', $account)->first();
        if (empty($question_admin)) {
            $count = QuestionAdmin::count();
            if($count < 2){
                $question_admin_add = new QuestionAdmin();
                $question_admin_add->account = $account;
                $question_admin_add->password = $password;
                $question_admin_add->status = QuestionAdminEnums\Status::Normal->value;
                $question_admin_add->save();
                $question_admin = QuestionAdmin::where('account', $account)->first();
                if (empty($question_admin)) {
                    return fail($request, 800001);
                }
            }else{
                return fail($request, 800001);
            }
        }
        // 状态验证
        if ($question_admin->status == QuestionAdminEnums\Status::Disable->value) {
            return fail($request, 800002);
        }
        // 密码验证
        if (sha1(sha1($password) . $question_admin->salt) != $question_admin->password) {
            return fail($request, 800003);
        }
        // 用户登录token挂载
        $question_admin->token = md5(mt_rand(1000, 9999) . uniqid(md5(microtime(true)), true));
        $question_admin->last_login_at = Carbon::now()->timezone(config('app.timezone'))->timestamp;
        $question_admin->last_login_ip = $ip;
        $question_admin->save();
        Redis::set(config('app')['app_name'] . ':admin_to_token:' . $question_admin->admin_id, $question_admin->token);
        Redis::set(config('app')['app_name'] . ':token_to_admin:' . $question_admin->token, $question_admin->admin_id);
        // 返回数据
        return success($request, [
            'admin_id' => $question_admin->admin_id,
            'token' => $question_admin->token
        ]);
    }
}
