from bypy import ByPy
import sys
import requests

args = sys.argv
bp = ByPy()
result = bp.upload(args[1], args[2])
# 返回上传数据
url = 'http://api.bilibililive.info/recorder/file-callback'
print('输出结果开始')
print(result)
print('输出结果结束')
if result == 0:
    requests.post(url, data={'files_id': args[3], 'success': 1})
else:
    requests.post(url, data={'files_id': args[3], 'success': 0})
