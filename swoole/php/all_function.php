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
function pk_object($new_object,$redis,$map,$deriction){
	
	$c = -1;
	switch($deriction){
		case 38:
			if($new_object->y >= 0 && ($c = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length]) != -1 ){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
		case 40:
			if($new_object->y < $map->height && ($c = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length]) != -1){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
		case 37:
			if($new_object->x >= 0 && ($c = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length]) != -1){
				$re = $redis->get_hash('random_battle_'.$c.'编号战士','is_death');
				if($re ==0 )
					return $c;
			}
			break;
		case 39:
			if($new_object->x < $map->width && ($c = $map->coordinate[$new_object->x/$map->point_length][$new_object->y/$map->point_length]) != -1){
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