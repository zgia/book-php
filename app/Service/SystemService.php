<?php

namespace App\Service;

/**
 * Class SystemService
 */
class SystemService extends BaseService
{
    /**
     * 分类
     *
     * 注：分类parentid为0，表示父分类，title务必是字母+数字或者只有字母，请勿使用其他字符
     *
     * @return []
     */
    public static function categories()
    {
        $cats = static::neoModel('category')->rows([], ['orderby' => 'parentid,id']);

        $categories = [];
        $titles = [];
        foreach ($cats as $cat) {
            if ($cat['parentid'] == 0) {
                $titles[$cat['id']] = strtolower($cat['title']);
                $categories[strtolower($cat['title'])] = [];
            } else {
                $categories[$titles[$cat['parentid']]][] = [
                    'value' => $cat['id'],
                    'title' => $cat['title'],
                ];
            }
        }

        return $categories;
    }

    /**
     * 分类
     *
     * 注：分类parentid为0，表示父分类，title务必是字母+数字或者只有字母，请勿使用其他字符
     *
     * @return []
     */
    public static function categoriesKV()
    {
        $cats = static::neoModel('category')->rows([], ['orderby' => 'parentid,id']);

        $categories = [];
        $titles = [];
        foreach ($cats as $cat) {
            if ($cat['parentid'] == 0) {
                $titles[$cat['id']] = strtolower($cat['title']);
                $categories[strtolower($cat['title'])] = [];
            } else {
                $categories[$titles[$cat['parentid']]][$cat['id']] = $cat['title'];
            }
        }

        return $categories;
    }
}
