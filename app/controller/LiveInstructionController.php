<?php

namespace app\controller;

use app\model\LiveDanmu;
use app\model\LiveFiles;
use app\model\LiveGift;
use app\model\LiveRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use support\Redis;
use support\Request;
use yzh52521\mailer\Mailer;
use resource\enums\LiveFilesEnums;
use support\Db as SupportDb;
use Webman\RedisQueue\Client;

class LiveInstructionController
{

    /**
     * 获取特定主播当前是否在直播
     *
     * @param string $room_id 房间号
     * 
     * @return string
     */
    public function liveStatus(Request $request): string
    {
        $param = $request->all();
        sublog('系统接口', '获取特定主播当前是否在直播', $param);
        $room_id = $param['room_id'];
        // 获取信息
        if (!empty(Redis::hGet(config('app')['app_name'] . ':recorder:list', $room_id))) {
            $message = '这位主播现在正在直播，请稍等，我会帮你跳转到她的直播间。' . "\r\n" . '<br>';
        } else {
            $live_record = LiveRecord::where('room_id', $room_id)->orderBy('start_time', 'desc')->first();
            $message = '当前这位主播暂时还没有直播。' . "\r\n" . '<br>';
            if (!empty($live_record)) {
                $message .= '我这边查询到她最后一次直播是在' . Carbon::parse($live_record['start_time'])->timezone(config('app.timezone'))->format('m月d日') . '。' . "\r\n" . '<br>';
                $message .= '那一次他直播了' . sec2Time($live_record['duration']) . '。';
            }
        }
        return $message;
    }

    /**
     * 获取某日直播的详细信息
     *
     * @param string $room_id 房间号
     * @param string $date 日期
     * 
     * @return string
     */
    public function liveInfo(Request $request): string
    {
        $param = $request->all();
        sublog('系统接口', '获取某日直播的详细信息', $param);
        $room_id = $param['room_id'];
        $start_time = Carbon::parse($param['date'] . ' 00:00:00')->timezone(config('app.timezone'))->timestamp;
        $end_time = Carbon::parse($param['date'] . ' 23:59:59')->timezone(config('app.timezone'))->timestamp;
        $date = [$start_time, $end_time];
        // 获取数据进行处理
        $live_record = LiveRecord::where('room_id', $room_id)->whereBetween('start_time', $date)->orderBy('start_time', 'asc')->get([
            'live_id' => 'live_id',
            'room_id' => 'room_id',
            'name' => 'name',
            'area_name_parent' => 'area_name_parent',
            'area_name_child' => 'area_name_child',
            'start_time' => 'start_time',
            'down_time' => 'down_time',
            'duration' => 'duration'
        ]);
        $live_gift = [];
        $live_danmu = [];
        // 处理数据
        if ($live_record->count()) {
            $message = '';
            $name = '';
            $duration = 0;
            $live_id = [];
            foreach ($live_record as $record) {
                $name = $record->name;
                $duration += $record->duration;
                $live_id[] = $record->live_id;
            }
            if (count($live_id)) {
                $live_gift = self::giftInfo($live_id);
                $live_danmu = self::danmuInfo($live_id);
            }
            $message .= '是这样的，我这边有查询到：' . $name . ' 在' . Carbon::parse($param['date'])->timezone(config('app.timezone'))->format('m月d日') . '的直播信息。' . "\r\n" . '<br>';
            if ($duration) {
                $message .= '他在那一天播了' . $live_record->count() . '场。' . "\r\n" . '<br>';
                if ($live_record->count() == 1) {
                    $message .= Carbon::parse($live_record[0]['start_time'])->timezone(config('app.timezone'))->format('H点i分') . '开始，' . Carbon::parse($live_record[0]['down_time'])->timezone(config('app.timezone'))->format('H点i分') . '结束。' . "\r\n" . '<br>';
                }
                $message .= '共计' . sec2Time($duration) . '。' . "\r\n" . '<br>';
            }
            // 弹幕信息
            if (count($live_danmu)) {
                $danmu = 0;
                foreach ($live_danmu as $_live_danmu) {
                    $danmu += $_live_danmu['num'];
                }
                $message .= '一共有' . count($live_danmu) . '位用户，累计发送了' . $danmu . '条弹幕。';
                if (count($live_danmu) > 3) {
                    $message .= '其中发言最多的人是：' . $live_danmu[0]['uname'] . '。' . "\r\n" . '<br>';
                    $message .= '他一个人就发送了' . $live_danmu[0]['num'] . '条弹幕。' . "\r\n" . '<br>';
                    $message .= '其次是' . $live_danmu[1]['uname'] . ' 跟 ' . $live_danmu[2]['uname'] . '。' . "\r\n" . '<br>';
                    $message .= '分别发送了' . $live_danmu[1]['num'] . '条 与 ' . $live_danmu[2]['num'] . '条。' . "\r\n" . '<br>';
                    $danmu_rate = number_format((round((($live_danmu[0]['num'] + $live_danmu[1]['num'] + $live_danmu[2]['num']) / $danmu), 4) * 100), 2);
                    if ($danmu_rate > 10) {
                        $message .= '他们三个人占据了全天直播 ' . $danmu_rate . '% 的弹幕量，看起来直播间的其他人都不是很活跃。' . "\r\n" . '<br>';
                    }
                }
            }
            // 礼物信息
            if (count($live_gift)) {
                $message .= '在那天的直播中，共有' . count($live_gift) . '位用户送出了礼物' . "\r\n" . '<br>';
                if (count($live_gift) > 3) {
                    $message .= '排名前三位分别为：' . "\r\n" . '<br>';
                    $message .= $live_gift[0]['name'] . '，总计送出了价值' . round(($live_gift[0]['price'] / 100), 2) . '元的礼物。' . "\r\n" . '<br>';
                    $message .= $live_gift[1]['name'] . '，总计送出了价值' . round(($live_gift[1]['price'] / 100), 2) . '元的礼物。' . "\r\n" . '<br>';
                    $message .= $live_gift[2]['name'] . '，总计送出了价值' . round(($live_gift[2]['price'] / 100), 2) . '元的礼物。' . "\r\n" . '<br>';
                }
                $gift = [];
                $price = 0;
                foreach ($live_gift as $_live_gift) {
                    foreach ($_live_gift['gift_info'] as $gift_info) {
                        if (!isset($gift[$gift_info['name']])) {
                            $gift[$gift_info['name']] = [
                                'name' => $gift_info['name'],
                                'num' => 0,
                                'price' => 0
                            ];
                        }
                        $gift[$gift_info['name']]['num'] += $gift_info['num'];
                        $gift[$gift_info['name']]['price'] += $gift_info['price'];
                    }
                    $price += $_live_gift['price'];
                }
                $gift = arraySort($gift, 'price', 'desc');
                if (count($live_gift) > 3) {
                    $message .= '以及其他' . (count($live_gift) - 3) . '位观众，总计赠送了 ' . round(($price / 100), 2) . ' 元的礼物。' . "\r\n" . '<br>';
                }
                if (count($gift) > 3) {
                    $message .= '按照价值排序为：' . "\r\n" . '<br>';
                    foreach ($gift as $_gift) {
                        $message .= $_gift['num'] . '个' . $_gift['name'] . '，价值' . round(($_gift['price'] / 100), 2) . ' 元。' . "\r\n" . '<br>';
                    }
                }
            } else {
                $message .= '只可惜，有可能是我的计算错误，因为我并没有找到有人赠送给她礼物。' . "\r\n" . '<br>';
            }
        } else {
            $message = '根据我这边掌握的信息，在您需要的时间段，这位主播并没有开播。';
        }
        return $message;
    }

    /**
     * 获取直播场次的礼物信息
     *
     * @param array $live_id 直播id
     * 
     * @return array
     */
    private static function giftInfo($live_id): array
    {
        // 获取礼物信息
        $live_gift = LiveGift::where('gift_type', '<>', 5)->whereIn('live_id', $live_id)->get([
            'action' => 'action',
            'id' => 'id',
            'gift_name' => 'gift_name',
            'gift_type' => 'gift_type',
            'gift_level' => 'gift_level',
            'num' => 'num',
            'price' => 'price',
            'uid' => 'uid',
            'uname' => 'uname',
        ]);
        $gift = [];
        foreach ($live_gift as $_live_gift) {
            if (!isset($gift[$_live_gift->uid])) {
                $gift[$_live_gift->uid] = [
                    'name' => $_live_gift->uname,
                    'price' => 0,
                    'num' => 0,
                    'gift_info' => []
                ];
            }
            $gift[$_live_gift->uid]['price'] += $_live_gift->price;
            $gift[$_live_gift->uid]['num'] += $_live_gift->num;
            if (!isset($gift[$_live_gift->uid]['gift_info'][$_live_gift->id])) {
                $gift[$_live_gift->uid]['gift_info'][$_live_gift->id] = [
                    'name' => $_live_gift->gift_name,
                    'num' => 0,
                    'price' => 0
                ];
            }
            $gift[$_live_gift->uid]['gift_info'][$_live_gift->id]['num'] += $_live_gift->num;
            $gift[$_live_gift->uid]['gift_info'][$_live_gift->id]['price'] += $_live_gift->price;
        }
        foreach ($gift as &$_gift) {
            $_gift['gift_info'] = arraySort($_gift['gift_info'], 'price', 'desc');
        }
        return arraySort($gift, 'price', 'desc');
    }

    /**
     * 获取直播场次的弹幕信息
     *
     * @param array $live_id 直播id
     * 
     * @return array
     */
    private static function danmuInfo($live_id): array
    {
        $live_danmu = SupportDb::select("select uname,count(*) as 'num' from bili_live_danmu where live_id in (:live_id) and uid != :uid group by uid", ['live_id' => implode(',', $live_id), 'uid' => '3493141330004769']);
        $danmu = [];
        foreach ($live_danmu as $_live_danmu) {
            $danmu[] = [
                'uname' => $_live_danmu->uname,
                'num' => $_live_danmu->num
            ];
        }
        return arraySort($danmu, 'num', 'desc');
    }
}
