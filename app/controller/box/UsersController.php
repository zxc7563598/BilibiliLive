<?php

namespace app\controller\box;

use app\model\QuestionBox;
use Carbon\Carbon;
use support\Request;
use Webman\Http\Response;
use Suqingan\Network;

class UsersController
{

    /**
     * 用户投稿
     *
     * @param string $type 类型
     * @param string $content 内容
     * @param string $real_name 名称
     * 
     * @return Response
     */
    public function contribute(Request $request): Response
    {
        $param = $request->data;
        $user_ip = $request->user_ip;
        sublog('棉花糖接口/用户端', '用户投稿', $param);
        // 获取数据
        $type = $param['type'];
        $content = $param['content'];
        if (empty($content)) {
            return fail($request, 800005);
        }
        $real_name = '匿名用户' . Carbon::now()->timezone(config('app.timezone'))->timestamp . mt_rand(1000, 9999);
        // 存储投稿
        $question_box = new QuestionBox();
        $question_box->ip = $user_ip;
        $question_box->type = $type;
        $question_box->real_name = $real_name;
        $question_box->content = $content;
        $question_box->ip_address = '未知';
        if (!empty($user_ip)) {
            $url = 'https://api.ip138.com/ip/?ip=' . $user_ip . '&datatype=jsonp&token=a1eeb621116ed48d0b5cd9d1069a3694';
            $ip_data = Network\Curl::Get($url, 'json');
            if ($ip_data['code'] == 200) {
                $address = json_decode($ip_data['data'], true);
                if (!empty($address['data'][0])) {
                    $question_box->ip_address = $address['data'][0];
                }
                if (!empty($address['data'][1])) {
                    $question_box->ip_address .= ' - ' . $address['data'][1];
                }
                if (!empty($address['data'][2])) {
                    $question_box->ip_address .= ' - ' . $address['data'][2];
                }
                if (!empty($address['data'][3])) {
                    $question_box->ip_address .= ' - ' . $address['data'][3];
                }
            }
        }
        $question_box->save();
        // 返回数据
        return success($request, $param);
    }
}
