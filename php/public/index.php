<?php
header('Content-Type:text/json;charset=utf-8');
require '../model/map.php';
require '../model/random_object.php';
require '../config/redis_config.php';
require '../databases/redis.php';
require '../array_transfer_string.php';
require '../all_function.php';
/**
 * 建立redis链接
 */
$redis = RedisCache::getInstance(REDIS_HOST,REDIS_PORT);

$num = redis_soldier_num($redis) ;//获取redis中存活战士的数量

if($redis->get_hash('map','width')) $mp = 1;


$random_battle = null;
$map = null;

if($num > 0 && $mp > 0){

	// $battle = null;
	// for($i = 0; $i < 100; $i++){
	// 	$da['x'] = $redis->get_hash('random_battle_'.$i.'编号战士','x');
	// 	$da['y'] = $redis->get_hash('random_battle_'.$i.'编号战士','y');
	// 	$da['blood'] = $redis->get_hash('random_battle_'.$i.'编号战士','blood');
	// 	$da['attack'] = $redis->get_hash('random_battle_'.$i.'编号战士','attack');
	// 	$da['name'] = $redis->get_hash('random_battle_'.$i.'编号战士','name');
	// 	$da['defense'] = $redis->get_hash('random_battle_'.$i.'编号战士','defense');
	// 	$da['is_death'] = $redis->get_hash('random_battle_'.$i.'编号战士','is_death');
	// 	$da['kill_num'] = $redis->get_hash('random_battle_'.$i.'编号战士','kill_num');
	// 	$battle[] = $da;
	// }

	// $map['width'] = $redis->get_hash('map','width');
	// $map['height'] = $redis->get_hash('map','height');
	// $map['point_length'] = $redis->get_hash('map','point_length');
	// $map['coordinate'] = emplodex(',',$redis->get_hash('map','coordinate'),'|');

	// $map = instance_map($map);
	// $random_battle = instance_soldier($battle,$map);

}else{
    //记录战士是否被控制
    $selects = null;
    for($i = 0; $i < 100; $i++){
        $selects[] = -1;
    }

    $redis->set_string('select',implode(',',$selects));
	$num = 100;
	$data = init_data($redis);
	$map = $data['map'];
	$random_battle = $data['random_battle'];
}
$ranking = null;
/*剩余一人时获取排名*/
if($num <= 1){
	$daa = $redis->get_all_data('ranking');
	for($i = 0; $i < count((array)$daa); $i++){
		$h['name'] = $daa[$i];
		$h['kill_num'] = $redis->get_hash('random_battle_'.$daa[$i],'kill_num');
		$ranking[] = $h;
	}
}

// $data['random_battle'] = (array)$random_battle;
// $data['map'] = $map;
$data['num'] = $num;
$data['ranking'] = $ranking;


echo json_encode($data);
?>



