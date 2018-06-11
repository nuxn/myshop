<?php
/*
 * mysql 连接类
 */
define('CLIENT_MULTI_RESULTS', 131072);
class mysql {
	private static $username;
	private static $password;
	private static $host;
	private static $database;
	private static $conn=NULL;
	private static $select_db;
	private static $dbg_mode;
	private static $code;
	private static $mysql = null;
	private static $log_slow_or_big_query=true;
	//构造函数 初使化连接数据库的各个参数  各参数可以不传，不传的时候默认连接的游戏数据库
	/*
	 * $user 数据库存用户
	 * $pass 数据库存密码
	 * $host 数据库存ip
	 * $db 数据库的数据库名
	 * $code 数据库存编码
	*/
	function __construct($user='',$pass='',$host='',$db='',$code='') {
		global $_mysql;
		self::$username=$user==''?$_mysql['user']:$user;
		self::$password=$pass==''?$_mysql['pass']:$pass;
		self::$host=$host==''?$_mysql['host']:$host;
		self::$database=$db==''?$_mysql['db']:$db;
		self::$code=$code==''?$_mysql['code']:$code;
		self::$dbg_mode=isset($_mysql['dbg_mode'])?$_mysql['dbg_mode']:false;
		self::$log_slow_or_big_query=isset($_mysql['log_slow_or_big_query'])?$_mysql['log_slow_or_big_query']:false;
	}
	
	//单件模式取得实例
	/*
	 * 参数和构造函数相同
	 */
	static function getInstance($user='',$pass='',$host='',$db='',$code='utf8',$force_flag=false) {
		$_user=$user==''?$GLOBALS['_mysql']['user']:$user;
		$_pass=$pass==''?$GLOBALS['_mysql']['pass']:$pass;
		$_host=$host==''?$GLOBALS['_mysql']['host']:$host;
		$_db=$db==''?$GLOBALS['_mysql']['db']:$db;
		$_code=$code==''?$GLOBALS['_mysql']['code']:$code;
		
		if(self::$mysql == NULL||$force_flag) {         //判断是否实例化了，没有则实例化
			if(self::$conn)
			{
				@mysql_close(self::$conn);
				self::$conn=NULL;
			}
			if(self::$mysql) self::$mysql=NULL;
			self::$mysql = new mysql($_user,$_pass,$_host,$_db,$_code);
		}
		return self::$mysql;
	}
	
	static function getInstance1($user='',$pass='',$host='',$db='',$code='utf8',$force_flag=false) {
		$_user=$user==''?$GLOBALS['_mysql']['user']:$user;
		$_pass=$pass==''?$GLOBALS['_mysql']['pass']:$pass;
		$_host=$host==''?$GLOBALS['_mysql']['host']:$host;
		$_db=$db==''?$GLOBALS['_mysql']['db']:$db;
		$_code=$code==''?$GLOBALS['_mysql']['code']:$code;
		
		return new mysql($_user,$_pass,$_host,$_db,$_code);
		$conn = @mysql_connect($_host,$_user, $_pass); //连接mysql
		if(!$conn) {
			echo 'mysql用户名或密码不对！';
			return false;
		}
		if(!mysql_select_db($db)){//选择数据库
			echo '数据库名不对';
			return false;
		}
		@mysql_query("SET NAMES utf8" ,$conn) or die(@mysql_error());
		return $conn;
	}
	
	//记录错误日志,静态方法
	/*
	 * $err_str,错误信息
	 * $type,错误类型
	 */
	public static function logError($err_str,$type='common')
	{
		if(self::$mysql == null) {//判断是否实例化了，没有则实例化,因为允许外部直接调用这个函数,所以要实现getInstance的功能
		   self::$mysql = new mysql($user,$pass,$host,$db,$code);
		}
		self::connect();
		$sql= 'insert into err_log set create_time="'.time().'",type="'.$type.'",data="'.str_replace('"','\"',$err_str).'",user_id="'.$_SESSION["role_id"].'"';
		echo $sql;
	}
	
	//检查是否已经断开
	/*
	 *
	 */
	public function checkConn()
	{
		if(!$this->query('SELECT 1')) self::connect();
	} 
	
	//连接数据库
	/*
	 *
	 */
	private static function connect()
	{
		if(!self::$conn)//判断mysql是否已经连接
		{
			if(defined('CLIENT_MULTI_RESULTS_NEEDED')){
				self::$conn = @mysql_connect(self::$host, self::$username, self::$password, 1, CLIENT_MULTI_RESULTS); //连接mysql
			}else{
				self::$conn = @mysql_connect(self::$host, self::$username, self::$password, 1); //连接mysql
			}
			if(! self::$conn) {
			   echo 'mysql用户名或密码不对！';
			   return false;
			}
			if(!@mysql_select_db(self::$database)){//选择数据库
			   echo '数据库名不对';
			   return false;
			}
			if(!self::$code) self::$code='utf8';
			@mysql_query("SET NAMES ".self::$code, self::$conn) or die(@mysql_error()); 
		}
		
	}
	
	

	//取得毫秒级的时间
	static function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	public function close()
	{
		@mysql_close(self::$conn);
	}
	//执行sql语句，如果为select则返回数组(只有一条记录则返回一维数组,否则返回二维数组)，否则返回影响行。
	/*
	 * $sql 为要执行的sql
	 */
	function query($sql,$force_two_array=false) {
		global $__class,$__method;
		self::connect();

	    if(empty($sql)) {
	       return false;
	    }else {
			if(self::$log_slow_or_big_query)//开启了慢查询,则记录开始时间
			{
				$start_time=self::microtime_float();
			}
			$result = @mysql_query($sql, self::$conn);

			if($err=@mysql_error()){
				if(strpos($err,'Lost connection to MySQL server')!==false)
				{
					@mysql_close(self::$conn);
					self::$conn=NULL;
					self::connect();
					$result = @mysql_query($sql, self::$conn);
					if($err=mysql_error())
					{
						echo '<h4>SQL ERROR:'.@mysql_error().'</h4><br />sql='.$sql.'<hr />';
					}
				}else echo '<h4>SQL ERROR:'.@mysql_error().'</h4><br />sql='.$sql.'<hr />';				
			}

			if(self::$log_slow_or_big_query)
			{
				$use_time=$start_time - self::microtime_float();
				if($use_time>SLOW_QUERY_TIME)//记录慢查询
				{
					self::logError($sql,'SLOW_QUERY');
				}
			}
			if(!$result)
			{				
				if(self::$dbg_mode && $err = mysql_error()){
					$err_str = 'sql语句有误:'.$err.'<br />SQL:<font color="red">'.$sql.'</font>'."<br/>\n";
					echo $err_str;
				}else{
					$err = @mysql_error();
					$err_str = 'sql语句有误:'.$err.'<br />SQL:<font color="red">'.$sql.'</font>'."<br/>\n";
					self::logError($err_str,'db');
				}
				return false;
			}else{
				if(!is_resource($result)) {//非select语句，返回影响行
					if(strtolower(substr($sql,0,6)) == 'insert'){//判断是否为insert语句,是则返回上次增加的自增id,否则返回影响行
						return @mysql_insert_id(self::$conn);
					}else if($result===false){
						return false;
					}else{
						return @mysql_affected_rows(self::$conn);
					}
				}else{
					$data = array();
					while($info = mysql_fetch_assoc($result))//取得结果集
					{
						$data[] = $info;
					}
					@mysql_free_result($result);//释放结果集

					$num_rows=count($data);
					if(self::$log_slow_or_big_query&&$num_rows>BIG_QUERY_NUM_ROWS)//返回记录数太大
					{
						self::logError($__class." - ".$__method.' - Num rows: '.$num_rows."\n".$sql,'BIG_QUERY');
					}

					if($num_rows==1&&!$force_two_array)
					{
						return $data[0];//查询数据以一维数组的形式返回
					}
					else if($num_rows==0)
					{
						return false;
					}
					else
					{
						return $data;   //查询数据以二维数组的形式返回
					}
				}
			}
		}
	}
	//关闭mysql
	function __destruct(){
		@mysql_close(self::$conn);
	}
	
	//取得连接
	public static function getConn()
	{
		return self::$conn;
	}
}
?>
