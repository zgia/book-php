<?php

namespace App\Service;

use App\Exception\BookException;

/**
 * Class BookService
 */
class BookService extends BaseService
{
    public function download(array $book)
    {
        $filename = '/tmp/book-' . \Neo\Str::randString(10) . '.txt';

        if (! $fp = fopen($filename, 'wb')) {
            throw new BookException("Cannot open file ({$filename})");
        }

        $chapterModel = static::neoModel('chapter');

        $chaps = $chapterModel->rows(
            [
                'chapter.bookid' => $book['bookid'],
                'chapter.deletedat' => 0,
            ],
            [
                'field' => 'txt,chapter.title AS chapterTitle,vol.title AS volumeTitle',
                'inner' => [
                    'content AS content ON chapter.id=content.chapterid',
                    'volume AS vol ON vol.id=chapter.volumeid',
                ],
                'orderby' => 'chapter.id',
            ]
        );

        $content = $book['title'] . PHP_EOL . PHP_EOL . $book['author'] . PHP_EOL . PHP_EOL . $book['summary'] . PHP_EOL . PHP_EOL . PHP_EOL;

        if (fwrite($fp, $content) === false) {
            throw new BookException("Cannot write to file ({$filename})");
        }

        $content = '';

        $volumeTitle = '';

        foreach ($chaps as $chap) {
            if ($volumeTitle != $chap['volumeTitle']) {
                $volumeTitle = $chap['volumeTitle'];

                if ($chap['volumeTitle'] != $book['title']) {
                    if (fwrite($fp, $chap['volumeTitle'] . PHP_EOL . PHP_EOL . PHP_EOL) === false) {
                        throw new BookException("Cannot write to file ({$filename})");
                    }
                }
            }

            if (fwrite($fp, $chap['chapterTitle'] . PHP_EOL . PHP_EOL . $chap['txt'] . PHP_EOL . PHP_EOL) === false) {
                throw new BookException("Cannot write to file ({$filename})");
            }
        }

        fclose($fp);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
        readfile($filename);

        unload();
    }

    /**
     * 图书列表
     *
     * @param string $title
     *
     * @return []
     */
    public function items(string $title)
    {
        $perpage = getOption('perpage');

        $conds = [
            'deletedat' => 0,
        ];

        // 搜索
        if ($title) {
            $conds['title LIKE'] = $title . '%';
        }

        $bookModel = static::neoModel('book');
        $total = $bookModel->total($conds);

        $books = $bookModel->rows(
            $conds,
            [
                'field' => 'id AS bookid,title,author,summary,source',
                'orderby' => 'id DESC',
                'limit' => [(CURRENT_PAGE - 1) * $perpage, $perpage],
            ],
            ['k' => '']
        );

        return ['total' => $total, 'items' => $books];
    }

    /**
     * 图书
     *
     * @param int $bookid
     *
     * @return []
     */
    public function getBook(int $bookid)
    {
        $book = static::neoModel('book')->getRow($bookid);

        if (empty($book) || $book['deletedat']) {
            throw new BookException('没有找到图书。', 404);
        }

        $book['bookid'] = $book['id'];

        unset($book['id'], $book['createdat'], $book['updatedat'], $book['deletedat']);

        return $book;
    }

    /**
     * 新增图书
     *
     * @param  array $book
     * @return int   图书ID
     */
    public function addBook(array $book)
    {
        $bookModel = static::neoModel('book');

        try {
            $book['createdat'] = TIMENOW;
            $bookid = $bookModel->insert($book);
        } catch (BookException $ex) {
            throw new BookException($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $bookid;
    }

    /**
     * 新增图书
     *
     * @param  array $book
     * @param  int   $bookid
     * @return int   图书ID
     */
    public function updateBook(array $book, int $bookid)
    {
        $bookModel = static::neoModel('book');

        try {
            $book['updatedat'] = TIMENOW;
            $bookModel->update($book, ['id' => $bookid]);
        } catch (BookException $ex) {
            throw new BookException($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $bookid;
    }

    /**
     * 删除图书
     *
     * @param int $bookid
     *
     * @return int
     */
    public function deleteBook(int $bookid)
    {
        $bookModel = static::neoModel('book');

        $bookModel->setDeletedFlag('deletedat');
        $bookModel->setDeletedVal(TIMENOW);

        return $bookModel->deleteItem($bookid);
    }

    /**
     * 图书目录列表
     *
     * @param array $book
     *
     * @return []
     */
    public function getChapters(array $book)
    {
        $volumes = static::neoModel('volume')->rows(
            ['bookid' => $book['bookid']],
            [
                'field' => 'id AS volumeid,title,summary',
                'orderby' => 'id',
            ],
            ['k' => '']
        );

        $chaps = static::neoModel('chapter')->rows(
            [
                'bookid' => $book['bookid'],
                'deletedat' => 0,
            ],
            [
                'field' => 'id,volumeid,title',
                'orderby' => 'id',
            ]
        );

        $data = [];

        foreach ($chaps as $chap) {
            $data[$chap['volumeid']][] = [
                'chapterid' => $chap['id'],
                'title' => $chap['title'],
            ];
        }

        if (! $volumes) {
            $volumes = [['volumeid' => 0, 'title' => $book['title'], 'summary'=>'']];
        }

        if (! $data) {
            foreach ($volumes as $vol) {
                $data[$vol['volumeid']] = [];
            }
        }

        return ['items' => ['chapters' => $data, 'volumes' => $volumes]];
    }

    /**
     * 图书章节内容
     *
     * @param array $book
     * @param int   $chapterid
     *
     * @return []
     */
    public function getChapter(array $book, int $chapterid)
    {
        if (! $chapterid) {
            return [
                'chapterid' => 0,
                'content' => '',
                'volumeid' => '',
                'chapterTitle' => '',
                'volumeTitle' => '',
                'bookTitle' => $book['title'],
            ];
        }

        $conds = [
            'chapter.id' => $chapterid,
            'chapter.deletedat' => 0,
        ];

        $chapterModel = static::neoModel('chapter');

        $chap = $chapterModel->row(
            $conds,
            [
                'field' => 'content.chapterid,txt AS content,chapter.volumeid,chapter.title AS chapterTitle,vol.title AS volumeTitle',
                'inner' => [
                    'content AS content ON chapter.id=content.chapterid',
                    'volume AS vol ON vol.id=chapter.volumeid',
                ],
            ]
        );

        $chap['bookTitle'] = $book['title'];

        return $chap;
    }

    /**
     * 图书章节内容，同时获取前一个与后一个的章节信息
     *
     * @param array $book
     * @param int   $chapterid
     *
     * @return []
     */
    public function getChapterWithBN(array $book, int $chapterid)
    {
        if (! $chapterid) {
            throw new BookException('没有指定章节ID', 404);
        }

        $conds = [
            'chapter.id' => $chapterid,
            'chapter.deletedat' => 0,
        ];

        $chapterModel = static::neoModel('chapter');

        $chap = $chapterModel->row(
            $conds,
            [
                'field' => 'content.chapterid,txt AS content,chapter.volumeid,chapter.title AS chapterTitle,vol.title AS volumeTitle',
                'inner' => [
                    'content AS content ON chapter.id=content.chapterid',
                    'volume AS vol ON vol.id=chapter.volumeid',
                ],
            ]
        );
        $chap['bookTitle'] = $book['title'];

        $next = $chapterModel->row(
            [
                'id > ' . $chapterid,
                'bookid' => $book['bookid'],
                'volumeid' => $chap['volumeid'],
                'deletedat' => 0,
            ],
            ['field' => 'id AS chapterid,title']
        );

        $before = $chapterModel->row(
            [
                'id < ' . $chapterid,
                'bookid' => $book['bookid'],
                'volumeid' => $chap['volumeid'],
                'deletedat' => 0,
            ],
            [
                'field' => 'id AS chapterid,title',
                'orderby' => 'id DESC',
            ]
        );

        return ['before' => $before, 'next' => $next, 'current' => $chap];
    }

    /*
     * 编辑章节
     *
     * @param array $data
     *
     * @return int
     */
    public function updateChapter(array $data)
    {
        static::neoModel('chapter')->update(
            [
                'title' => $data['title'],
                'volumeid' => $data['volumeid'],
                'updatedat' => TIMENOW,
            ],
            ['id' => $data['chapterid']]
        );

        static::neoModel('content')->update(
            ['txt' => $data['content']],
            ['chapterid' => $data['chapterid']]
        );

        return $data['chapterid'];
    }

    /*
     * 添加章节
     *
     * @param array $data
     *
     * @return int
     */
    public function addChapter(array $data)
    {
        $chapterModel = static::neoModel('chapter');

        $chapterid = $chapterModel->insert(
            [
                'title' => $data['title'],
                'bookid' => $data['bookid'],
                'volumeid' => $data['volumeid'],
                'createdat' => TIMENOW,
            ]
        );

        static::neoModel('content')->insert(
            [
                'chapterid' => $chapterid,
                'txt' => $data['content'],
            ]
        );

        return $chapterid;
    }

    /*
     * 删除章节
     *
     * @param int $id
     *
     * @return int
     */
    public function deleteChapter(int $id)
    {
        $chapterModel = static::neoModel('chapter');

        $chapterModel->setDeletedFlag('deletedat');
        $chapterModel->setDeletedVal(TIMENOW);

        return $chapterModel->deleteItem($id);
    }

    /*
     * 图书卷
     *
     * @param int $bookid
     *
     * @return []
     */
    public function getVolumes(int $bookid)
    {
        $vols = static::neoModel('volume')->rows(
            ['bookid' => $bookid],
            [
                'field' => 'id AS volumeid,title',
                'orderby' => 'id DESC',
            ],
            ['k' => '']
        );

        return ['items' => $vols];
    }

    /*
     * 编辑图书卷
     *
     * @param array $vol
     *
     * @return int
     */
    public function updateVolume(array $vol)
    {
        static::neoModel('volume')->update(
            ['title' => $vol['title'], 'updatedat' => TIMENOW],
            ['id' => $vol['volumeid']]
        );

        return $vol['volumeid'];
    }

    /*
     * 添加图书卷
     *
     * @param string $title
     * @param int $bookid
     *
     * @return int
     */
    public function addVolume(string $title, int $bookid)
    {
        return static::neoModel('volume')->insert(['title' => $title, 'bookid' => $bookid, 'createdat' => TIMENOW]);
    }

    /*
     * 删除图书卷
     *
     * @param int $id
     *
     * @return int
     */
    public function deleteVolume(int $id)
    {
        return static::neoModel('volume')->deleteItem($id, false);
    }
}
