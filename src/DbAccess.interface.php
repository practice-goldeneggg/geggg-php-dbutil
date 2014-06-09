<?php
/**
 * DBアクセス用の機能を提供するインタエース
 *
 * @version ver 0.1
 */
interface DbAccess
{

    /**
     * DBに接続する
     *
     * @access public
     * @param string $sign 接続ホストを識別する識別子
     */
    public function connect($sign);

    /**
     * 固有の接続対象を指定してDBに接続する
     *
     * @access public
     * @param array $connectParams array('dbms' => DBMS, 'host' => 接続先ホスト, 'port' => 接続ポート, 'dbname' => 接続DB名, 'user' => 接続ユーザー, 'password' => 接続パスワード)
     */
    public function connectWithTarget($connectParams);

    /**
     * トランザクションを開始する
     *
     * @access public
     */
    public function beginTransaction();

    /**
     * 検索処理を行う
     *
     * @access public
     * @param string $sql SQL
     * @param array $binds バインド値の配列
     * @param boolean $isSingleRecord 単一レコード取得時はtrueを指定
     * @return mixed 検索結果を格納した配列、単一レコード取得を行いデータがなかった場合はnull、、複数レコード取得を行いデータがなかった場合は空配列
     */
    public function select($sqlfile, array $binds = null, $isSingleRecord = false);

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
    public function selectRange($sqlfile, $offset, $limit, array $binds = null);

    /**
     * 更新系処理を行う
     *
     * @access public
     * @param string $sql SQL
     * @param array $binds バインド値の配列
     * @return mixed 更新件数
     */
    public function update($sqlfile, array $binds = null);

    /**
     * 直近の挿入処理で新たに採番されたシーケンスを取得する
     *
     * @access public
     * @param string $seqname 対象シーケンス名
     * @return integer 直近の挿入処理で新たに採番されたシーケンス
     */
    public function getLastInsertId($seqname = null);

    /**
     * コミットする
     *
     * @access public
     */
    public function commit();

    /**
     * ロールバックする
     *
     * @access public
     */
    public function rollback();

    /**
     * DBを切断する
     *
     * @access public
     */
    public function close();

}
