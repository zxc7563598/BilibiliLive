<?php

use Carbon\Carbon;
use Webman\Http\Response;


/**
 * base64图片存储
 *
 * @param string $path 文件路径
 * @param string $base64 图片Base64
 * @return void
 */
function ImageStorageBase64($path, $base64)
{
    ini_set('pcre.backtrack_limit', -1);
    if (!file_exists($path)) {
        mkdir($path, 0774, true);
    }
    // 匹配出图片的格式
    $result = [];
    if (preg_match('/^(data:\s*((image)|(application))\/(\S+);base64,)/', $base64, $result)) {
        $type = $result[5];
        $save_file = ((microtime(true) * 10000) . '.' . $type);
        if (file_put_contents($path . $save_file, base64_decode(str_replace($result[1], '', $base64)))) {
            return $save_file;
        }
    }
    return false;
}

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

/**
 * 多层数组排序
 *
 * @param array $array 数组
 * @param string $sortRule 排序字段
 * @param string $order 排序方式
 * @return array
 */
function arraySort($array, $sortRule = "", $order = "asc")
{
    if (is_array($sortRule)) {
        usort($array, function ($a, $b) use ($sortRule) {
            foreach ($sortRule as $sortKey => $order) {
                if ($a[$sortKey] == $b[$sortKey]) {
                    continue;
                }
                return (($order == 'desc') ? -1 : 1) * (($a[$sortKey] < $b[$sortKey]) ? -1 : 1);
            }
            return 0;
        });
    } else
        if (is_string($sortRule) && !empty($sortRule)) {
        usort($array, function ($a, $b) use ($sortRule, $order) {
            if ($a[$sortRule] == $b[$sortRule]) {
                return 0;
            }
            return (($order == 'desc') ? -1 : 1) * (($a[$sortRule] < $b[$sortRule]) ? -1 : 1);
        });
    } else {
        usort($array, function ($a, $b) use ($order) {
            if ($a == $b) {
                return 0;
            }
            return (($order == 'desc') ? -1 : 1) * (($a < $b) ? -1 : 1);
        });
    }
    return $array;
}

/**
 * 字符串替换
 *
 * @param string $search 搜索值
 * @param string $replace 替换值
 * @param string $subject 操作字符串
 * 
 * @return string
 */
function replaceFirst($search, $replace, $subject): string
{
    if ($search === '') {
        return $subject;
    }
    $position = strpos($subject, $search);

    if ($position !== false) {
        return substr_replace($subject, $replace, $position, strlen($search));
    }
    return $subject;
}
