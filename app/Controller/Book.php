<?php

namespace App\Controller;

use App\Service\BookService;

/**
 * 图书
 */
class Book extends ApiBaseController
{
    /**
     * @var BookService
     */
    protected $BookService;

    protected int $bookid = 0;

    protected array $book = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->addServices('BookService');

        parent::__construct();

        $this->bookid = inputOne('r', 'bookid', INPUT_TYPE_INT);

        if ($this->bookid) {
            try {
                $this->book = $this->BookService->getBook($this->bookid);
            } catch (\Throwable $ex) {
                $this->resp($ex->getMessage(), I_FAILURE, ['bookid' => $this->bookid], 404);
            }
        }
    }

    public function index()
    {
        $title = inputOne('g', 'title', INPUT_TYPE_STR);

        $data = $this->BookService->items($title);

        $this->resp('图书列表', I_SUCCESS, $data);
    }

    public function download()
    {
        $this->BookService->download($this->book);
    }

    /**
     * 更新
     */
    public function update()
    {
        $data = input(
            'p',
            [
                'title' => INPUT_TYPE_STR,
                'author' => INPUT_TYPE_STR,
                'summary' => INPUT_TYPE_STR,
                'source' => INPUT_TYPE_STR,
            ]
        );

        try {
            if ($this->bookid) {
                $book['bookid'] = $this->BookService->updateBook($data, $this->bookid);
            } else {
                $book['bookid'] = $this->BookService->addBook($data);
            }

            $this->resp('保存成功。', I_SUCCESS, $book);
        } catch (\Throwable $ex) {
            $this->teapot($ex, $data);
        }
    }

    /**
     * 图书
     */
    public function get()
    {
        try {
            $this->resp('成功获取信息。', I_SUCCESS, $this->book);
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['bookid' => $this->bookid]);
        }
    }

    /**
     * 删除
     */
    public function delete()
    {
        try {
            $this->BookService->deleteBook($this->bookid);

            $this->resp('成功删除图书。');
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['bookid' => $this->bookid]);
        }
    }

    /**
     * 图书目录
     */
    public function getChapters()
    {
        try {
            $chapters = $this->BookService->getChapters($this->book);

            $this->resp('成功获取信息。', I_SUCCESS, $chapters);
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['bookid' => $this->bookid]);
        }
    }

    /**
     * 图书章节内容
     */
    public function getChapter()
    {
        try {
            $chapterid = inputOne('r', 'chapterid', INPUT_TYPE_INT);
            $next = inputOne('r', 'next', INPUT_TYPE_INT);

            if ($next) {
                $content = $this->BookService->getChapterWithBN($this->book, $chapterid);
            } else {
                $content = $this->BookService->getChapter($this->book, $chapterid);
            }

            $this->resp('成功获取信息。', I_SUCCESS, $content);
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['bookid' => $this->bookid]);
        }
    }

    /**
     * 更新章节内容
     */
    public function updateChapter()
    {
        $data = input(
            'p',
            [
                'title' => INPUT_TYPE_STR,
                'content' => INPUT_TYPE_STR,
                'volumeid' => INPUT_TYPE_INT,
                'chapterid' => INPUT_TYPE_INT,
            ]
        );

        $data['bookid'] = $this->bookid;

        try {
            if ($data['chapterid']) {
                $chap['chapterid'] = $this->BookService->updateChapter($data);
            } else {
                $chap['chapterid'] = $this->BookService->addChapter($data);
            }

            $this->resp('保存成功。', I_SUCCESS, $chap);
        } catch (\Throwable $ex) {
            $this->teapot($ex, $data);
        }
    }

    /**
     * 删除章节
     */
    public function deleteChapter()
    {
        $chapterid = inputOne('r', 'chapterid', INPUT_TYPE_INT);

        try {
            $this->BookService->deleteChapter($chapterid);

            $this->resp('成功删除图书章节。');
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['chapterid' => $chapterid]);
        }
    }

    /**
     * 图书卷
     */
    public function getVolumes()
    {
        try {
            $vols = $this->BookService->getVolumes($this->bookid);

            $vols['book'] = $this->book;

            $this->resp('成功获取信息。', I_SUCCESS, $vols);
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['bookid' => $this->bookid]);
        }
    }

    /**
     * 更新卷
     */
    public function updateVolume()
    {
        $data = input(
            'p',
            [
                'title' => INPUT_TYPE_STR,
                'volumeid' => INPUT_TYPE_INT,
            ]
        );

        try {
            if ($data['volumeid']) {
                $vol['volumeid'] = $this->BookService->updateVolume($data);
            } else {
                $vol['volumeid'] = $this->BookService->addVolume($data['title'], $this->bookid);
            }

            $this->resp('保存成功。', I_SUCCESS, $vol);
        } catch (\Throwable $ex) {
            $this->teapot($ex, $data);
        }
    }

    /**
     * 删除卷
     */
    public function deleteVolume()
    {
        $volumeid = inputOne('r', 'volumeid', INPUT_TYPE_INT);

        try {
            $this->BookService->deleteVolume($volumeid);

            $this->resp('成功删除图书卷。');
        } catch (\Throwable $ex) {
            $this->teapot($ex, ['volumeid' => $volumeid]);
        }
    }
}
