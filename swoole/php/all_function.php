<?php

/*随机点移动*/
function move(&$new_object,&$map,$deriction){
	$p = 0;//标记是否可以在改方向上移动
	switch($deriction){
		case 1://向上移动
			if($new_object->y - $map->point_length >= 0 && $map->coordinate[$new_object->x/$map->point_length][($new_object->y - $map->point_length)/$map->point_length] == -1){
				$p = 1;
				$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
				$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
				$new_object->update_y($new_object->y - $map->point_length);
				$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
			}
			break;
		case 2://向下
			if($new_object->y + $map->point_length <= ($map->height-$map->point_length) && $map->coordinate[$new_object->x/$map->point_length][($new_object->y + $map->point_length)/$map->point_length] == -1){
				$p = 1;
				$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
				$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
				$new_object->update_y($new_object->y + $map->point_length);
				$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
			}
			break;
		case 3://向左
			if($new_object->x - $map->point_length >= 0 && $map->coordinate[($new_object->x - $map->point_length)/$map->point_length][$new_object->y /$map->point_length] == -1){
				$p = 1;
				$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
				$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
				$new_object->update_x($new_object->x - $map->point_length);
				$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
			}
			break;
		case 4://向右
			if($new_object->x + $map->point_length <= ($map->width-$map->point_length) && $map->coordinate[($new_object->x + $map->point_length)/$map->point_length][$new_object->y/$map->point_length] == -1){
				$p = 1;
				$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
				$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
				$new_object->update_x($new_object->x + $map->point_length);
				$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
			}
			break;
	}

	return $p;
}


/*寻找与a进行pk的对手,找寻规则为先上后下再左最后右*/
function pk_object($new_object,$redis,$map,$deriction = -1){
	
	$c = -1;
	if($deriction == -1){
		$data =array(38,40,37,39);
		$deriction = $data[rand(0,3)];
	}
	switch($deriction){
		case 38:
			if(($new_object->y - $map->point_length) >= 0 && ($c = $map->coordinate[$new_object->x/$map->point_length][($new_object->y - $map->point_length)/$map->point_length]) != -1 ){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
		case 40:
			if(($new_object->y + $map->point_length) < $map->height && ($c = $map->coordinate[$new_object->x/$map->point_length][($new_object->y + $map->point_length)/$map->point_length]) != -1){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
		case 37:
			if(($new_object->x - $map->point_length) >= 0 && ($c = $map->coordinate[($new_object->x - $map->point_length)/$map->point_length][$new_object->y/$map->point_length]) != -1){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
		case 39:
			if(($new_object->x + $map->point_length) < $map->width && ($c = $map->coordinate[($new_object->x + $map->point_length)/$map->point_length][$new_object->y/$map->point_length]) != -1){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
	}
	return false;

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

/*redis缓存或修改地图数据*/
function redis_cache_map($redis, $map, $key){
	$redis->set_hash($key,'height',$map->height);
	$redis->set_hash($key,'width',$map->width);
	$redis->set_hash($key,'point_length',$map->point_length);
	$redis->set_hash($key,'coordinate',implodex(',',$map->coordinate,'|'));
}


/*查询redis数据库中战士的存货数量*/
function redis_soldier_num($redis){
	// return $redis->get_hash('random_battle_0编号战士','name');
	$num = 0;
	for($i = 0; $i < 100; $i++){
		if($redis->get_hash('random_battle_'.$i.'编号战士','name') && $redis->get_hash('random_battle_'.$i.'编号战士','is_death') == 0) $num++;
	}
	return $num;
}

/*计算排名,random_battle为所有参与排名的战士数组，$value为需要当前获得排名的战士*/
function calculation_ranking($random_battle,$value,$key){
	$ranking = 1;
	for($i = 0; $i < count((array)$random_battle); $i++){
		//排名规则：当击杀数不相等时，以击杀数为标准，当击杀数相同时以谁或则为标准，让同时击杀数相同和同时活着时，以谁的血量高为标准,当击杀数、是否活着、血量都一样时以攻击力为标准,当前面的都相同时以防御力为标准，最后以编号先后为标准
		if($i == $key) continue;
		if($value['kill_num'] > $random_battle[$i]['kill_num']) continue;
		else if($value['kill_num'] < $random_battle[$i]['kill_num']) $ranking++;
		else if($value['is_death'] > $random_battle[$i]['is_death']) $ranking++;
		else if($value['is_death'] < $random_battle[$i]['is_death']) continue;
		else if($value['blood'] < $random_battle[$i]['blood']) $ranking++;
		else if($value['blood'] > $random_battle[$i]['blood']) continue;
		else if($value['attack'] < $random_battle[$i]['attack']) $ranking++;
		else if($value['attack'] > $random_battle[$i]['attack']) continue;
		else if($value['defense'] < $random_battle[$i]['defense']) $ranking++;
		else if($value['defense'] > $random_battle[$i]['defense']) continue;
		else if($key > $i) $ranking++; 
		else continue;
	}
	return $ranking;
}

/*寻找距离最近的对手*/
function find_nearly_enemy($new_object,$all_object,$map,$k){
	$near_object = NULL;
	$min_length = pow($map->width,2) + pow($map->height,2);
	for($i = 0; $i < count((array)$all_object); $i++){
		if($k == $i || ((array)$all_object[$i])['is_death'] == 1) continue;//根据key判断是否是它本身
		if($min_length > (pow(((array)$all_object[$i])['x']-$new_object->x,2) + pow(((array)$all_object[$i])['y'] - $new_object->y,2))){
			$min_length = pow(((array)$all_object[$i])['x']-$new_object->x,2) + pow(((array)$all_object[$i])['y'] - $new_object->y,2);
			$nearly_object = $all_object[$i];
		}
	}


	return $nearly_object;
}



/*/随机寻找与a进行pk的对手,找寻规则为先上后下再左最后右*/
function random_pk_object($new_object,$all_object,$map){
	
	$c = -1;
	if(($new_object->y - $map->point_length) >= 0 && ($c = $map->coordinate[$new_object->x/$map->point_length][($new_object->y - $map->point_length)/$map->point_length]) != -1 ){
		if($all_object[$c]['is_death'] ==0 )
			return $c;
	}

	if(($new_object->y + $map->point_length) <= $map->height && ($c = $map->coordinate[$new_object->x/$map->point_length][($new_object->y + $map->point_length)/$map->point_length]) != -1){
		if($all_object[$c]['is_death'] ==0 )
			return $c;
	}

	if(($new_object->x - $map->point_length) >= 0 && ($c = $map->coordinate[($new_object->x - $map->point_length)/$map->point_length][$new_object->y/$map->point_length]) != -1){
		if($all_object[$c]['is_death'] ==0)
			return $c;
	}

	if(($new_object->x + $map->point_length) <= $map->width && ($c = $map->coordinate[($new_object->x + $map->point_length)/$map->point_length][$new_object->y/$map->point_length]) != -1){
		if($all_object[$c]['is_death'] ==0)
			return $c;
	}
	return false;

}


/*计算存活数*/
function survive_num($all_object){
	$num = 0;
	for($h = 0;$h < count($all_object); $h++){
		// var_dump($all_object[$h]);
		if(((array)$all_object[$h])['is_death'] != 1) $num++;
	}
	return $num;
}

/*随机移动*/
function random_move(&$new_object,&$map,$all_object,$k){
	$num = survive_num($all_object);
	$direction = 0;
	$enemy = find_nearly_enemy($new_object,$all_object,$map,$k);//寻找最近距离的对手
	$h = rand(0,1);
	if($enemy['x'] == $new_object->x) $h = 1;
	if($enemy['y'] == $new_object->y) $h = 0;
	//随机选择x还是y方向上的移动
	switch ($h) {
		case 0://x方向上的移动
			if($enemy['x'] > ($new_object->x - $map->point_length)) $direction = 4;
			else if($enemy['x'] < ($new_object->x + $map->point_length)) $direction = 3;
			break;
		
		case 1://y方向上的移动
			if($enemy['y'] > ($new_object->y - $map->point_length)) $direction = 2;
			else if($enemy['y'] < ($new_object->y + $map->point_length)) $direction = 1;
			break;
	}
	$move_num = 0;//判断是否四个方向都尝试过移动
	do{
		$p = 0;//标记是否可以在改方向上移动
		switch($direction){
			case 1://向上移动
				if($new_object->y - $map->point_length >= 0 && $map->coordinate[$new_object->x/$map->point_length][($new_object->y - $map->point_length)/$map->point_length] == -1){
					$p = 1;
					$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
					$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
					$new_object->update_y($new_object->y - $map->point_length);
					$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
				}
				break;
			case 2://向下
				if($new_object->y + $map->point_length <= ($map->height-$map->point_length) && $map->coordinate[$new_object->x/$map->point_length][($new_object->y + $map->point_length)/$map->point_length] == -1){
					$p = 1;
					$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
					$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
					// $h = $new_object->y;
					$new_object->update_y($new_object->y + $map->point_length);
					$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
				}
				break;
			case 3://向左
				if($new_object->x - $map->point_length >= 0 && $map->coordinate[($new_object->x - $map->point_length)/$map->point_length][$new_object->y /$map->point_length] == -1){
					$p = 1;
					$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
					$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
					$new_object->update_x($new_object->x - $map->point_length);
					$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
				}
				break;
			case 4://向右
				if($new_object->x + $map->point_length <= ($map->width-$map->point_length) && $map->coordinate[($new_object->x + $map->point_length)/$map->point_length][$new_object->y/$map->point_length] == -1){
					$p = 1;
					$key = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length];
					$map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
					$new_object->update_x($new_object->x + $map->point_length);
					$map->occupied_point($new_object->x/$map->point_length,$new_object->y/$map->point_length,$key);
				}
				break;
		}
		$move_num ++;
		$direction = rand(1,4);//随机四个方向
	}while($p != 1 && $move_num < 4);

	return $p;
}