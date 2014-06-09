<?php
require_once(dirname(__FILE__) . '/DbAccess.interface.php');
require_once(dirname(__FILE__) . '/exception/DbException.class.php');
require_once(dirname(__FILE__) . '/exception/InvalidOffsetLimitException.class.php');

/**
 * 全てのDBアクセスクラスの基底となるクラス
 *
 * @version ver 0.1
 */
abstract class AbstractDbAccess implements DbAccess
{

    protected $dbms = null;

    protected $conn = null;

    /**
     * コンストラクタ
     *
     * @access protected
     */
    protected function __construct()
    {
    }

    /**
     * DBに接続する
     *
     * @access public
     * @param string $connectParams
     */
    public function connect($connectParams)
    {
        $this->connectWithTarget($connectParams);
    }

    /**
     * 検索処理を行う
     *
     * @access public
     * @param string $sql SQL
     * @param array $binds バインド値の配列
     * @param boolean $isSingleRecord 単一レコード取得時はtrueを指定
     * @return mixed 検索結果を格納した配列、単一レコード取得を行いデータがなかった場合はnull、複数レコード取得を行いデータがなかった場合は空配列
     */
    public function select($sql, array $binds = null, $isSingleRecord = false)
    {
        try {
            // ステートメント実行
            $stmt = $this->executeStatement($sql, $binds);

            // クエリー結果取得
            $resultArray = $this->getQueryResult($stmt, $isSingleRecord);

        } catch (Exception $e) {
            $stmt = null;
            $this->dbError($e);

        }

        $stmt = null;

        return $resultArray;
    }


    /**
     * 検索処理を行い、指定した範囲の結果を取得する
     *
     * @access public
     * @param string $sql SQL
     * @param integer $offset 取得開始位置
     * @param integer $limit 取得件数
     * @param array $binds バインド値の配列
     * @return mixed 検索結果を格納した配列
     */
    public function selectRange($sql, $offset, $limit, array $binds = null)
    {
        try {
            // offsetとlimitの整数チェック
            $this->_checkOffsetAndLimit($offset, $limit);

            // 取得範囲指定
            $rangeSql = $this->getRangeSql($this->dbms, $offset, $limit);
            $sql .= $rangeSql;

            // ステートメント実行
            $stmt = $this->executeStatement($sql, $binds);

            // クエリー結果取得
            $resultArray = $this->getQueryResult($stmt);

        } catch(Exception $e) {
            $stmt = null;
            $this->dbError($e);

        }

        // データが0件の場合は空の配列を返す
        if (!isset($resultArray)) {
            $resultArray = array();
        }

        $stmt = null;

        return $resultArray;
    }

    /**
     * 範囲指定SQL文を取得する
     *
     * @access private
     * @static
     * @param string $dbms DBMS
     * @param integer $offset 取得開始位置
     * @param integer $limit 取得件数
     * @return string 範囲指定SQL文
     */
    private function getRangeSql($dbms, $offset, $limit)
    {
        if ($dbms == 'mysql' || $dbms == 'postgres') {
            return ' LIMIT ' . $offset . ',' . $limit;
        } else {
            return null;
        }
    }

    /**
     * offsetとlimitの整数チェックを行う
     *
     * @access private
     * @param integer $offset 取得開始位置
     * @param integer $limit 取得件数
     */
    private function _checkOffsetAndLimit($offset, $limit)
    {
        if ($this->_isEmptyString($offset, true) || $this->_isEmptyString($limit, true)) {
            throw new InvalidOffsetLimitException('selectRange offset[' . $offset . '] or limit[' . $limit . '] is empty');
        } else {
            if (!is_int($offset)) {
                throw new InvalidOffsetLimitException('selectRange offset[' . $offset . '] is not number');
            }
            if (!is_int($limit)) {
                throw new InvalidOffsetLimitException('selectRange limit[' . $limit . '] is not number');
            }
        }
    }

    /**
     * 引数の文字列が空かどうか判定する
     * ※「変数がセットされているか」の判定でこの関数を使用しないこと
     *
     * @access protected
     * @static
     * @param string $value チェック対象文字列
     * @param boolean $isTrim チェック対象文字列をtrimするか
     * @return boolean 空の場合true、そうでない場合false
     */
    protected function _isEmptyString($value, $isTrim = false)
    {
        if (isset($value)) {
            if ($isTrim) {
                $value = $this->_mbTrim($value);
            }
            return $value === '';

        } else {
            return false;
        }

    }

    /**
     * 引数の文字列内の空白(全角半角問わず)を除去した文字列を返す
     *
     * @access protected
     * @static
     * @param string $value 対象文字列
     * @return string 空白を除去した文字列
     */
    protected function _mbTrim($value)
    {
        $afterValue = mb_ereg_replace("^[ 　]+", "", $value);
        $afterValue = mb_ereg_replace("[ 　]+$", "", $afterValue);
        return trim($afterValue);
    }

    /**
     * ステートメントを実行する
     *
     * @access protected
     * @param string $sql 実行SQL
     * @param array $binds バインド値の配列
     * @return mixed SQL実行済みのステートメント
     */
    protected abstract function executeStatement($sql, array $binds = null);

    /**
     * クエリー結果を取得する
     *
     * @access protected
     * @param mixed $stmt SQL実行済みステートメント
     * @param boolean $isSingleRecord 単一レコード取得時はtrueを指定
     * @return mixed 検索結果を格納した配列
     */
    protected abstract function getQueryResult($stmt, $isSingleRecord = false);

    /**
     * 更新処理を行う
     *
     * @access public
     * @param string $sql SQL
     * @param array $binds バインド値の配列
     * @return mixed 更新件数
     */
    public function update($sql, array $binds = null)
    {
        try {
            // ステートメント実行
            $stmt = $this->executeStatement($sql, $binds);

            // 更新件数取得
            $updCount = $this->getUpdateResult($stmt);

        } catch (Exception $e) {
            $stmt = null;
            $this->dbError($e);

        }

        $stmt = null;

        return $updCount;
    }

    /**
     * 更新結果を取得する
     *
     * @access protected
     * @param mixed $stmt SQL実行済みステートメント
     * @return integer 更新件数
     */
    protected abstract function getUpdateResult($stmt);

    /**
     * DB処理でエラー発生時のエラーハンドリングを行う
     *
     * @access protected
     * @param Exception $e DB処理で発生した例外
     */
    protected function dbError($e)
    {
        throw new DbException($e->getMessage());
    }

}
