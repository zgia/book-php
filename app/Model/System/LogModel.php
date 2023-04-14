<?php

namespace App\Model\System;

use Neo\Base\Model;
use Neo\NeoLog;

/**
 * 日志类
 */
class LogModel extends Model
{
    protected $table = 'log';

    /**
     * 管理日志
     *
     * @param array $data       数据
     * @param array $conditions 条件
     * @param bool  $replace    当前数据是插入还是替换
     *
     * @return int 日志ID
     */
    public function save(array $data, array $conditions = [], bool $replace = false)
    {
        if ($data['from'] && $data['to']) {
            $data['to'] = array_diff_assoc($data['to'], $data['from']);
        }

    NeoLog::info('actionlog', 'student', $this->neo->getUser());

        return $this->insert(
            [
                '`type`' => (string) ($data['type'] ?? $this->neo->routeInfo['func']),
                '`objectid`' => (int) $data['id'],
                '`script`' => $data['script'] ?? neo()->getRequest()->script(),
                '`action`' => (string) ($data['action'] ?? $this->neo->routeInfo['class']),
                '`ipaddress`' => neo()->getRequest()->getClientIp(),
                '`userid`' => (int) ($data['userid'] ?? $this->neo->getUser()['id']),
                '`fromcontent`' => json_encode($data['from'] ?? [], JSON_UNESCAPED_UNICODE),
                '`tocontent`' => json_encode($data['to'] ?? [], JSON_UNESCAPED_UNICODE),
                '`createdat`' => timenow(),
            ]
        );
    }

    /**
     * 获取日志详细信息
     *
     * @param int $logid 日志ID
     *
     * @return array
     */
    public function getLogDetail(int $logid)
    {
        $item = $this->getRow($logid);

        $this->parseContent($item['fromcontent']);
        $this->parseContent($item['tocontent']);

        return $item;
    }

    /**
     * 获取日志
     *
     * @param array $conditions 条件
     * @param int   $offset     数据偏移量
     * @param int   $limit      获取数据数量
     *
     * @return array
     */
    public function getLogs(array $conditions, int $offset = 0, int $limit = 20)
    {
        $items = $this->rows(
            $conditions,
            [
                'orderby' => 'id DESC',
                'limit' => [$offset, $limit],
            ]
        );

        foreach ($items as &$item) {
            $this->parseContent($item['fromcontent']);
            $this->parseContent($item['tocontent']);
        }

        return $items;
    }

    /**
     * 解析日志内容
     *
     * @param string $content
     */
    private function parseContent(string &$content = null)
    {
        if ($content) {
            $content = json_decode($content, true);
        }
    }
}
