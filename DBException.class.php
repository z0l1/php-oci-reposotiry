<?php
class DBException extends Exception
{

	protected $sql_code;
	protected $sql_message;
	protected $sql_query;
	protected $sql_query_offset;

	public function __construct($message, $code = 0, $sqlmessage = "", $sqlcode = -1, $query = "", $query_offset = -1)
	{
		parent::__construct($message, $code);

		$this->sql_code = $sqlcode;
		$this->sql_message = $sqlmessage;
		$this->sql_query = $query;
		$this->sql_query_offset = $query_offset;
	}

	public function getSqlMessage()
	{
		return "[{$this->message}]\n[ERRCODE({$this->sql_code})]: {{$this->sql_message}}\n[AT({$this->sql_query_offset})]> {{$this->sql_query}}";
	}
	public function getSqlArr()
	{
		return [
			"title" => $this->message,
			"code" => $this->sql_code,
			"message" => $this->sql_message,
			"offset" => $this->sql_query_offset,
			"sqltext" => $this->sql_query
		];
	}
	// protected function generateSqlMessage($message)
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]> {{$this->message}}\n[SQL]: [{$this->sql_code}]> {{$this->sql_message}}\n[{$this->sql_query_offset}]> {{$this->sql_query}}";
	}
}

class OciException extends DBException
{
	public function __construct($message, $code = 0, $oci_error = null)
	{

		$oci_err_code = isset($oci_error["code"]) ? $oci_error["code"] : -1;
		$oci_err_message = isset($oci_error["message"]) ? $oci_error["message"] : "[empty]";
		$oci_err_offset = isset($oci_error["offset"]) ? $oci_error["offset"] : -1;
		$oci_err_sqltext = isset($oci_error["sqltext"]) ? $oci_error["sqltext"] : "[empty]";

		parent::__construct($message, $code, $oci_err_message, $oci_err_code, $oci_err_sqltext, $oci_err_offset);
	}
}

class OciConnectionException extends OciException
{
	public function __construct($err)
	{
		parent::__construct("[ERROR AT CONNECTION]", 0, $err);
	}
}

class OciParseException extends OciException
{
	public function __construct($err)
	{
		parent::__construct("[ERROR AT PARSING]", 0, $err);
	}
}

class OciBindException extends OciException
{
	public function __construct($paramname, $paramvalue, $err)
	{
		parent::__construct("[ERROR AT BINDING ({{$paramvalue}} to {:{$paramname}}]", 0, $err);
	}
}

class OciExecuteException extends OciException
{
	public function __construct($err)
	{
		parent::__construct("[ERROR AT EXECUTING]", 0, $err);
	}
}

class OciFetchException extends OciException
{
	public function __construct($err)
	{
		parent::__construct("[ERROR AT FETCHING]", 0, $err);
	}
}
