<?php

class Random_object {

	const MAX_BLOOD = 20;//最大血量
	const MIN_BLOOD = 10;//最小血量
	const MAX_ATTACK = 5;//最大攻击
	const MIN_ATTACK = 3;//最小攻击
	const MAX_DEFENSE = 2;//最大防御
	const MIN_DEFENSE = 0;//最小防御
	const MIN_RANDOM_MOVE = 30;//规定最少剩余数，当战士剩余人数低于该值得机会改变移动原则，寻找最近的对手进行靠近移动
	
	public $x;//坐标
	public $y;//坐标
	public $name;//名称
	public $attack;//攻击
	public $defense;//防御
	public $blood;//血量
	public $is_death;//是否死亡，0为未死亡，1为死亡
	public $kill_num;//击杀数

	public function __construct($x, $y, $name, $attack = self::MIN_ATTACK, $defense = self::MIN_DEFENSE, $blood = self::MAX_BLOOD, $is_death = 0, $kill_num = 0){
		$this->x = $x;
		$this->y = $y;
		$this->name = $name;
		$this->attack = $attack;
		$this->defense = $defense;
		$this->blood = $blood;
		$this->is_death = $is_death;
		$this->kill_num = $kill_num;
	}

	public function update_x($value){
		$this->x = $value;
	}
	public function update_y($value){
		$this->y = $value;
	}

	public function update_attack($value){
		$this->attack = $value;
	}

	public function update_defense($value){
		$this->defense = $value;
	}


	public function update_blood($value){
		$this->blood = $value;
	}


	public function update_is_death($value){
		$this->is_death = $value;
	}

	public function update_kill_num($value){
		$this->kill_num = $value;
	}

	public function whether_kill(&$b) {//与b进行pk
		//判断b是否掉血或击杀
		$initial_blood = $b->blood;//击杀对象的初始血量值
		if(($h = $this->attack - $b->defense) > 0){
			$b->blood = $b->blood - $h;
		}

		if($b->blood <= 0) {//b被击杀
			$this->blood += $initial_blood;
			$this->blood = $this->blood > self::MAX_BLOOD? self::MAX_BLOOD:$this->blood;
			$this->kill_num++;
			return true;             
		}
		return false;
	}
}

