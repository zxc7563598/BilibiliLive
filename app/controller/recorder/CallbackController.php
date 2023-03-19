<?php

namespace app\controller\recorder;

use app\model\LiveFiles;
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
        sublog('接口调用', '录播姬回调', $param);
        sublog('接口调用', '录播姬回调', $request->getRealIp());
        sublog('接口调用', '录播姬回调', '===================');
        $type = $param['EventType'];
        $timestamp = $param['EventTimestamp'];
        $id = $param['EventId'];
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
                    // 加入队列，走 python 上传百度网盘
                    Client::send('baidu-netbook-upload', [
                        'files_id' => $live_files->files_id,
                        'files_path' => $live_files->files_path,
                        'files_name' => $live_files->files_name
                    ]);
                }
                break;
        }
        return 'success';
    }
}
