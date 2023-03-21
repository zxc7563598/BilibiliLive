<?php

use Carbon\Carbon;
use Webman\Http\Response;


/**
 * 日志信息存储
 *
 * @param string $paths 存储路径
 * @param string $filename 存储名称
 * @param string $contents 存储内容
 * 
 * @return void
 */
function sublog($paths, $filename, $contents): void
{
    $dir = base_path() . '/runtime/logs/' . Carbon::now()->timezone(config('app')['default_timezone'])->format('Y-m-d') . '/' . $paths . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . $filename . ".log";
    $content = Carbon::now()->timezone(config('app')['default_timezone'])->format('H:i:s') . "        " . json_encode($contents, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION) . "\r\n";
    file_put_contents($file, $content, FILE_APPEND);
}

/**
 * Api响应成功
 *
 * @param object $request Webman\Http\Request对象
 * @param array|object $data 返回数据
 * 
 * @return Response
 */
function success($request, $data = []): Response
{
    $request->res = [
        'code' => 0,
        'message' => config('code')[0],
        'data' => empty($data) ? (object)[] : $data
    ];
    return json($request->res, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
}

/**
 * Api响应失败
 *
 * @param object $request Webman\Http\Request对象
 * @param array $data 返回数据
 * 
 * @return Response
 */
function fail($request, $code = 500, $data = []): Response
{
    // 记录错误信息
    sublog('接口异常', str_replace('/', '-', $request->route->getPath()), '请求人ip -> ' . $request->getRealIp());
    $account_id = '未获取到';
    if (isset($request->account_id)) {
        $account_id = $request->account_id;
    }
    sublog('接口异常', str_replace('/', '-', $request->route->getPath()), '请求商户 -> ' . $account_id);
    sublog('接口异常', str_replace('/', '-', $request->route->getPath()), $request->all());
    $request->res = [
        'code' => $code,
        'message' => config('code')[$code],
        'data' => empty($data) ? (object)[] : $data
    ];
    sublog('接口异常', str_replace('/', '-', $request->route->getPath()), $request->res);
    sublog('接口异常', str_replace('/', '-', $request->route->getPath()), '====================');
    return json($request->res, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION);
}

/**
 * 根据秒数转换天/时/分/秒
 *
 * @param integer $time 秒数
 * 
 * @return string|bool
 */
function sec2Time($time): string|bool
{
    if (is_numeric($time)) {
        $value = array(
            "years" => 0, "days" => 0, "hours" => 0,
            "minutes" => 0, "seconds" => 0,
        );
        $t = '';
        if ($time >= 31556926) {
            $value["years"] = floor($time / 31556926);
            $time = ($time % 31556926);
            $t .= $value["years"] . "年";
        }
        if ($time >= 86400) {
            $value["days"] = floor($time / 86400);
            $time = ($time % 86400);
            $t .= $value["days"] . "天";
        }
        if ($time >= 3600) {
            $value["hours"] = floor($time / 3600);
            $time = ($time % 3600);
            $t .= $value["hours"] . "小时";
        }
        if ($time >= 60) {
            $value["minutes"] = floor($time / 60);
            $time = ($time % 60);
            $t .= $value["minutes"] . "分";
        }
        $value["seconds"] = floor($time);
        $t .= $value["seconds"] . "秒";
        return $t;
    } else {
        return (bool) false;
    }
}
