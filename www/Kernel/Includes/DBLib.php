<?php

//соединение с сервером
function DB_Connect ($DBType) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_connect (OBB_CONNECTION_HOST, OBB_CONNECTION_USER, OBB_CONNECTION_PASSWORD);
			break;
		}

		default:
		{
			return mysql_connect (OBB_CONNECTION_HOST, OBB_CONNECTION_USER, OBB_CONNECTION_PASSWORD);
			break;
		}
	}
}

//Выбор базы данных
function DB_SelectDatabase ($DBType, $DBName, $ServerConn) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_select_db ($DBName, $ServerConn);
			break;
		}

		default:
		{
			return mysql_select_db ($DBName, $ServerConn);
			break;
		}
	}
}

//Запрос к базе данных
function DB_Query ($DBType, $QueryString, $ServerConn) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_query ($QueryString, $ServerConn);
			break;
		}

		default:
		{
			return mysql_query ($QueryString, $ServerConn);
			break;
		}
	}
}

//Запрос к базе данных
function DB_NumRows ($DBType, $QueryResource) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_num_rows ($QueryResource);
			break;
		}

		default:
		{
			return mysql_num_rows ($QueryResource);
			break;
		}
	}
}

//Ассоциативный массив из результата запроса
function DB_FetchAssoc ($DBType, $QueryResource) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_fetch_assoc ($QueryResource);
			break;
		}

		default:
		{
			return mysql_fetch_assoc ($QueryResource);
			break;
		}
	}
}

//Ассоциативный массив из результата запроса
function DB_Result ($DBType, $QueryResource, $Row, $Column) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_result ($QueryResource, $Row, $Column);
			break;
		}

		default:
		{
			return mysql_result ($QueryResource, $Row, $Column);
			break;
		}
	}
}

//экранирование служебный SQL-символов
function DB_EscapeString ($DBType, $String) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_real_escape_string ($String);
			break;
		}

		default:
		{
			return mysql_real_escape_string ($String);
			break;
		}
	}
}

//извлечение последнего автоинкрементарного ид-шника
function DB_LastID ($DBType, $ServerConn) {
	switch ($DBType) {
		case "mysql":
		{
			return mysql_insert_id ($ServerConn);
			break;
		}

		default:
		{
			return mysql_insert_id ($ServerConn);
			break;
		}
	}
}

//ошибка бд
function DB_Error ($DBType) {
	switch ($DBType) {
		case "mysql":
		default:
		{
			return mysql_errno () . ": " . mysql_error ();
			break;
		}
	}
}

?>