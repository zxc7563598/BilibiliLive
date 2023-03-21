<?php

namespace app\queue\redis;

use app\model\LiveFiles;
use Webman\RedisQueue\Consumer;
use resource\enums\LiveFilesEnums;

class BaiduNetbookUpload implements Consumer
{
    // 要消费的队列名
    public $queue = 'baidu-netbook-upload';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        sublog('队列任务', '文件上传队列', '上传路径：' . $data['files_path']);
        sublog('队列任务', '文件上传队列', '上传文件：' . $data['files_name']);
        // 文件获取
        $live_files = LiveFiles::where('files_id', $data['files_id'])->first();
        $live_files->status = LiveFilesEnums\Status::InUpload->value;
        $live_files->save();
        // 路径处理
        $python = public_path() . '/BaiduNetworkDisk.py';
        $local_path = $data['files_path'] . '/' . $data['files_name'];
        $upload_path = $data['files_path'] . '/' . $data['files_name'];
        // 执行 python 脚本
        $command = 'python3 ' . $python . ' ' . $local_path . ' ' . $upload_path . ' ' . $live_files->files_id . '  > /root/录播上传.log 2>&1 &';
        exec($command, $output, $return_val);
    }
}
