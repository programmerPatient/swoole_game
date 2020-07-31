<?php
/*redis缓存或修改地图数据*/
function redis_cache_map($redis, $map, $key){
	$redis->set_hash($key,'height',$map->height);
	$redis->set_hash($key,'width',$map->width);
	$redis->set_hash($key,'point_length',$map->point_length);
	$redis->set_hash($key,'coordinate',implodex(',',$map->coordinate,'|'));
}

/*缓存或修改战士数据*/
function redis_cache_soldier($redis,$data, $key){
	$redis->set_hash($key, 'name',$data->name);
	$redis->set_hash($key, 'x',$data->x);
	$redis->set_hash($key, 'y',$data->y);
	$redis->set_hash($key, 'attack',$data->attack);
	$redis->set_hash($key, 'defense',$data->defense);
	$redis->set_hash($key, 'blood',$data->blood);
	$redis->set_hash($key, 'is_death',$data->is_death);
	$redis->set_hash($key, 'kill_num',$data->kill_num);
}

/*查询redis数据库中战士的存货数量*/
function redis_soldier_num($redis){
	// return $redis->get_hash('random_battle_0编号战士','name');
	$num = 0;
	for($i = 0; $i < 10; $i++){
		if($redis->get_hash('random_battle_'.$i.'编号战士','name') && $redis->get_hash('random_battle_'.$i.'编号战士','is_death') == 0) $num++;
	}
	return $num;
}


/*实例化地图*/
function instance_map($map){
	return  new Map($map['width'],$map['height'],$map['point_length'],$map['coordinate']);
}

/*实例化战士*/
function instance_soldier($battle,&$map){
	$random_battle = null;
	for($i = 0; $i < count($battle); $i++){
		$soldier_ids[] = $battle[$i]['id'];
		if($battle[$i]['is_death'] == 0) $map->coordinate[$battle[$i]['x']/$map->point_length][$battle[$i]['y']/$map->point_length] = $i;
		$name = $battle[$i]['name'];
		$rand = new Random_object($battle[$i]['x'],$battle[$i]['y'],$battle[$i]['name'],$battle[$i]['attack'],$battle[$i]['defense'],$battle[$i]['blood'],$battle[$i]['is_death'],$battle[$i]['kill_num']);
		$random_battle[] = $rand;
	}

	return $random_battle;
}

/*初始化数据*/
function init_data($redis){
	$map = new Map(500,500,20);
	$random_battle = null;
	$h = null;
	for($i = 0; $i < 10; $i++){
		do {

			$x = rand(0,($map->width/$map->point_length)-1);
			$y = rand(0,($map->height/$map->point_length)-1);
		}while($map->coordinate[$x][$y] != -1);
		$map->occupied_point($x,$y,$i);
		$name = $i.'编号战士';//命名
		$rand = new Random_object($x*$map->point_length,$y*$map->point_length,$name,rand(3,5),rand(0,2),rand(10,20));
		$random_battle[] = $rand;

		/*缓存战士数据*/
		redis_cache_soldier($redis,$rand,'random_battle_'.$name);
	}
	
	$arr_map = (array)$map;
	$arr_map['coordinate'] = implodex(',',$arr_map['coordinate'],'|');
	/*缓存地图数据*/
	redis_cache_map($redis,$map,'map');

	$data['map'] = $map;
	$data['random_battle'] = $random_battle;
	return $data;
}


/*计算排名,random_battle为所有参与排名的战士数组，$value为需要当前获得排名的战士*/
function calculation_ranking($random_battle,$value,$key){
	$ranking = 1;
	for($i = 0; $i < count((array)$random_battle); $i++){
		//排名规则：当击杀数不相等时，以击杀数为标准，当击杀数相同时以谁或则为标准，让同时击杀数相同和同时活着时，以谁的血量高为标准,当击杀数、是否活着、血量都一样时以攻击力为标准,当前面的都相同时以防御力为标准，最后以编号先后为标准
		if($i == $key) continue;
		if($value->kill_num > $random_battle[$i]->kill_num) continue;
		else if($value->kill_num < $random_battle[$i]->kill_num) $ranking++;
		else if($value->is_death > $random_battle[$i]->is_death) $ranking++;
		else if($value->is_death < $random_battle[$i]->is_death) continue;
		else if($value->blood < $random_battle[$i]->blood) $ranking++;
		else if($valuie->blood > $random_battle[$i]->blood) continue;
		else if($value->attack < $random_battle[$i]->attack) $ranking++;
		else if($value->attack > $random_battle[$i]->attack) continue;
		else if($value->defense < $random_battle[$i]->defense) $ranking++;
		else if($value->defense > $random_battle[$i]->defense) continue;
		else if($key > $i) $ranking++; 
		else continue;
	}
	return $ranking;
}
