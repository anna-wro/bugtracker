<?php
/**
 * Bookmarks.
 *
 * @link http://epi.chojna.info.pl
 * @author EPI UJ <epi@uj.edu.pl>
 * @copyright (c) 2017
 */

namespace Model\Bookmarks\Csv;

/**
 * Class BookmarksCsv.
 */
class Bookmarks
{
    /**
     * Bookmarks.
     *
     * @var array $bookmarks
     */
    protected $bookmarks = [];

    /**
     * Bookmarks constructor.
     */
    public function __construct()
    {
        $this->bookmarks = $this->loadData();
    }

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

    /**
     * Load data from CSV file.
     *
     * @see http://php.net/manual/en/function.fgetcsv.php
     *
     * @return array Result
     */
    protected function loadData()
    {
        $fileName = dirname(__FILE__).'/bookmarks.csv';
        $result = [];

        if (($handle = fopen($fileName, 'r')) !== false) {
            $rowCounter = 0;
            $data = [];
            while (($row = fgetcsv($handle, 1000, ',', '\'')) !== false) {
                if (0 == $rowCounter) {
                    $headers = $row;
                } else {
                    $data[] = $row;
                }
                $rowCounter++;
            }
            foreach ($headers as &$header) {
                $header = mb_strtolower($header, 'UTF-8');
            }
            unset($header);

            foreach ($data as $datum) {
                $i = 0;
                $resultRow = [];
                foreach ($datum as $item) {
                    if ('tags' == $headers[$i]) {
                        $resultRow[$headers[$i]] = explode(',', $item);
                    } else {
                        $resultRow[$headers[$i]] = $item;
                    }
                    $i++;
                }
                $result[] = $resultRow;
            }

            fclose($handle);
        }

        return $result;
    }
}