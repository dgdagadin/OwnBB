<?php

/*********************************************************
 * имя класса: OwnBB_Transactions
 * функция класса: имитация транзакций 
 ********************************************************/

class OwnBB_Transactions {

	/*
	//////////////////////
	/////СВОЙСТВА/////////
	//////////////////////
	*/
	
	/**
	 * массив откатов
	 * (array)
	 */
	var $QueryCommitArray;
		
	/**
	 * Тип СУБД
	 * (string)
	 */
	var $DBType;
	
	/**
	 * Ресурс соединения с БД
	 * (resource)
	 */
	var $ConnResource;
	
	/**
	 * Путь к логу ошибок транзакций
	 * (string)
	 */
	var $PathToLog;
	
	/*
	//////////////////////
	/////МЕТОДЫ///////////
	//////////////////////
	*/
	
	/**
	 * Конструктор класса - Инициализация. Параметры:
	 *   (array)   $DBType             Данные лог-функции
	 *   (array)   $ConnResource       Данные ф-ции запроса к БД
	 *   (array)   $PathToLog          Данные ф-ции экранирования спецсимволов БД
	 * -
	 */ 
	function __construct ($DBType, $ConnResource, $PathToLog) {		
		$this->DBType        = $DBType;
		$this->ConnResource  = $ConnResource;
		$this->PathToLog     = $PathToLog;    
	}
	
	/**
	 * Старт транзакции. Параметры:
	 *   - 
	 * -
	 */
	public function StartTransaction () {
		$this->QueryCommitArray = array ();
	}
	
	/**
	 * Завершение транзакции. Параметры:
	 *   - 
	 * -
	 */
	public function CommitTransaction () {
		unset ($this->QueryCommitArray);
	}
	
	/**
	 * Откат транзакции. Параметры:
	 *   -
	 * -
	 */
	public function RollbackTransaction () {
		$MainArray = $this->QueryCommitArray;
					
		foreach ($MainArray as $MainKey => $MainValue) {
			$Operation  = $MainValue['Operation'];
			$FieldArray = $MainValue['FieldArray'];
			$WhereArray = $MainValue['WhereArray'];
			$TableName  = $MainValue['TableName'];
		
			$RollbackSQL = $this->GenerateSQLString ($Operation, $TableName, $FieldArray, $WhereArray);
			$this->ExecuteQuery ($RollbackSQL);
		}
	}
	
	/**
	 * Добавляет в память массив запроса для последующего возможного применения. Параметры: 
	 *   (array)   $QueryArray         массив для транзакции  
	 * -
	 */
	public function PrepareQueryToRollback ($QueryArray) {
		$this->QueryCommitArray[] = $QueryArray;
	}
	
	/**
	 * Генерирует стоку запроса. Параметры:
	 *   (string)  $Operation          сокращение операции  
	 *   (string)  $TableName          название таблицы  
	 *   (array)   $FieldArray         массив полей, с которыми производится операция  
	 *   (array)   $WhereArray         массив полей с условиями  
	 * (string) $Result
	 */
	public function GenerateSQLString ($Operation, $TableName, $FieldArray, $WhereArray) {
		$Operation = $this->CorrectOperation ($Operation);
		$SQLAction = $this->SetQueryAction ($Operation);
		$SQLFrom   = $this->SetQueryFrom ($Operation);
		$SQLSet    = $this->SetQuerySet ($Operation);
		$SQLInto   = $this->SetQueryInto ($Operation);
		$SQLFields = $this->SetQueryFields ($Operation, $FieldArray);
		$SQLWhere  = $this->SetQueryWhere ($WhereArray);
		$SQLTable  = $this->PrepareDataType ($TableName, 'string');
	
		$Result = $SQLAction . $SQLFrom . $SQLInto . $SQLTable . $SQLSet . $SQLFields . $SQLWhere;
		return ($Result);
	}

	/**
	 * Получает поля запроса. Параметры:
	 *   (array)  $FieldArray          сокращение операции  
	 * (string) $Result
	 */
	public function SetQueryFields ($Operation, $FieldArray) {
		if ($Operation == 'u') {
			$FieldOperations = array ();
			foreach ($FieldArray as $FieldKey => $FieldValue) {
				$OneOperation = $FieldKey . '=';
				$Value = $FieldValue['Value'];
				$Flag  = $FieldValue['Flag'];
				$Type  = $FieldValue['Type'];
			
				if ($Flag == 'none') {
					$OneOperation .= '\'' . $this->PrepareDataType ($Value, $Type) . '\'';
				}
				else {
					$Operator = $Flag == 'incr' ? '+' : '-';
					$OneOperation .= $FieldKey . $Operator . '1';
				}
				$FieldOperations[] = $OneOperation; 
			}    
			if (sizeof ($FieldOperations) > 0) {
				$Result = implode (', ', $FieldOperations);
			}
			else {
				$Result = '';
			}
		}
		else if ($Operation == 'i') {
			$FieldNames  = array ();
			$FieldValues = array ();        
			foreach ($FieldArray as $FieldKey => $FieldValue) {
				$Value = $FieldValue['Value'];            
				$Type  = $FieldValue['Type'];
		
				$FieldNames[]  = $FieldKey;
				$FieldValues[] = '\'' . $this->PrepareDataType ($Value, $Type) . '\'';
			}
			if ((sizeof ($FieldNames) > 0) && (sizeof ($FieldValues) > 0)) {
				$NameString  = '(' . implode (',', $FieldNames)  . ')';
				$ValueString = '(' . implode (',', $FieldValues) . ')';
				$Result = ' ' . $NameString . ' VALUES ' . $ValueString . ' ';
			}
			else {
				$Result = '';
			}
		}
		else {
			$Result = NULL;
		}
		return ($Result);
	}
	
	/**
	 * Получает поля запроса. Параметры:
	 *   (array)  $FieldArray          сокращение операции  
	 * (string) $Result
	 */
	public function SetQueryWhere ($WhereArray) {
		$WhereFields = array ();
		foreach ($WhereArray as $WhereKey=>$WhereArray) {
			$Value = $WhereArray['Value'];
			$Type  = $WhereArray['Type'];
			$OneCondition = $WhereKey . '=\'' . $this->PrepareDataType ($Value, $Type) . '\'';
			$WhereFields[] = $OneCondition;
		}
	
		if (sizeof ($WhereFields) > 0) {
			$Result = ' WHERE ' . implode (' AND ', $WhereFields);
		}
		else {
			$Result = '';
		}
		return ($Result);
	}
	
	/**
	 * Получает главное действие запроса. Параметры:
	 *   (string)  $Operation          сокращение операции  
	 * (string) $Result
	 */
	public function SetQueryAction ($Operation) {            
		switch ($Operation) {
			case 'd': 
			default:{
				$Result = 'DELETE';
				break;
			}
			case 'u':{
				$Result = 'UPDATE';
				break;
			}        
			case 'i':{
				$Result = 'INSERT';
				break;
			}
		}
	
		$Result .= ' ';
		return ($Result);
	}
	
	/**
	 * Получает from-часть запроса. Параметры:
	 *   (string)  $Operation          сокращение операции  
	 * (string) $Result
	 */
	public function SetQueryFrom ($Operation) {            
		$Result = $Operation == 'd' ? ' FROM ' : '';
		return ($Result);
	}
	
	/**
	 * Получает set-часть запроса. Параметры:
	 *   (string)  $Operation          сокращение операции  
	 * (string) $Result
	 */
	public function SetQuerySet ($Operation) {            
		$Result = $Operation == 'u' ? ' SET ' : '';
		return ($Result);
	}
	
	/**
	 * Получает into-часть запроса. Параметры:
	 *   (string)  $Operation          сокращение операции  
	 * (string) $Result
	 */
	public function SetQueryInto ($Operation) {            
		$Result = $Operation == 'i' ? ' INTO ' : '';
		return ($Result);
	}
	
	/**
	 * Возвращает операцию в нижнем регистре. Параметры:
	 *   (string)  $Operation          сокращение операции  
	 * (string) $Result
	 */
	public function CorrectOperation ($Operation) {
		$Result = Main_Strtolower ($Operation);
		return ($Result);
	}
	
	/**
	 * Выполняет запрос. Параметры:
	 *   (string)  $SQLString          строка запроса   
	 * -
	 */
	public function ExecuteQuery ($SQLString) {
		$DBType  = $this->DBType;
		$Connect = $this->ConnResource;
		$LogFile = $this->PathToLog;
	
		$TransactionQuery = DB_Query ($DBType, $SQLString, $Connect);  
		if (!$TransactionQuery) {
			OBB_Main_Log ($SQLString . "\r\n" . DB_Error ($DBType), $LogFile);
		}
	}
 
	/**
	 * Подготавливает строку к безопасной работе с БД. Параметры:
	 *   (string)  $Value              значение
	 *   (string)  $Type               тип данных
	 * (string) $Result
	 */
	public function PrepareDataType ($Value, $Type) {    
		$DataType = Main_Strtolower ($Type);
		switch ($DataType) {
			case 'string':
			default: {
				$Result = DB_EscapeString ($this->DBType, $Value);
				break;
			}
			case 'int': {
				$Result = intval ($Value);
				break;
			}
			case 'double': {
				$Result = floatval ($Value);
				break;
			}
		}
		return ($Result);
	}

}

?>