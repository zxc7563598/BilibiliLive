<?php

namespace app\controller\box;

use app\model\QuestionAdmin;
use app\model\QuestionBox;
use Carbon\Carbon;
use support\Redis;
use support\Request;
use Webman\Http\Response;
use resource\enums\QuestionBoxEnums;
use resource\enums\QuestionAdminEnums;

class MainController
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
        sublog('棉花糖接口/管理端', '用户登录', $param);
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

    /**
     * 展示投稿箱信息
     * 
     * @param integer $type 投稿类型
     * @param integer $read 是否显示已读
     *
     * @return Response
     */
    public function main(Request $request): Response
    {
        $param = $request->data;
        $admins = $request->admins;
        $param['account'] = $admins->account;
        sublog('棉花糖接口/管理端', '投稿箱查看', $param);
        // 获取数据
        $type = isset($param['type']) ? $param['type'] : null;
        $read = isset($param['read']) ? $param['read'] : QuestionBoxEnums\Read::Yes->value;
        // 获取投稿
        $question_box = new QuestionBox();
        if ($read == QuestionBoxEnums\Read::No->value) {
            $question_box = $question_box->where('read', QuestionBoxEnums\Read::No->value);
        }
        $question_box = $question_box->orderBy('created_at', 'asc')->get([
            'box_id' => 'box_id',
            'type' => 'type',
            'real_name' => 'real_name',
            'content' => 'content',
            'ip_address' => 'ip_address',
            'read' => 'read',
            'created_at' => 'created_at'
        ]);
        foreach ($question_box as &$_box) {
            $_box->create_time = $_box->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }
        // 返回数据
        return success($request, [
            'question_box' => $question_box
        ]);
    }

    /**
     * 标记投稿已读
     *
     * @param integer $box_id 投稿id
     * 
     * @return Response
     */
    public function read(Request $request): Response
    {
        $param = $request->data;
        $admins = $request->admins;
        sublog('棉花糖接口/管理端', '标记已读', $param);
        // 获取数据
        $box_id = $param['box_id'];
        // 获取数据
        $question_box = QuestionBox::where('box_id', $box_id)->first();
        if (!empty($question_box)) {
            $question_box->read = QuestionBoxEnums\Read::Yes->value;
            $question_box->save();
        }
        // 返回数据
        return success($request);
    }
}
