<?php

namespace app\controller\lottery;

use app\model\Lottery;
use app\model\LotteryItem;
use support\Request;
use Webman\Http\Response;
use resource\enums\LotteryEnums;
use resource\enums\LotteryItemEnums;

class UserController
{

    /**
     * 获取抽奖信息
     *
     * @return Response
     */
    public function main(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/用户端', '列表查看', $param);
        // 获取投稿
        $lottery = Lottery::orderBy('created_at', 'asc')->get([
            'lottery_id' => 'lottery_id',
            'lottery_name' => 'lottery_name',
            'num' => 'num',
            'medal_name' => 'medal_name',
            'medal_level' => 'medal_level',
            'type' => 'type',
            'status' => 'status',
            'created_at' => 'created_at'
        ]);
        foreach ($lottery as &$_lottery) {
            $_lottery->type = LotteryEnums\Type::from($_lottery->type)->label();
            $_lottery->status = LotteryEnums\Status::from($_lottery->status)->label();
            $_lottery->create_time = $_lottery->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
            unset($_lottery->created_at);
        }
        // 返回数据
        return success($request, [
            'lottery' => $lottery
        ]);
    }

    /**
     * 获取抽奖信息
     *
     * @param string $lottery_id 奖品id
     * 
     * @return Response
     */
    public function details(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/用户端', '获取抽奖信息', $param);
        // 获取参数
        $lottery_id = $param['lottery_id'];
        // 获取数据
        $lottery = Lottery::where('lottery_id', $lottery_id)->first();
        $lottery_item = LotteryItem::where('lottery_id', $lottery_id)->orderBy('box_number', 'asc')->get([
            'item_id' => 'item_id',
            'box_number' => 'box_number',
            'uid' => 'uid',
            'uname' => 'uname',
            'content' => 'content',
            'status' => 'status'
        ]);
        // 获取已经开奖的用户名单
        $prize = LotteryItem::where('lottery_id', $lottery_id)->where('status', LotteryItemEnums\Status::Lottery->value)->orderBy('updated_at', 'asc')->get();
        // 返回数据
        return success($request, [
            'lottery' => $lottery,
            'items' => $lottery_item,
            'prize' => $prize
        ]);
    }

    /**
     * 用户盒子信息绑定
     *
     * @param string $item_id 盒子id
     * @param string $uid 用户uid
     * @param string $uname 用户uname
     * 
     * @return Response
     */
    public function bindUid(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/用户端', '绑定盒子', $param);
        // 获取参数
        $item_id = $param['item_id'];
        $uid = $param['uid'];
        $uname = $param['uname'];
        // 处理数据
        $lottery_item = LotteryItem::where('item_id', $item_id)->first();
        if (empty($lottery_item)) {
            return fail($request, 800008);
        }
        if ($lottery_item->status != LotteryItemEnums\Status::Unbound->value) {
            return fail($request, 800009);
        }
        // 绑定用户
        $lottery_item->uid = $uid;
        $lottery_item->uname = $uname;
        $lottery_item->status = LotteryItemEnums\Status::Bound->value;
        $lottery_item->save();
        // 返回成功
        return success($request, $lottery_item);
    }

    /**
     * 随机打乱活动盲盒
     *
     * @param string $lottery_id 奖品id
     * 
     * @return Response
     */
    public function randomBox(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/用户端', '随机打乱活动盲盒', $param);
        // 获取参数
        $lottery_id = $param['lottery_id'];
        // 获取数据
        $lottery_item = LotteryItem::where('lottery_id', $lottery_id)->where('status', LotteryItemEnums\Status::Unbound->value)->get();
        // 处理数据
        $prize = [];
        foreach ($lottery_item as $_item) {
            $prize[] = $_item->content;
        }
        $old = $prize;
        if (count($prize)) {
            shuffle($prize);
            $new = $prize;
            $i = 0;
            foreach ($lottery_item as &$_item) {
                $_item->content = $prize[$i];
                $_item->save();
                $i++;
            }
        }
        // 返回成功
        return success($request, [
            $old, $new
        ]);
    }

    /**
     * 获取中奖信息
     *
     * @param string $item_id 盒子id
     * 
     * @return Response
     */
    public function getPrize($request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/用户端', '获取中奖信息', $param);
        // 获取参数
        $item_id = $param['item_id'];
        // 获取数据
        $lottery_items = LotteryItem::where('item_id', $item_id)->first();
        if (empty($lottery_items)) {
            return fail($request, 800008);
        }
        $lottery_items->status = LotteryItemEnums\Status::Lottery->value;
        $lottery_items->save();
        // 返回成功
        return success($request, $lottery_items);
    }
}
