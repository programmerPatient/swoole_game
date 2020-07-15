<?php
/**
 * redis操作类
 * 说明，任何为false的串，在redis里都是空字符串
 * 只有在key不存在时，才会返回false
 * 这点可用于防止缓存穿透
 */
class RedisCache {

	private static $_instance;
	public $handle;

	private $host;
	private $port;

	private function __construct($host,$port){
		$this->$host = $host;
		$this->port = $port;
		$data['host'] = $this->host;
		$data['port'] = $this->port;
		$this->connect($data);
	}

	private function __clone(){}

	public static function getInstance($host,$port){
		if(is_null(self::$_instance)){
			self::$_instance = new self($host,$port);
		}
		return self::$_instance;
	}


	public function connect($config = array()){
		$host = isset($config['host']) ? $config['host'] : 'localhost';
		$port = isset($config['port']) ? $config['port'] : 6379;
		$this->handle = new Redis();
		$this->handle->connect($host,$port) or die('ssssss');
	}


	/**
	 * 字符串操作
	 */
	/*向redis写入字符串*/
	public function  set_string($key,$value){
		return $this->handle->set($key,$value);
	}

	/*根据键获取数据*/
	public function get_string($key){
		return $this->handle->get($key);
	}



	/**
	 * list（列表操作）
	 */
	/*向redis写入列表数据*/
	public function set_list($key,$value){
		return $this->handle->lpush($key,$value);
	}

	/*获取list数据*/
	public function get_list($key,$start,$stop){
		return $this->handle->lrange($key,$start,$stop);
	}



	/**
	 * （hash）哈希操作
	 */
	/*写入hash数据*/
	public function set_hash($name,$key,$value){
		$this->handle->hset($name,$key,$value);

	}
	/*获取hash数据*/
	public function get_hash($name,$key){
		return $this->handle->hget($name,$key);
	}



	/**
	 * 有序集合（sort set）
	 */
	/*添加元素*/
	public function set_sort($name,$sorce,$value){
		$this->handle->zadd($name,$sorce,$value);
	}
	/*获取集合所有元素*/
	public function get_all_data($name){
		return $this->handle->zrange($name,0,-1);
	}
	/*返回元素的sorce值*/
	public function get_sort_score($name,$value){
		return $this->handle->zscore($name,$value);
	}



	/*
	*
	*删除
	*/
	public function delete($key){
		$this->handle->del($key);
	}



	/**
	*清空所有数据
	*/
	public function flush_all(){
		if($this->handle){
			$this->handle->flushall();
		}
	}






}