<?php
/**
 * DB例外クラス
 *
 * @version ver 0.1
 */
class DbException extends Exception
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

    /**
     * オブジェクトの文字列表現を取得する
     *
     * @access public
     * @return このオブジェクトの文字列表現
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
