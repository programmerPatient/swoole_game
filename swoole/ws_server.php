<?php
require '../php/config/redis_config.php';
require '../php/databases/redis.php';
require './php/all_function.php';
require '../php/model/random_object.php';
require '../php/model/map.php';
require '../php/array_transfer_string.php';
/**
 * 建立redis链接
 */
$redis = RedisCache::getInstance(REDIS_HOST,REDIS_PORT);
/** * websocket服务器端程序 * *///
//创建websocket服务器对象，监听0.0.0.0:9502端口
$ws = new Swoole\WebSocket\Server("0.0.0.0", 9503);

$ws->set(array(
    'max_conn'   => 100,
));


//监听WebSocket连接打开事件
$ws->on('open', function ($ws, $request) use ($redis) {
    $select = $redis->get_string('select');
    if(!$select){
        //记录战士是否被控制
        $selects = null;
        for($i = 0; $i < 100; $i++){
            $selects[] = -1;
        }

        $redis->set_string('select',implode(',',$selects));
    }
    $select = explode(',',$redis->get_string('select'));
    /***随机分配未被控制的战士***/
    do{
        $number = rand(0,99);
    }while($select[$number] != -1);
    $select[$number] = $request->fd;
    $redis->set_string('select',implode(',',$select));
    foreach ($ws->connections as $key => $fd) {
        if($fd == $request->fd){
            $ws->push($fd,json_encode(['msg'=>'你控制的战士的编号为'.$number]));
        }else{
            $ws->push($fd,json_encode(['msg'=>'用户'.$request->fd.'进入房间，他将控制'.$number.'战士']));
        } 
    }
    $redis->set_hash('random_battle_'.$number.'编号战士','belongto_fd',$request->fd);
    
});

//监听WebSocket消息事件
$ws->on('message', function ($ws, $frame) use ($redis) {
	$data = json_decode($frame->data);
    $select = explode(',',$redis->get_string('select'));
    $soldier_number = array_search($frame->fd,$select);
    //获取地图数据
    $map['height'] = $redis->get_hash('map','height');
    $map['width'] = $redis->get_hash('map','width');
    $map['point_length'] = $redis->get_hash('map','point_length');
    $map['coordinate'] = emplodex(',',$redis->get_hash('map','coordinate'),'|');

    //实例化地图
    $map = new Map($map['width'],$map['height'],$map['point_length'],$map['coordinate']);

    //所有战士数据
    $random_battle = null;
    for($i = 0; $i < 100; $i++){
        $das['x'] = $redis->get_hash('random_battle_'.$i.'编号战士','x');
        $das['y'] = $redis->get_hash('random_battle_'.$i.'编号战士','y');
        $das['blood'] = $redis->get_hash('random_battle_'.$i.'编号战士','blood');
        $das['attack'] = $redis->get_hash('random_battle_'.$i.'编号战士','attack');
        $das['name'] = $redis->get_hash('random_battle_'.$i.'编号战士','name');
        $das['defense'] = $redis->get_hash('random_battle_'.$i.'编号战士','defense');
        $das['is_death'] = $redis->get_hash('random_battle_'.$i.'编号战士','is_death');
        $das['kill_num'] = $redis->get_hash('random_battle_'.$i.'编号战士','kill_num');
        $random_battle[] = $das;
    }

    $num = redis_soldier_num($redis);
    $res = null;
    if($num <= 1){
        for($i = 0; $i < count($random_battle); $i++){
            $position = calculation_ranking($random_battle,$random_battle[$i],$i);
            $redis->set_sort('ranking',$position,$random_battle[$i]['name']);
        }
        $daa = $redis->get_all_data('ranking');
        for($i = 0; $i < count((array)$daa); $i++){
            $h['name'] = $daa[$i];
            $h['kill_num'] = $redis->get_hash('random_battle_'.$daa[$i],'kill_num');
            $ranking[] = $h;
        }
        $res['ranking'] = $ranking;
        $res['msg'] = '本次pk已经结束，欢迎下次光临！';
    }else{
        //获取当前用户控制的战士数据
        $da = $random_battle[$soldier_number];
        $da = new Random_object($da['x'],$da['y'],$da['name'],$da['attack'],$da['defense'],$da['blood'],$da['is_death'],$da['kill_num']);
        if($da->is_death == 1){
            $ws->push($frame->fd,json_encode(['msg'=>'抱歉您已经被击杀了，请观战！']));
        }else if($da->is_death == 0){

            //pk对战
            $enemy = pk_object($da,$redis,$map,$data->deriction);//对手对象
            if($enemy !== false){
                //获取pk战士的数据
                $en = $random_battle[$enemy];
                $en = new Random_object($en['x'],$en['y'],$en['name'],$en['attack'],$en['defense'],$en['blood'],$en['is_death'],$en['kill_num']);
                $enemy_init_blood = $en->blood;
                //pk操作
                $result = $da->whether_kill($en,$map);
                if($result){//如果对手被击杀
                    $description = $da->name.'击杀了'.$en->name;
                    $map->release_point($en->x/$map->point_length,$en->y/$map->point_length);
                    $en->update_is_death(1);
                    $en->update_blood(0);
                    $res['map'] = $map;
                    $random_battle[$enemy] = (array)$en;
                    $res['random_battle'] = $random_battle;
                    //通知被击杀用户
                    $tofd = $redis->get_hash('random_battle_'.$enemy.'编号战士','belongto_fd');
                    if(!empty($tofd))
                        $ws->push($tofd,json_encode(['msg'=>'您被'.$da->name.'击杀了！']));
                }else{
                    $description = $da->name.'对'.$en->name.'造成'.$da->attack.'点伤害';
                }
                $res['pk_recording'] = $description;
                
                redis_cache_soldier($redis,$en,'random_battle_'.$enemy.'编号战士');
                $redis->set_list('battle_record',$description);
                redis_cache_soldier($redis,$da,'random_battle_'.$soldier_number.'编号战士');
            }
            if($da->is_death == 1){
                foreach ($ws->connections as $key => $fd) {
                    if($fd == $frame->fd){
                        $ws->push($fd,json_encode(['msg'=>'您控制的编号为'.$soldier_number.'的战士被击杀']));
                    }else{
                        $ws->push($fd,json_encode(['msg'=>'用户'.$frame->fd.'控制的编号为'.$soldier_number.'战士被击杀']));
                    } 
                }
            }


            $p = null;
            switch($data->deriction){
                case 37://左
                    $p = move($da,$map,3);
                    break;
                case 39://右
                    $p = move($da,$map,4);
                    break;
                case 38://上
                    $p = move($da,$map,1);
                    break;
                case 40://下
                    $p = move($da,$map,2);
                    break;
            }
            
            if($p == 1){
                redis_cache_soldier($redis,$da,'random_battle_'.$soldier_number.'编号战士');
                $res['map'] = $map;
                $random_battle[$soldier_number] = $da;
                $res['random_battle'] = $random_battle;
            }

            
            

            redis_cache_map($redis,$map,'map');//缓存地图信息

           
        }  
    }

    foreach ($ws->connections as $key => $fd) {
        $ws->push($fd,json_encode($res));
    }
    
    
});


//监听WebSocket连接关闭事件
$ws->on('close', function ($ws, $fd) use ($redis) {
    $select = explode(',',$redis->get_string('select'));
    $soldier_number = array_search($fd,$select);
    $select[$soldier_number] = -1;
    $redis->set_string('select',implode(',',$select));
    foreach ($ws->connections as $key => $fds) {
        $ws->push($fds,json_encode(['msg'=>'用户'.$fd.'退出战局']));
    }
});

$ws->start();







