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
        $local_path = $data['files_path'] . '/' . $data['files_name'];
        $upload_path = $data['files_path'] . '/' . $data['files_name'];
        // 执行 python 脚本
        $command = 'python3 /www/wwwroot/Bd/baidu.py ' . $local_path . ' ' . $upload_path;
        exec($command, $output, $return_val);
        if (count($output) == 1 && $output[0] == 'success') {
            // 上传成功
            $live_files->status = LiveFilesEnums\Status::UploadSuccessful->value;
            $live_files->save();
        } else {
            // 上传失败
            $live_files->status = LiveFilesEnums\Status::UploadFailed->value;
            $live_files->save();
        }
    }
}
