<?php

namespace app\controller\lottery;

use app\model\Lottery;
use app\model\LotteryItem;
use support\Request;
use Webman\Http\Response;
use resource\enums\LotteryEnums;
use resource\enums\LotteryItemEnums;

class MainController
{

    /**
     * 获取抽奖信息
     *
     * @return Response
     */
    public function main(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/管理端', '列表查看', $param);
        // 获取数据
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
     * 获取抽奖详情
     * 
     * @param string lottery_id 抽奖id
     *
     * @return Response
     */
    public function details(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/管理端', '获取抽奖详情', $param);
        // 获取参数
        $lottery_id = isset($param['lottery_id']) ? $param['lottery_id'] : null;
        // 获取数据
        $lottery = null;
        if (!empty($lottery_id)) {
            $lottery = Lottery::where('lottery_id', $lottery_id)->first();
            if (empty($lottery)) {
                return fail($request, 800006);
            }
        }
        // 返回数据
        return success($request, [
            'lottery' => $lottery,
            'type' => LotteryEnums\Type::all(),
            'prize_type' => LotteryEnums\PrizeType::all()
        ]);
    }

    /**
     * 抽奖信息添加/变更
     *
     * @param string $lottery_id 抽奖id
     * @param string $lottery_name 抽奖名称
     * @param string $num 奖品数量
     * @param string $medal_name 限制牌子
     * @param string $medal_level 限制等级
     * @param string $type 抽奖类型（编号直接绑定，弹幕在拓展里指定内容，礼物为json）
     * @param string $expand 拓展信息
     * 
     * @return Response
     */
    public function setLottery(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/管理端', '抽奖信息添加or变更', $param);
        // 获取参数
        $lottery_id = !empty($param['lottery_id']) ? $param['lottery_id'] : null;
        $lottery_name = $param['lottery_name'];
        $num = $param['num'];
        $medal_name = !empty($param['medal_name']) ? $param['medal_name'] : null;
        $medal_level = !empty($param['medal_level']) ? $param['medal_level'] : null;
        $type = $param['type'];
        $expand = !empty($param['expand']) ? $param['expand'] : null;
        // 处理数据
        $lottery = new Lottery();
        if (!empty($lottery_id)) {
            $lottery = Lottery::where('lottery_id', $lottery_id)->first();
            if ($lottery->status == LotteryEnums\Status::Completed->value) {
                return fail($request, 800007);
            }
        }
        $lottery->lottery_name = $lottery_name;
        $lottery->num = $num;
        $lottery->medal_name = $medal_name;
        $lottery->medal_level = $medal_level;
        $lottery->type = $type;
        $lottery->expand = $expand;
        $lottery->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 管理端获取盒子
     *
     * @param string $lottery_id 抽奖id
     * @return Response
     */
    public function getBox(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/管理端', '管理端获取盒子', $param);
        // 获取参数
        $lottery_id = $param['lottery_id'];
        // 获取盒子
        $items = LotteryItem::where('lottery_id', $lottery_id)->orderBy('box_number', 'asc')->get([
            'box_number' => 'box_number',
            'content' => 'content',
            'status' => 'status',
            'uname' => 'uname'
        ]);
        // 返回数据
        return success($request, [
            'box' => $items
        ]);
    }

    /**
     * 配置盒子
     *
     * @param string $lottery_id 抽奖id
     * @param array $box 盒子信息
     * @return Response
     */
    public function setBox(Request $request): Response
    {
        $param = $request->data;
        sublog('抽奖接口/管理端', '管理端获取盒子', $param);
        // 获取参数
        $lottery_id = $param['lottery_id'];
        $box = $param['box'];
        // 获取配置信息
        $count = 0;
        $success = 0;
        foreach ($box as $_box) {
            $count += 1;
            $lottery_items = LotteryItem::where('lottery_id', $lottery_id)
                ->where('box_number', $_box['box_number'])
                ->first();
            if (!empty($lottery_items)) {
                $lottery_items->content = $_box['content'];
                $lottery_items->save();
                $success += 1;
            }
        }
        // 返回数据
        return success($request, [
            'count' => $count,
            'success' => $success
        ]);
    }
}
