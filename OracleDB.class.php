<?php

include("DBException.class.php");

class OracleDB
{
	private $db_connection;
	private $db_command;
	private $q_result;
	private $affected_rows;

	private $commit_mode;

	public function __construct()
	{
		$this->commit_mode = OCI_COMMIT_ON_SUCCESS;
	}

	public function test()
	{
		echo "############-COMMIT_MODE:[{$this->commit_mode}];-###############";
	}

	public function connect($db_server, $db_name, $db_user, $db_password, $persistent = false)
	{
		$this->db_server = $db_server;
		$this->db_name = $db_name;
		$this->db_user = $db_user;
		$this->db_password = $db_password;

		if ($persistent === true) {
			$this->db_connection = oci_pconnect($db_user, $db_password, $db_server, 'AL32UTF8');
		} else if ($persistent === false) {
			$this->db_connection = oci_connect($db_user, $db_password, $db_server, 'AL32UTF8');
		} else {
			throw new InvalidArgumentException("argument 'persistent':'$persistent' is not boolean");
		}

		if (!$this->db_connection) {
			$e = oci_error();
			throw new OciConnectionException($e);
		}
	}

	public function disconnect()
	{
		// if ($this->db_command) { oci_free_statement($this->db_command); }
		$res = oci_close($this->db_connection);
		if (!$res) {
			$e = oci_error($this->db_command);
			throw new OciException("[ERROR WHILE DISCONNECTING]", 0, $e);
		}
	}

	public function setAutoCommit($autoCommit)
	{
		if (gettype($autoCommit) !== "boolean") {
			throw new InvalidArgumentException("argument 'autoCommit':'$autoCommit' is not boolean");
		}
		$this->commit_mode = $autoCommit === true ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
	}

	public function commit()
	{
		$res = oci_commit($this->db_connection);
		if (!$res) {
			$e = oci_error($this->db_connection);
			throw new OciException("[ERROR AT COMMIT]", 0, $e);
		}
	}

	public function rollback()
	{
		oci_rollback($this->db_connection);
	}

	public function setQuery($query)
	{
		$stid = oci_parse($this->db_connection, $query);

		if (!$stid) {
			$e = oci_error($this->db_connection);
			throw new OciParseException($e);
		}

		$this->db_command = $stid;
	}

	public function setQueryParameter($paramname, &$variable, $maxlen = -1, $type = SQLT_CHR)
	{
		if (!isset($this->db_command) || !$this->db_command) {
			throw new OciException("[(setQueryParameter): query not set]");
		}

		$res = @oci_bind_by_name($this->db_command, $paramname, $variable, $maxlen, $type);
		if (!$res) {
			$e = oci_error($this->db_command);
			throw new OciBindException($paramname, $variable, $e);
		}
	}

	public function executeQuery()
	{
		if (!isset($this->db_command) || !$this->db_command) {
			throw new OciException("[(executeQuery): query not set]");
		}

		if (!$this->db_connection) {
			$e = oci_error();
			throw new OciException("[(executeQuery): could not find connection]");
		}

		$stid = $this->db_command;

		if (!$stid) {
			oci_free_statement($stid);
			oci_free_statement($this->db_command);
			throw new OciException("[(executeQuery): error with query (empty?)]");
		}

		$res = @oci_execute($stid, $this->commit_mode);

		if (!$res) {
			$e = oci_error($stid);
			oci_free_statement($stid);
			throw new OciExecuteException($e);
		}

		$fetchresult = [];
		$nrows = @oci_fetch_all($stid, $fetchresult, null, null, OCI_FETCHSTATEMENT_BY_ROW);

		if ($nrows === false) {
			$e = oci_error($stid);
			throw new OciFetchException($e);
		}

		$this->affected_rows = $nrows;

		if ($nrows === 0) {
			$this->q_result = array();
			return;
		}

		$this->q_result = $fetchresult;
	}

	public function getResult()
	{
		return $this->q_result;
	}

	public function getAffectedRows()
	{
		return $this->affected_rows;
	}

	public function __destruct()
	{
		$this->disconnect();
		unset($this->q_result);
		unset($this->db_command);
		unset($this->db_connection);
	}
}

//$db = new db(odb_server, odb_name, odb_user, odb_password);
