<?php
require_once(dirname(__FILE__) . '/DbException.class.php');

/**
 * 範囲指定検索のoffset、limit指定が不正な場合の例外クラス
 *
 * @version ver 0.1
 */
class InvalidOffsetLimitException extends DbException
{

    /**
     * コンストラクタ
     *
     * @access public
     * @param string $message メッセージ
     * @param integer $code 例外コード
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

}
