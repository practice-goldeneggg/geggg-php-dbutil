<?php
require_once(dirname(__FILE__) . '/AbstractDbAccess.class.php');
require_once(dirname(__FILE__) . '/exception/AlreadyConnectedException.class.php');

/**
 * PDOを使用したDBアクセスクラス
 *
 * @version ver 0.1
 */
class PDODbAccess extends AbstractDbAccess
{

    /**
     * コンストラクタ
     *
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 固有の接続対象を指定してDBに接続する
     *
     * @access public
     * @param array $connectParams array('dbms' => DBMS, 'host' => 接続先ホスト, 'port' => 接続ポート, 'dbname' => 接続DB名, 'user' => 接続ユーザー, 'password' => 接続パスワード)
     */
    public function connectWithTarget($connectParams)
    {
        try {
            if (isset($this->conn)) {
                throw new AlreadyConnectedException('db was already connected');
            } else {
                // DSN文字列生成
                $dsnInfo = $this->getPDODsnInfo($connectParams);

                // 接続
                if (isset($dsnInfo['user']) && isset($dsnInfo['password'])) {
                    $this->conn = new PDO($dsnInfo['dsn'], $dsnInfo['user'], $dsnInfo['password']);
                } else {
                    $this->conn = new PDO($dsnInfo['dsn']);
                }
            }

            // 取得するカラム名を強制的に小文字にする
            $this->conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

            // エラー時にExceptionをthrowさせる
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $e) {
            $this->dbError($e);

        }
    }

    /**
     * PDOによるDB接続で使用するDSN文字列を取得する
     *
     * @access protected
     * @param array $connectParams array('dbms' => DBMS, 'host' => 接続先ホスト, 'port' => 接続ポート, 'dbname' => 接続DB名, 'user' => 接続ユーザー, 'password' => 接続パスワード)
     * @return array PDO接続時に使用する情報 array('dsn' => DSN文字列, 'user' => 接続ユーザー, 'password' => 接続パスワード)
     */
    protected function getPDODsnInfo($connectParams)
    {
        if ($this->_isEmptyString($connectParams['port'])) {
            $port = '';
        } else {
            $port = '; port=' . $connectParams['port'];
        }

        if ($connectParams['dbms'] == 'mysql') {
            // MySQL
            $dsn = 'mysql:host=' . $connectParams['host'] . $port .
                '; dbname=' . $connectParams['dbname'];
            $user = $connectParams['user'];
            $password = $connectParams['password'];
        } elseif ($connectParams['dbms'] == 'postgres') {
            // Postgres
            $dsn = 'pgsql:host=' . $connectParams['host'] .
                ' port=' . $connectParams['port'] .
                ' dbname=' . $connectParams['dbname'] .
                ' user=' . $connectParams['user'] .
                ' password=' . $connectParams['password'];
            $user = null;
            $password = null;
        }

        // DBMS情報設定
        $this->dbms = $connectParams['dbms'];

        return array('dsn' => $dsn, 'user' => $user, 'password' => $password);
    }

    /**
     * トランザクションを開始する
     *
     * @access public
     */
    public function beginTransaction()
    {
        try {
            $this->conn->beginTransaction();

            // 自動コミットをOff(Postgresではここでエラーが発生する)
            $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);

        } catch (PDOException $e) {
            $this->dbError($e);

        }
    }

    /**
     * ステートメントを実行する
     *
     * @access protected
     * @param string $sql 実行SQL
     * @param array $binds バインド値の配列
     * @return PDOStatement SQL実行済みのPDOStatement
     */
    protected function executeStatement($sql, array $binds = null)
    {
        try {
            // prepareを実行しPDOStatementを取得
            $stmt = $this->conn->prepare($sql);

            // バインド変数を設定する
            if (isset($binds)) {
                foreach ($binds as $bindkey => $bindvalue) {
                    //※重要、bindParamを使うと2番目の引数(value)を参照渡しで渡すので、こういうループで使ってしまうと毎度上書きされる
                    $stmt->bindValue($bindkey, $bindvalue);
                }
            }

            // SQLを実行する
            $stmt->execute();

        } catch (PDOException $e) {
            $stmt = null;
            throw $e;

        }

        return $stmt;
    }

    /**
     * クエリー結果を取得する
     *
     * @access protected
     * @param mixed $stmt SQL実行済みステートメント
     * @param boolean $isSingleRecord 単一レコード取得時はtrueを指定
     * @return mixed 検索結果を格納した配列、単一レコード取得を行いデータがなかった場合はnull、、複数レコード取得を行いデータがなかった場合は空配列
     */
    protected function getQueryResult($stmt, $isSingleRecord = false)
    {
        $resultArray = null;
        try {
            // 実行結果を取得
            if ($isSingleRecord) {
                $resultArray = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($resultArray === false) {
                    $resultArray = null;
                }
            } else {
                $resultArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (PDOException $e) {
            $stmt = null;
            throw $e;

        }

        return $resultArray;
    }

    /**
     * 更新結果を取得する
     *
     * @access protected
     * @param mixed $stmt SQL実行済みステートメント
     * @return integer 更新件数
     */
    protected function getUpdateResult($stmt)
    {
        try {
            // 更新件数取得
            $updCount = $stmt->rowCount();

        } catch (PDOException $e) {
            $stmt = null;
            throw $e;

        }

        return $updCount;
    }

    /**
     * 直近の挿入処理で新たに採番されたシーケンスを取得する
     *
     * @access public
     * @param string $seqname 対象シーケンス名
     * @return string 直近の挿入処理で新たに採番されたシーケンス
     */
    public function getLastInsertId($seqname = null)
    {
        if (isset($seqname)) {
            return $this->conn->lastInsertId($seqname);
        } else {
            return $this->conn->lastInsertId();
        }
    }

    /**
     * コミットする
     *
     * @access public
     */
    public function commit()
    {
        try {
            $this->conn->commit();

        } catch (PDOException $e) {
            $this->dbError($e);

        }
    }

    /**
     * ロールバックする
     *
     * @access public
     */
    public function rollback()
    {
        try {
            $this->conn->rollBack();

        } catch (PDOException $e) {
            $this->dbError($e);

        }
    }

    /**
     * DBを切断する
     *
     * @access public
     */
    public function close()
    {
        try {
            $this->conn = null;

        } catch (PDOException $e) {
            $this->dbError($e);

        }
    }

}
