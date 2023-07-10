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

use app\model\LiveBlack;
use app\model\LiveDanmu;
use app\model\LiveDanmuLog;
use app\model\LiveGift;
use app\model\LiveGiftLog;
use support\Redis;
use app\model\LiveUser;
use Carbon\Carbon;
use SplFixedArray;
use Workerman\Connection\AsyncTcpConnection;
use Suqingan\Network;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use yzh52521\mailer\Mailer;
use Workerman\Worker;
use HelloNico\Brotli\Brotli;
use resource\enums\LiveBlackEnums;

/**
 * Class FileMonitor
 * @package process
 */
class BiliWebsocket
{
    /**
     * FileMonitor constructor.
     * @param $monitorDir
     * @param $monitorExtensions
     * @param array $options
     */
    public function __construct()
    {
        self::init(30118851, 3494365156608185);
    }

    public static function init($room_id, $uid)
    {
        sublog('哔哩哔哩', '主进程', 'init 函数调用，开始获取信息流认证秘钥');
        $getDanmuInfo = self::getDanmuInfo($room_id);
        if (!$getDanmuInfo['success']) {
            sublog('哔哩哔哩', '主进程', 'getDanmuInfo 响应失败');
            sublog('哔哩哔哩', '主进程', '60秒后重新进行 init，请在日志中检查 getDanmuInfo 的信息');
            sublog('哔哩哔哩', '主进程', '--------------------');
            sleep(15);
            self::init($room_id, $uid);
        }
        // 进行 websocket 连接
        sublog('哔哩哔哩', '主进程', '进行 websocket 连接');
        sublog('哔哩哔哩', '主进程', 'tcp://' . $getDanmuInfo['host_list']['host'] . ':' . $getDanmuInfo['host_list']['port']);
        $websocket = new AsyncTcpConnection('tcp://' . $getDanmuInfo['host_list']['host'] . ':' . $getDanmuInfo['host_list']['port']);
        // $websocket->websocketType = Ws::BINARY_TYPE_ARRAYBUFFER;
        $websocket->onConnect = function ($websocket) use ($getDanmuInfo, $room_id) {
            sublog('哔哩哔哩', '主进程', '连接成功，发送认证包');
            $payload = json_encode([
                'roomid' => $room_id,
                'protover' => 3
            ]);
            sublog('哔哩哔哩', '主进程', json_decode($payload, true));
            $websocket->send(self::pack(7, $payload), true);
            sublog('哔哩哔哩', '主进程', '认证包发送完成');
            Timer::add(30, function () use ($websocket) {
                $heartbeat = $websocket->send(self::pack(2), true);
                if (!$heartbeat) {
                    $websocket->close();
                }
                sublog('哔哩哔哩', '主进程', '心跳发送成功');
            });
            sublog('哔哩哔哩', '主进程', '心跳创建完成');
        };
        $websocket->onMessage = function ($websocket, $data) use ($room_id, $uid) {
            sublog('哔哩哔哩', '主进程', '收到消息');
            $packet = self::unpack($data);
            sublog('哔哩哔哩', '主进程', $packet);
            if (gettype($packet['payload']) == 'array') {
                foreach ($packet['payload'] as $payload) {
                    if (isset($payload['cmd'])) {
                        sublog('哔哩哔哩/事件信息', $payload['cmd'], $payload);
                        // http://8.217.79.31:23333/block?uid=29233734&time=24 // 拉黑
                        switch ($payload['cmd']) {
                            case 'SEND_GIFT': // 礼物记录
                                $live_gift_log = new LiveGiftLog();
                                $live_gift_log->action = $payload['data']['action'];
                                $live_gift_log->face = $payload['data']['face'];
                                $live_gift_log->uid = $payload['data']['uid'];
                                $live_gift_log->uname = $payload['data']['uname'];
                                $live_gift_log->wealth_level = $payload['data']['wealth_level'];
                                $live_gift_log->gift_id = $payload['data']['giftId'];
                                $live_gift_log->gift_name = $payload['data']['giftName'];
                                $live_gift_log->num = $payload['data']['num'];
                                $live_gift_log->price = (!empty($payload['data']['price']) ? round($payload['data']['price'] / 10) : 0) * $payload['data']['num'];
                                $live_gift_log->gift_type = $payload['data']['giftType'];
                                $live_gift_log->gold = $payload['data']['gold'];
                                $live_gift_log->receive_uid = isset($payload['data']['receive_user_info']['uid']) ? $payload['data']['receive_user_info']['uid'] : null;
                                $live_gift_log->receive_uname = isset($payload['data']['receive_user_info']['uname']) ? $payload['data']['receive_user_info']['uname'] : null;
                                $live_gift_log->medal_level = isset($payload['data']['medal_info']['medal_level']) ? $payload['data']['medal_info']['medal_level'] : null;
                                $live_gift_log->medal_name = isset($payload['data']['medal_info']['medal_name']) ? $payload['data']['medal_info']['medal_name'] : null;
                                $live_gift_log->target_id = isset($payload['data']['medal_info']['target_id']) ? $payload['data']['medal_info']['target_id'] : null;
                                $live_gift_log->guard_level = $payload['data']['guard_level'];
                                $live_gift_log->timestamp = $payload['data']['timestamp'];
                                $live_gift_log->save();
                                self::delBlackCheck($room_id, $live_gift_log->uid, ($live_gift_log->price * 10));
                                break;
                            case 'DANMU_MSG': // 弹幕记录
                                $live_danmu_log = new LiveDanmuLog();
                                $live_danmu_log->uid = isset($payload['info'][2][0]) ? $payload['info'][2][0] : null;
                                $live_danmu_log->uname = isset($payload['info'][2][1]) ? $payload['info'][2][1] : null;
                                $live_danmu_log->uguard = isset($payload['info'][3][10]) ? $payload['info'][3][10] : null;
                                $live_danmu_log->ulevel = isset($payload['info'][4][0]) ? $payload['info'][4][0] : null;
                                $live_danmu_log->ulevel_ranking = isset($payload['info'][4][3]) ? $payload['info'][4][3] : null;
                                $live_danmu_log->medal_level = isset($payload['info'][3][0]) ? $payload['info'][3][0] : null;
                                $live_danmu_log->medal_name = isset($payload['info'][3][1]) ? $payload['info'][3][1] : null;
                                $live_danmu_log->medal_anchor_name = isset($payload['info'][3][2]) ? $payload['info'][3][2] : null;
                                $live_danmu_log->medal_room_id = isset($payload['info'][3][3]) ? $payload['info'][3][3] : null;
                                $live_danmu_log->msg = isset($payload['info'][1]) ? $payload['info'][1] : null;
                                $live_danmu_log->timestamp = isset($payload['info'][0][4]) ? round(($payload['info'][0][4] / 1000)) : null;
                                $live_danmu_log->save();
                                // 弹幕后续处理
                                if ($live_danmu_log->uid != '3493141330004769') {
                                    $addBlackCheck = self::addBlackCheck($room_id, $live_danmu_log->uid, $live_danmu_log->uname, $live_danmu_log->msg);
                                    // 如果没有命中黑名单，则去判断自动回复
                                    if (!$addBlackCheck) {
                                        self::autoReply($room_id, $uid, $live_danmu_log->uname, $live_danmu_log->msg);
                                    }
                                }
                                break;
                            case 'PK_BATTLE_PRE_NEW': // PK即将开始
                                sublog('哔哩哔哩', 'pk开始', $payload);
                                $initPk = self::initPk($payload['data']['uid'], $payload['data']['room_id']);
                                $push_str = [];
                                $push_str[] = '即将于' . $payload['data']['uname'] . '进行pk';
                                foreach ($initPk as $_initPk) {
                                    $push_str[] = $_initPk;
                                }
                                sublog('哔哩哔哩', 'pk开始', $push_str);
                                foreach ($push_str as $push) {
                                    self::sendMsg($room_id, $push);
                                }
                                break;
                            case 'ANCHOR_LOT_START': // 天选开始
                                self::sendMsg($room_id, $payload['data']['danmu']);
                                break;
                            case 'ANCHOR_LOT_AWARD': // 天选结束-获取完整中奖信息
                                $message = [];
                                $message[] = '恭喜' . $payload['data']['award_users']['uname'] . '成为天选之人';
                                $message[] = '获得' . $payload['data']['award_price_text'] . '的' . $payload['data']['award_name'] . '~';
                                if ($payload['data']['award_users']['uid'] == '3493141330004769') {
                                    $reply = [];
                                    $reply[] = '是谁运气那么好呢？啊原来是我啊（痴汉笑';
                                    $reply[] = '哎嘿，被我抢到了，气不气，气不气？';
                                    $message[] = $reply[array_rand($reply)];
                                } else {
                                    $message[] = '是准备送给我们主包吗？是吗？是吗？（期待';
                                }
                                break;
                                // 发送信息
                                foreach ($message as $_message) {
                                    self::sendMsg($room_id, $_message);
                                }
                            case 'POPULARITY_RED_POCKET_START': // 红包开始
                                self::sendMsg($room_id, $payload['data']['danmu']);
                                break;
                            case 'POPULARITY_RED_POCKET_WINNER_LIST': // 红包结束-公开中奖人
                                foreach ($payload['data']['winner_info'] as $winner_info) {
                                    if ($winner_info[0] == '3493141330004769') {
                                        $reply = [];
                                        $reply[] = '好耶！我抢到了' . $payload['awards'][$winner_info[3]];
                                        $reply[] = $payload['awards'][$winner_info[3]] . '被我抢到了！开心！（搓手手';
                                        $message[] = $reply[array_rand($reply)];
                                        self::sendMsg($room_id, $message);
                                    }
                                }
                                break;
                        }
                    } else {
                        // echo '无cmd数据：' . json_encode($payload) . "\r\n";
                    }
                }
            } else {
                // echo '非数组类型：' . json_encode($packet) . "\r\n";
            }
        };
        $websocket->onClose = function (TcpConnection $websocket) use ($room_id, $uid) {
            sublog('哔哩哔哩', '主进程', '窗口关闭');
            sublog('哔哩哔哩', '主进程', '15秒后重新进行 init');
            sublog('哔哩哔哩', '主进程', '--------------------');
            sleep(15);
            self::init($room_id, $uid);
        };
        // 执行异步连接
        $websocket->connect();
    }

    /**
     * 获取信息流认证秘钥
     *
     * @param [type] $room_id
     * @return void
     */
    private static function getDanmuInfo($room_id)
    {
        $url = "https://api.live.bilibili.com/xlive/web-room/v1/index/getDanmuInfo?id=" . $room_id;
        $token = null;
        $host_list = null;
        $success = false;
        $getDanmuInfo = Network\Curl::Get($url);
        if ($getDanmuInfo['code'] == 200) {
            $data = json_decode($getDanmuInfo['data'], true);
            switch ($data['code']) {
                case '0':
                    sublog('哔哩哔哩', '获取信息流认证秘钥异常', $data);
                    $token = $data['data']['token'];
                    $host_list = $data['data']['host_list'][0];
                    $success = true;
                    break;
                default:
                    sublog('哔哩哔哩', '获取信息流认证秘钥异常', '接口code异常');
                    sublog('哔哩哔哩', '获取信息流认证秘钥异常', $data);
                    break;
            }
        } else {
            sublog('哔哩哔哩', '获取信息流认证秘钥异常', 'http 返回非 200');
            sublog('哔哩哔哩', '获取信息流认证秘钥异常', $getDanmuInfo);
        }
        return [
            'success' => $success,
            'token' => $token,
            'host_list' => $host_list
        ];
    }

    /**
     * 压缩数据
     *
     * @param integer $opcode 操作类型
     * @param string $payload 待处理数据
     * @return void
     */
    public static function pack($opcode, $payload = '')
    {
        $packetLen = 16; // 头部长度
        $protocol_version = 3; // 协议版本
        $magic_number = 1;
        if (!empty($payload)) {
            $packetLen += strlen($payload);
        }
        return pack('NnnNN', $packetLen, 16, $protocol_version, $opcode, $magic_number) . $payload;
    }

    /**
     * 解压数据
     *
     * @param string $data 待处理数据
     * @return void
     */
    private static function unpack($data)
    {
        if (empty($data)) {
            return [];
        }
        $header = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number', $data);
        sublog('哔哩哔哩', '数据解压', $header);
        switch ($header['opcode']) {
            case 3: // 心跳包回复（人气值）
                $data = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number/a*payload', $data);
                $payload = [];
                $payload[0] = unpack('N', $data['payload']);
                $data['payload'] = $payload;
                break;
            case 5: // 普通包（命令）
                switch ($header['protocol_version']) {
                    case 2: // gzip压缩
                        $data = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number/a*payload', $data);
                        $de_data = gzuncompress($data['payload']);
                        $data = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number/a*payload', $de_data);
                        break;
                    default: // 普通包（brotli压缩）
                        $data = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number/a*payload', $data);
                        if ($data['payload'][0] != '{') {
                            $de_data = brotli_uncompress($data['payload']);
                            $data = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number/a*payload', $de_data);
                        }
                        break;
                }
                $string = [];
                $num = 0;
                $pointer = 0;
                $data_count = strlen($data['payload']);
                sublog('哔哩哔哩', '数据查看', '=============');
                do {
                    sublog('哔哩哔哩', '数据查看', $data);
                    if ($pointer != 0) {
                        $pointer += 90;
                    }
                    $pointer += ($data['packet_len'] - $data['header_len']);
                    $this_pointer = $data['packet_len'] - $data['header_len'];
                    $string[$num] = json_decode(substr($data['payload'], 0, $this_pointer), true);
                    sublog('哔哩哔哩', '数据查看', $string[$num]);
                    sublog('哔哩哔哩', '数据查看', '----------');
                    $num++;
                    if ($pointer < $data_count) {
                        $data_str = substr($data['payload'], $this_pointer);
                        $data['packet_len'] = unpack('N', substr($data_str, 0, 4))[1];
                        $data['header_len'] = unpack('n', substr($data_str, 4, 2))[1];
                        $data['protocol_version'] = unpack('n', substr($data_str, 6, 2))[1];
                        $data['opcode'] = unpack('N', substr($data_str, 8, 4))[1];
                        $data['magic_number'] = unpack('N', substr($data_str, 12, 4))[1];
                        $data['payload'] = unpack('a*', substr($data_str, 16))[1];
                    }
                } while ($pointer < $data_count);
                $data['payload'] = $string;
                // echo count($string) . "\r\n";
                break;
            default:
                $data = unpack('Npacket_len/nheader_len/nprotocol_version/Nopcode/Nmagic_number/a*payload', $data);
                $data['payload'] = !empty($data['payload']) ? json_decode($data['payload'], true) : [];
                break;
        }
        sublog('哔哩哔哩', '数据解压', $data);
        sublog('哔哩哔哩', '数据解压', '=============');
        return $data;
    }

    /**
     * PK时对面主播高能榜获取
     *
     * @param integer $uid 用户id
     * @param integer $room_id 房间号
     * @return void
     */
    private static function getOnlineGoldRank($uid, $room_id)
    {
        $url = "https://api.live.bilibili.com/xlive/general-interface/v1/rank/getOnlineGoldRank?ruid=" . $uid . "&roomId=" . $room_id . "&page=1&pageSize=5000";
        $getOnlineGoldRank = Network\Curl::Get($url);
        if ($getOnlineGoldRank['code'] != 200) {
            Mailer::setFrom(['992182040@qq.com' => "直播间出现问题"])
                ->setTo('junjie.he.925@gmail.com')
                ->setSubject('高能榜获取出现问题')
                ->setTextBody(json_encode($getOnlineGoldRank))
                ->send();
            return false;
        }
        $data = json_decode($getOnlineGoldRank['data'], true);
        if ($data['code'] != 0) {
            Mailer::setFrom(['992182040@qq.com' => "直播间出现问题"])
                ->setTo('junjie.he.925@gmail.com')
                ->setSubject('高能榜获取出现问题')
                ->setTextBody(json_encode($data))
                ->send();
            return false;
        }
        return $data['data'];
    }

    /**
     * 自动回复处理
     *
     * @param integer $room_id 房间号
     * @param integer $room_uid 主播uid
     * @param string $uname 用户名（用来替换参数）
     * @param string $msg 弹幕信息
     * 
     * @return bool
     */
    private static function autoReply($room_id, $room_uid, $uname, $msg)
    {
        // 如果有人叫我
        if (strpos($msg, '胖胖') !== false) {
            $message = [
                '你们是在聊那个帅气可爱的胖胖吗，他不在哦',
                '胖胖不在，你们是不是在说他坏话！偷偷告诉我，我不跟他说',
                '你们别叫胖胖啦，我也不知道他去哪里了'
            ];
            $hour = Carbon::now()->timezone(config('app')['default_timezone'])->format('H');
            if ($hour > 22 || $hour < 8) {
                array_push(
                    $message,
                    '胖胖已经睡了，你们不准说他坏话！',
                    '太晚啦，胖胖已经睡啦！',
                    '这么晚的时间，我不准你们打扰胖胖睡觉'
                );
            }
            $getOnlineGoldRank = self::getOnlineGoldRank($room_uid, $room_id);
            $exist = false;
            if ($getOnlineGoldRank) {
                foreach ($getOnlineGoldRank['OnlineRankItem'] as $OnlineRankItem) {
                    if ($OnlineRankItem['uid'] == '4325051') {
                        $exist = true;
                    }
                }
                if (!$exist) {
                    self::sendMsg($room_id, $message[array_rand($message)]);
                }
            }
        }
        // 弹幕规则
        $role = [
            ['words' => ['机器人'], 'message' => [
                $uname . '！你是在叫我吗？',
                '我听到了机器人？我不是，我是温以泠的狗',
                '你骂谁机器人！你才是机器人！'
            ]]
        ];
        foreach ($role as $_role) {
            foreach ($_role['words'] as $words) {
                if (strpos($msg, $words) !== false) {
                    self::sendMsg($room_id, $_role['message'][array_rand($_role['message'])]);
                }
            }
        }
    }

    /**
     * pk信息获取
     *
     * @param integer $uid 对面主播uid
     * @param integer $room_id 对面主播房间号
     * @return array
     */
    private static function initPk($uid, $room_id): array
    {
        $getOnlineGoldRank = self::getOnlineGoldRank($uid, $room_id);
        if ($getOnlineGoldRank) {
            $total = 0;
            for ($i = 0; $i < 3; $i++) {
                if (isset($getOnlineGoldRank['OnlineRankItem'][$i])) {
                    $total += $getOnlineGoldRank['OnlineRankItem'][$i]['score'];
                }
            }
            $message = [];
            $message[] = '对面高能榜' . $getOnlineGoldRank['onlineNum'] . '人';
            $message[] = '榜一大哥贡献了' . $getOnlineGoldRank['OnlineRankItem'][0]['score'] . '电池';
            $message[] = '前三总共贡献了' . $total . '电池';
        }
        return $message;
    }

    /**
     * 禁言信息检查
     *
     * @param integer $room_id 房间号
     * @param integer $uid 用户id
     * @param string $uname 用户名
     * @param string $msg 弹幕信息
     * 
     * @return bool
     */
    private static function addBlackCheck($room_id, $uid, $uname, $msg): bool
    {
        // 验证禁言信息
        $init = 0;
        $blacks = LiveBlack::where('uid', $uid)->orderBy('created_at', 'desc')->first();
        if (!empty($blacks)) {
            // 因为解除禁言不会通知，所以如果用户发言时，黑名单拥有信息，则证明已经被他人解除禁言
            $init = ($blacks->price / 100);
            if ($blacks->status == LiveBlackEnums\Status::Normal->value) {
                $blacks->status = LiveBlackEnums\Status::Disable->value;
                $blacks->save();
            }
        }
        // 弹幕规则
        $role = [
            ['words' => ['妈妈', 'mama', 'MaMa', 'mama', 'mother', 'Mother'], 'hour' => 1, 'amount' => 10, 'message' => [
                '%NAME%！不准叫妈妈！罚款%AMOUNT%电池！',
                '恭喜%NAME%因触发关键词进入小黑屋，罚款%AMOUNT%电池！',
                '不要叫妈妈好吗？%NAME%，本次罚款%AMOUNT%电池，希望你可以自己出来',
                '别骗我！我听到什么妈妈了！罚款！罚款了！%AMOUNT%电池！%NAME%！'
            ]],
            ['words' => ['骂我'], 'hour' => 1, 'amount' => 5, 'message' => [
                '%NAME%！想被主包惩罚吗～本次罚款%AMOUNT%电池！',
                '恭喜%NAME%得到主包的禁言奖励，%AMOUNT%电池即可离开！',
                '%NAME%，叫你说怪话，打劫！%AMOUNT%电池',
                '%NAME%不要说怪话，上舰私信才能得到辱骂，在这里只会被罚款！不说了，%AMOUNT%电池'
            ]],
            ['words' => ['汪汪'], 'hour' => 1, 'amount' => 5, 'message' => [
                '%NAME%！不准挑战我的地位！本次罚款%AMOUNT%电池！',
                '恭喜%NAME%得到机器人的嫉妒，机器人向你勒索%AMOUNT%电池',
                '你们都不准叫！我才是温以泠的狗呜呜呜',
            ]]
        ];
        // 匹配规则
        $hit = false;
        $hour = 0;
        $message = '';
        $key_words = '';
        $amount = 0;
        foreach ($role as $_role) {
            foreach ($_role['words'] as $words) {
                if (strpos($msg, $words) !== false) {
                    $hit = true;
                    $key_words = $words;
                    $hour = $_role['hour'];
                    $amount = $init + $_role['amount'];
                    $message = $_role['message'][array_rand($_role['message'])];
                    $message = str_replace('%NAME%', $uname, $message);
                    $message = str_replace('%AMOUNT%', $amount, $message);
                    break;
                }
            }
        }
        // 数据处理
        if ($hit) {
            $addBlack = self::addBlack($uid, $hour);
            if ($addBlack) {
                // 黑名单添加成功，进行数据记录并发送弹幕
                $live_black = new LiveBlack();
                $live_black->uid = $uid;
                $live_black->uname = $uname;
                $live_black->msg = $msg;
                $live_black->key_words = $key_words;
                $live_black->price = $amount * 100;
                $live_black->use_price = 0;
                $live_black->end = Carbon::now()->addHours($hour)->timezone(config('app')['default_timezone'])->timestamp;
                $live_black->status = LiveBlackEnums\Status::Normal->value;
                $live_black->save();
                // 发送弹幕
                self::sendMsg($room_id, $message);
            }
        }
        // 返回数据
        return $hit;
    }

    /**
     * 解除禁言检查
     *
     * @param integer $room_id 房间号
     * @param integer $uid 用户id
     * @param integer $price 礼物金额，单位电池*100
     * @return void
     */
    private static function delBlackCheck($room_id, $uid, $price)
    {
        $blacks = LiveBlack::where('uid', $uid)->where('status', LiveBlackEnums\Status::Normal->value)->first();
        if (!empty($blacks)) {
            $blacks->use_price += $price;
            if ($blacks->use_price >= $blacks->price) {
                $blacks->status = LiveBlackEnums\Status::Disable->value;
                // 解除禁言
                self::delBlack($uid);
            }
            $blacks->save();
        }
    }

    /**
     * 与弹幕姬联动发送弹幕
     *
     * @param integer $room_id 房间号
     * @param string $msg 弹幕信息
     * 
     * @return bool
     */
    private static function sendMsg($room_id, $msg): bool
    {
        sublog('哔哩哔哩', '发送信息到弹幕姬', $room_id);
        sublog('哔哩哔哩', '发送信息到弹幕姬', $msg);
        $sendMsg = Network\Curl::Post('http://127.0.0.1:23333/sendMsg', [
            'room_id' => $room_id,
            'msg' => $msg
        ], 'from');
        if ($sendMsg['code'] == 200) {
            $data = json_decode($sendMsg['data'], true);
            sublog('哔哩哔哩', '发送信息到弹幕姬', '处理成功');
            sublog('哔哩哔哩', '发送信息到弹幕姬', $data);
            if (isset($data['result']) && $data['result'] == 0) {
                return true;
            }
        } else {
            sublog('哔哩哔哩', '发送信息到弹幕姬', '处理失败');
            sublog('哔哩哔哩', '发送信息到弹幕姬', $sendMsg);
        }
        Mailer::setFrom(['992182040@qq.com' => "直播间出现问题"])
            ->setTo('junjie.he.925@gmail.com')
            ->setSubject('发送弹幕出现问题')
            ->setTextBody(json_encode($sendMsg))
            ->send();
        return false;
    }

    /**
     * 与弹幕姬联动禁言用户
     *
     * @param integer $uid 用户id
     * @param integer $time 禁言小时，最小1，最大720
     * 
     * @return bool
     */
    private static function addBlack($uid, $time): bool
    {
        sublog('哔哩哔哩', '禁言用户', $uid);
        sublog('哔哩哔哩', '禁言用户', $time);
        $success = false;
        $addBlack = Network\Curl::Get('http://127.0.0.1:23333/block?uid=' . $uid . '&time=' . $time);
        if ($addBlack['code'] == 200) {
            $data = json_decode($addBlack['data'], true);
            sublog('哔哩哔哩', '禁言用户', '处理成功');
            sublog('哔哩哔哩', '禁言用户', $data);
            if (isset($data['result']) && $data['result'] == 0) {
                $success = true;
            }
        } else {
            sublog('哔哩哔哩', '禁言用户', '处理失败');
            sublog('哔哩哔哩', '禁言用户', $addBlack);
        }
        Mailer::setFrom(['992182040@qq.com' => "直播间出现问题"])
            ->setTo('junjie.he.925@gmail.com')
            ->setSubject('禁言用户出现问题')
            ->setTextBody(json_encode($addBlack))
            ->send();
        return $success;
    }

    /**
     * 与弹幕姬联动解除禁言
     *
     * @param integer $uid 用户id
     * @return void
     */
    private static function delBlack($uid)
    {
        sublog('哔哩哔哩', '解除禁言', $uid);
        $success = false;
        $addBlack = Network\Curl::Get('http://127.0.0.1:23333/delBlock?uid=' . $uid);
        if ($addBlack['code'] == 200) {
            $data = json_decode($addBlack['data'], true);
            sublog('哔哩哔哩', '解除禁言', '处理成功');
            sublog('哔哩哔哩', '解除禁言', $data);
            if (isset($addBlack['data']['result']) && $addBlack['data']['result'] == 0) {
                $success = true;
            }
        } else {
            sublog('哔哩哔哩', '解除禁言', '处理失败');
            sublog('哔哩哔哩', '解除禁言', $addBlack);
        }
        Mailer::setFrom(['992182040@qq.com' => "直播间出现问题"])
            ->setTo('junjie.he.925@gmail.com')
            ->setSubject('取消禁言出现问题')
            ->setTextBody(json_encode($addBlack))
            ->send();
        return $success;
    }
}
