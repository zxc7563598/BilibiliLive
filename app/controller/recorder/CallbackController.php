<?php

namespace app\controller\recorder;

use app\model\LiveDanmu;
use app\model\LiveFiles;
use app\model\LiveGift;
use app\model\LiveRecord;
use Carbon\Carbon;
use support\Redis;
use support\Request;
use yzh52521\mailer\Mailer;
use resource\enums\LiveFilesEnums;
use Webman\RedisQueue\Client;

class CallbackController
{
    /**
     * 录播姬回调
     *
     * @param Request $request
     * 
     * @return string
     */
    public function webHook(Request $request): string
    {
        $param = $request->all();
        sublog('录播姬接口', '录播姬回调', $param);
        sublog('录播姬接口', '录播姬回调', $request->getRealIp());
        sublog('录播姬接口', '录播姬回调', '===================');
        $type = $param['EventType'];
        $timestamp = $param['EventTimestamp'];
        $data = $param['EventData'];
        // 数据无误，验证类型
        switch ($type) {
            case 'StreamStarted': // 直播开始
                // 创建直播记录
                $live_record = new LiveRecord();
                $live_record->room_id = $data['RoomId'];
                $live_record->short_id = $data['ShortId'];
                $live_record->name = $data['Name'];
                $live_record->area_name_parent = $data['AreaNameParent'];
                $live_record->area_name_child = $data['AreaNameChild'];
                $live_record->start_time = Carbon::parse($timestamp)->timezone(config('app')['default_timezone'])->timestamp;
                $live_record->down_time = 0;
                $live_record->duration = 0;
                $live_record->save();
                Redis::set(config('app')['app_name'] . ':recorder:roomid', $data['RoomId']);
                Redis::hSet(config('app')['app_name'] . ':recorder:list', $data['RoomId'], $live_record->live_id);
                Mailer::setFrom(['992182040@qq.com' => "哔哩哔哩直播间通知"])
                    ->setTo('junjie.he.925@gmail.com')
                    ->setSubject('直播间状态变更')
                    ->view('mail/liveStatus', [
                        'name' => $data['RoomId'] . ' - ' . $data['Name'],
                        'title' => $data['Title'],
                        'area' =>  $data['AreaNameParent'] . ' - ' . $data['AreaNameChild'],
                        'type' => '直播开始',
                        'date' => Carbon::parse($timestamp)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
                        'times' => null
                    ])->send();
                break;
            case 'StreamEnded': // 直播结束
                $live_id = Redis::hGet(config('app')['app_name'] . ':recorder:list', $data['RoomId']);
                if (empty($live_id)) {
                    $live_record = LiveRecord::where('start_time', '>', 0)->where('down_time', 0)->first();
                } else {
                    $live_record = LiveRecord::where('live_id', $live_id)->first();
                }
                if (!empty($live_record)) {
                    $live_record->down_time = Carbon::parse($timestamp)->timezone(config('app')['default_timezone'])->timestamp;
                    $live_record->duration = $live_record->down_time - $live_record->start_time;
                    $live_record->save();
                }
                Redis::del(config('app')['app_name'] . ':recorder:roomid');
                Redis::hDel(config('app')['app_name'] . ':recorder:list', $data['RoomId']);
                Mailer::setFrom(['992182040@qq.com' => "哔哩哔哩直播间通知"])
                    ->setTo('junjie.he.925@gmail.com')
                    ->setSubject('直播间状态变更')
                    ->view('mail/liveStatus', [
                        'name' => $data['RoomId'] . ' - ' . $data['Name'],
                        'title' => $data['Title'],
                        'area' =>  $data['AreaNameParent'] . ' - ' . $data['AreaNameChild'],
                        'type' => '直播结束',
                        'date' => Carbon::parse($timestamp)->timezone(config('app')['default_timezone'])->format('Y-m-d H:i:s'),
                        'times' => isset($live_record->duration) ? sec2Time($live_record->duration) : 'N/A'
                    ])->send();
                break;
            case 'FileOpening': // 文件打开
                $live_id = Redis::hGet(config('app')['app_name'] . ':recorder:list', $data['RoomId']);
                // 创建文件记录
                $live_files = new LiveFiles();
                $live_files->live_id = $live_id;
                $live_files->room_id = $data['RoomId'];
                $live_files->short_id = $data['ShortId'];
                $live_files->name = $data['Name'];
                $live_files->title = $data['Title'];
                $live_files->area_name_parent = $data['AreaNameParent'];
                $live_files->area_name_child = $data['AreaNameChild'];
                $files_name = $files_path = '';
                $path = explode('/', $data['RelativePath']);
                if (count($path) > 1) {
                    $files_name = $path[count($path) - 1];
                    unset($path[count($path) - 1]);
                    $files_path = '/www/wwwdata/' . implode('/', $path);
                }
                $live_files->files_path = $files_path;
                $live_files->files_name = $files_name;
                $live_files->start_time = Carbon::parse($timestamp)->timezone(config('app')['default_timezone'])->timestamp;
                $live_files->down_time = 0;
                $live_files->duration = 0;
                $live_files->status = LiveFilesEnums\Status::Recording->value;
                $live_files->save();
                break;
            case 'FileClosed': // 文件关闭
                $live_id = Redis::hGet(config('app')['app_name'] . ':recorder:list', $data['RoomId']);
                // 获取对应文件记录
                $files_name = $files_path = '';
                $path = explode('/', $data['RelativePath']);
                if (count($path) > 1) {
                    $files_name = $path[count($path) - 1];
                    unset($path[count($path) - 1]);
                    $files_path = '/www/wwwdata/' . implode('/', $path);
                }
                $live_files = LiveFiles::where('room_id', $data['RoomId'])->where('files_path', $files_path)->where('files_name', $files_name)->first();
                if (!empty($live_files)) {
                    $live_files->down_time = Carbon::parse($timestamp)->timezone(config('app')['default_timezone'])->timestamp;
                    $live_files->duration = $live_files->down_time - $live_files->start_time;
                    $live_files->status = LiveFilesEnums\Status::ToUpload->value;
                    $live_files->save();
                    // 上传到哔哩哔哩
                    sublog('biliup', '上传确认', $live_files->files_name . '录制完成，检查视频是否需要投稿');
                    if ($live_files->duration > 30) {
                        sublog('biliup', '上传确认', '视频可以进行投稿，进行信息获取');
                        $file = $live_files->files_path . '/' . $live_files->files_name;
                        $title = '【直播回放】温以泠 ' . Carbon::parse($live_files->start_time)->timezone(config('app')['default_timezone'])->format('Y年m月d日H') . '点场';
                        $log = base_path() . '/runtime/logs/' . Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d') . '/biliup/视频上传.log';
                        $dtime = Carbon::now()->addHours(8)->timezone(config('app')['default_timezone'])->timestamp;
                        $desc = '关注主播「温以泠」好吗，求求了求求了求求了';
                        sublog('biliup', '上传确认', '投稿视频路径：' . $file);
                        sublog('biliup', '上传确认', '投稿名称：' . $title);
                        sublog('biliup', '上传确认', '打印日志地址：' . $log);
                        if (!is_dir(base_path() . '/runtime/biliup_log/')) {
                            mkdir(base_path() . '/runtime/biliup_log/', 0777, true);
                        }
                        $shell = 'biliup upload ' . $file . ' --tid 27 --tag 直播回放 --title "' . $title . '" --cover /www/wwwdata/封面.jpeg --dtime ' . $dtime . ' --line qn --desc "' . $desc . '" &>> ' . $log . ' &';
                        sublog('biliup', '上传确认', '执行命令' . $shell);
                        $dir = '/root'; // 指定要执行命令的目录
                        chdir($dir); // 更改当前工作目录为指定的目录
                        $output = shell_exec($shell); // 执行 shell 命令
                        sublog('biliup', '上传确认', $output);
                    } else {
                        sublog('biliup', '上传确认', '视频时长不超过30秒，不进行投稿');
                    }
                    sublog('biliup', '上传确认', '==========');
                }
                break;
        }
        return 'success';
    }

    public function webHookTest(Request $request)
    {
        $data = $request->all();
        $message = [];
        $cmd = '';
        if ($data) {
            $cmd = $data['cmd'];
            switch ($data['cmd']) {
                case 'danmu': // 弹幕
                    $message = [
                        '类型' => "弹幕",
                        "房管" => $data['result']['manager'],
                        '牌子主播' => $data['result']['medal_anchor'],
                        '牌子名称' => $data['result']['medal_name'],
                        "牌子等级" => $data['result']['medal_level'],
                        "舰长类型" => $data['result']['uguard'],
                        "名称" => $data['result']['uname'],
                        "发送信息" => $data['result']['msg'],
                        "时间戳" => $data['result']['timestamp'],
                        "状态" => $data['status']
                    ];
                    $live_id = Redis::hGet(config('app')['app_name'] . ':recorder:list', '26507836');
                    $live_danmu = new LiveDanmu();
                    $live_danmu->live_id = $live_id;
                    $live_danmu->manager = $data['result']['manager'];
                    $live_danmu->medal_anchor = $data['result']['medal_anchor'];
                    $live_danmu->medal_name = $data['result']['medal_name'];
                    $live_danmu->medal_level = $data['result']['medal_level'];
                    $live_danmu->uguard = $data['result']['uguard'];
                    $live_danmu->uid = $data['result']['uid'];
                    $live_danmu->uname = $data['result']['uname'];
                    $live_danmu->msg = $data['result']['msg'];
                    $live_danmu->timestamp = round(($data['result']['timestamp'] / 1000));
                    $live_danmu->status = $data['status'];
                    $live_danmu->save();
                    break;
                case 'gift':
                    $message = [
                        '类型' => "礼物",
                        "赠送类型" => $data['result']['action'],
                        "礼物id" => $data['result']['giftId'],
                        "礼物名称" => $data['result']['giftName'],
                        "看起来是是否免费" => $data['result']['giftType'],
                        "礼物级别" => $data['result']['guard_level'],
                        "数量" => $data['result']['num'],
                        "价格" => $data['result']['price'],
                        "赠送人uid" => $data['result']['uid'],
                        "赠送人名称" => $data['result']['uname'],
                        "状态" => $data['status']
                    ];
                    $live_id = Redis::hGet(config('app')['app_name'] . ':recorder:list', '26507836');
                    $live_gift = new LiveGift();
                    $live_gift->live_id = $live_id;
                    $live_gift->action = $data['result']['action'];
                    $live_gift->id = $data['result']['giftId'];
                    $live_gift->gift_name = $data['result']['giftName'];
                    $live_gift->gift_type = $data['result']['giftType'];
                    $live_gift->gift_level = $data['result']['guard_level'];
                    $live_gift->num = $data['result']['num'];
                    $live_gift->price = round(($data['result']['price'] * $data['result']['num']) / 10);
                    $live_gift->uid = $data['result']['uid'];
                    $live_gift->uname = $data['result']['uname'];
                    $live_gift->status = $data['status'];
                    $live_gift->save();
                    break;
                case 'superchat':
                    $live_id = Redis::hGet(config('app')['app_name'] . ':recorder:list', '26507836');
                    $live_gift = new LiveGift();
                    $live_gift->live_id = $live_id;
                    $live_gift->action = '发送';
                    $live_gift->id = $data['result']['gift']['gift_id'];
                    $live_gift->gift_name = $data['result']['gift']['gift_name'];
                    $live_gift->gift_type = 0;
                    $live_gift->gift_level = 0;
                    $live_gift->num = 1;
                    $live_gift->price = $data['result']['price'] * 100;
                    $live_gift->uid = $data['result']['uid'];
                    $live_gift->uname = $data['result']['user_info']['uname'];
                    $live_gift->status = $data['status'];
                    $live_gift->save();
                    break;
                default:
                    $message = [
                        "类型" => "未定义",
                        "内容" => $data
                    ];
                    break;
            }
            sublog('长链接测试', '弹幕机链接', $message);
            sublog('长链接测试', '原始数据', $data);
        }
        return success($request, $cmd);
    }

    /**
     * 录播文件上传回调
     *
     * @param string $files_id 文件id
     * @param integer $success 是否上传成功
     * 
     * @return string
     */
    public function fileCallback(Request $request): string
    {
        $param = $request->all();
        sublog('录播姬接口', '文件上传回调', $param);
        $files_id = $param['files_id'];
        $success = $param['success'];
        // 获取数据进行处理
        $live_files = LiveFiles::where('files_id', $files_id)->first();
        if (!empty($live_files)) {
            $live_files->status = $success == 1 ? LiveFilesEnums\Status::UploadSuccessful->value : LiveFilesEnums\Status::UploadFailed->value;
            $live_files->save();
        }
        return 'success';
    }
}
