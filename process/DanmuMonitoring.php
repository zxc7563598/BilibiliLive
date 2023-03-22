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

namespace process;

use app\model\LiveDanmu;
use app\model\LiveGift;
use support\Redis;
use app\model\LiveUser;
use Workerman\Connection\AsyncTcpConnection;

/**
 * Class FileMonitor
 * @package process
 */
class DanmuMonitoring
{
    /**
     * FileMonitor constructor.
     * @param $monitorDir
     * @param $monitorExtensions
     * @param array $options
     */
    public function __construct()
    {
        self::init();
    }

    public static function init()
    {
        $con = new AsyncTcpConnection('ws://47.243.105.184:23333/danmu/sub');
        $con->onConnect = function (AsyncTcpConnection $con) {
            $con->send('hello');
            sublog('长链接测试', '弹幕机链接', '连接成功了');
        };
        $con->onMessage = function (AsyncTcpConnection $con, $data) {
            $data = json_decode($data, true);
            $message = [];
            if ($data) {
                switch ($data['cmd']) {
                    case 'danmu': // 弹幕
                        $message = [
                            '类型' => "弹幕",
                            "房管" => $data['result']['manager'],
                            "等级" => $data['result']['medal_level'],
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
                        $live_danmu->level = $data['result']['medal_level'];
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
                    default:
                        $message = [
                            "类型" => "未定义",
                            "内容" => $data
                        ];
                        break;
                }
                sublog('长链接测试', '弹幕机链接', $message);
            }
        };
        $con->onClose = function (AsyncTcpConnection $con, $data) {
            sublog('长链接测试', '弹幕机链接', '链接断开，重新连接');
            self::init();
        };
        $con->connect();
    }
}
