<?php
/**
 * Bookmarks.
 *
 * @link http://epi.chojna.info.pl
 * @author EPI UJ <epi@uj.edu.pl>
 * @copyright (c) 2017
 */

namespace Model\Bookmarks\Arr;

/**
 * Class Bookmarks.
 */
class Bookmarks
{
    /**
     * Bookmarks.
     *
     * @var array $bookmarks
     */
    protected $bookmarks = [
        [
            'title' => 'PHP manual',
            'url'   => 'http://php.net',
            'tags'  => [
                'PHP',
                'manual',
            ],
        ],
        [
            'title' => 'Silex',
            'url'   => 'http://silex.sensiolabs.org',
            'tags'  => [
                'PHP',
                'framework',
                'Silex',
            ],
        ],
        [
            'title' => 'Learn Git Branching',
            'url'   => 'http://learngitbranching.js.org',
            'tags'  => [
                'tools',
                'Git',
                'VCS',
                'tutorials',
            ],
        ],
        [
            'title' => 'PhpStorm',
            'url'  => 'https://www.jetbrains.com/phpstorm',
            'tags' => [
                'tools',
                'IDE',
                'PHP',
            ],
        ],
        [
            'title' => 'Twig',
            'url'  => 'http://twig.sensiolabs.org',
            'tags' => [
                'tools',
                'templates',
                'Twig',
                'Silex',
                'PHP',
            ],
        ],
    ];

    /**
     * Find all bookmarks.
     *
     * @return array Result
     */
    public function findAll()
    {
        return $this->bookmarks;
    }

    /**
     * Find bookmark by its id.
     *
     * @param integer $id Bookmark id
     *
     * @return array Result
     */
    public function findOneById($id)
    {
        $bookmark = [];

        if (isset($this->bookmarks[$id]) && count($this->bookmarks[$id])) {
            $bookmark = $this->bookmarks[$id];
        }

        return $bookmark;
    }
}