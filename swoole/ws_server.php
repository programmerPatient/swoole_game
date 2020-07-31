<?php
require '../php/config/redis_config.php';
require '../php/databases/redis.php';
require './php/all_function.php';
// require './php/random_function.php';
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
    $select = explode(',',$redis->get_string('select'));
    /***随机分配未被控制的战士***/
    do{
        $number = rand(0,99);
    }while($select[$number] != -1 || $random_battle[$number]['is_death'] == 1);
    $select[$number] = $request->fd;
    $redis->set_string('select',implode(',',$select));
    // var_dump(explode(',',$redis->get_string('select')));
    $das['x'] = $redis->get_hash('random_battle_'.$number.'编号战士','x');
    $das['y'] = $redis->get_hash('random_battle_'.$number.'编号战士','y');
    $das['blood'] = $redis->get_hash('random_battle_'.$number.'编号战士','blood');
    $das['attack'] = $redis->get_hash('random_battle_'.$number.'编号战士','attack');
    $das['name'] = $redis->get_hash('random_battle_'.$number.'编号战士','name');
    $das['defense'] = $redis->get_hash('random_battle_'.$number.'编号战士','defense');
    $das['is_death'] = $redis->get_hash('random_battle_'.$number.'编号战士','is_death');
    $das['kill_num'] = $redis->get_hash('random_battle_'.$number.'编号战士','kill_num');
    foreach ($ws->connections as $key => $fd) {
        if($fd == $request->fd){
            $ws->push($fd,json_encode(['msg'=>'你控制的战士的编号为'.$number,'myself_info'=>$das,'id'=>$number,'map'=>$map,'random_battle'=>$random_battle]));
        }else{
            $ws->push($fd,json_encode(['msg'=>'用户'.$request->fd.'进入房间，他将控制'.$number.'战士']));
        } 
    }
    $redis->set_hash('random_battle_'.$number.'编号战士','belongto_fd',$request->fd);
    
});

//监听WebSocket消息事件
$ws->on('message', function ($ws, $frame) use ($redis) {

    $select = explode(',',$redis->get_string('select'));

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
        $ranking = null;
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
            $ws->push($frame->fd,json_encode(['msg'=>'抱歉您已经被击杀了，请观战！','id'=>-1]));
        }else if($da->is_death == 0){
            //pk对战
            $enemy = pk_object($da,$redis,$map,$data->deriction);//对手对象
            if($enemy !== false){
                //获取pk战士的数据
                $en = $random_battle[$enemy];
                $en = new Random_object($en['x'],$en['y'],$en['name'],$en['attack'],$en['defense'],$en['blood'],$en['is_death'],$en['kill_num']);
                $enemy_init_blood = $en->blood;
                //pk操作
                $result = $da->whether_kill($en);
                if($result){//如果对手被击杀
                    $description = $da->name.'击杀了'.$en->name;
                    $map->release_point($en->x/$map->point_length,$en->y/$map->point_length);
                    $en->update_is_death(1);
                    $en->update_blood(0);
                    // $res['map'] = $map;
                    $random_battle[$enemy] = (array)$en;
                    $random_battle[$soldier_number] = (array)$da;
                    // $res['random_battle'] = $random_battle;
                    //通知被击杀用户
                    $tofd = $redis->get_hash('random_battle_'.$enemy.'编号战士','belongto_fd');
                    if(!empty($tofd))
                        $ws->push($tofd,json_encode(['msg'=>'您被'.$da->name.'击杀了！','myself_info'=>$en]));

                }else{
                    /**
                     * 对手反击
                     * @var [type]
                     */
                    
                    $counter_res = $en->whether_kill($da);
                    if($counter_res){
                        //如果我被反杀
                        
                        $description = $da->name.'被'.$en->name.'反杀';
                        $map->release_point($da->x/$map->point_length,$da->y/$map->point_length);
                        $da->update_is_death(1);
                        $da->update_blood(0);
                        // $res['map'] = $map;
                        $random_battle[$soldier_number] = (array)$da;
                        $random_battle[$enemy] = (array)$en;
                        $ws->push($frame->fd,json_encode(['msg'=>'您被'.$en->name.'反杀了！','myself_info'=>$da]));
                        // $res['random_battle'] = $random_battle;

                    }else{
                        $description = $en->name.'对'.$da->name.'实际造成了'.(($en->attack-$da->defense) > 0? ($en->attack-$da->defense):0).'点伤害';
                    }
                    $res['pk_recording'][] = $description;

                    // $tofd = $redis->get_hash('random_battle_'.$enemy.'编号战士','belongto_fd');
                    $description = $da->name.'对'.$en->name.'实际造成了'.(($en->attack-$da->defense) > 0? ($en->attack-$da->defense) : 0).'点伤害';
                    $res['pk_recording'][] = $description;
                    // $ws->push($tofd,json_encode(['msg'=>$da->name.'对您造成的实际伤害为'.(($en->attack-$da->defense) > 0? ($en->attack-$da->defense) : 0),'myself_info'=>$en]));
                }
                
                
                redis_cache_soldier($redis,$en,'random_battle_'.$enemy.'编号战士');
                // $redis->set_list('battle_record',$description);
                redis_cache_soldier($redis,$da,'random_battle_'.$soldier_number.'编号战士');
                $random_battle[$soldier_number] = (array)$da;
                $random_battle[$enemy] = (array)$en;
                $res['rival_info'] = $en;
            }else{
                $res['rival_info'] = null;
            }
            for($j=0; $j< count($random_battle);$j++){
                if($select[$j] == -1 && $j != $soldier_number){
                    
                    if($random_battle[$j]['is_death'] == 0){

                        $new_object = new Random_object($random_battle[$j]['x'],$random_battle[$j]['y'],$random_battle[$j]['name'],$random_battle[$j]['attack'],$random_battle[$j]['defense'],$random_battle[$j]['blood'],$random_battle[$j]['is_death'],$random_battle[$j]['kill_num']);
                        //pk对战
                        $enemys = pk_object($new_object,$redis,$map);//对手对象
                        
                        if($enemys !== false){

                            //获取pk战士的数据
                             $ens = $random_battle[$enemys];
                             $ens = new Random_object($ens['x'],$ens['y'],$ens['name'],$ens['attack'],$ens['defense'],$ens['blood'],$ens['is_death'],$ens['kill_num']);
                             //pk操作
                             $enemy_init_blood = $ens->blood;
                             $result = $new_object->whether_kill($ens);
                             if($result){//如果对手被击杀
                                $description = $new_object->name.'击杀了'.$ens->name;
                                $map->release_point($ens->x/$map->point_length,$ens->y/$map->point_length);
                                $ens->update_is_death(1);
                                $ens->update_blood(0);
                                $random_battle[$enemys] = (array)$ens;
                                $random_battle[$j] = (array)$new_object;
                                $res['pk_recording'][] = $description;
                             }else{
                                //对手进行反击
                                $counter_res = $ens->whether_kill($new_object);
                                if($counter_res){
                                    //如果我被反杀
                                    $description = $new_object->name.'被'.$ens->name.'反杀';
                                    $map->release_point($new_object->x/$map->point_length,$new_object->y/$map->point_length);
                                    $new_object->update_is_death(1);
                                    $new_object->update_blood(0);   
                                }else{
                                    $description = $ens->name.'对'.$new_object->name.'实际造成了'.(($ens->attack-$new_object->defense) > 0? ($ens->attack-$new_object->defense):0).'点伤害';
                                }
                                $random_battle[$j] = (array)$new_object;
                                $random_battle[$enemys] = (array)$ens;
                                $res['pk_recording'][] = $description;
                             }

                             redis_cache_soldier($redis,$ens,'random_battle_'.$enemys.'编号战士');
                             redis_cache_soldier($redis,$new_object,'random_battle_'.$j.'编号战士');

                        }
         
                    }
                }
            }

            $num = redis_soldier_num($redis);
            if($num <= 1){
                $ranking = null;
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
                /**
                 * 移动，未被控制的点随机移动
                 */
                for($j = 0; $j < count($random_battle); $j++){
                    // var_dump($j == $soldier_number);
                    if($j == $soldier_number){
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
                        //移动后的操作
                        if($p == 1){
                            /*缓存或修改战士数据*/
                            redis_cache_soldier($redis,$da,'random_battle_'.$soldier_number.'编号战士');
                            // $res['map'] = $map;
                            $random_battle[$soldier_number] = (array)$da;
                            // $res['random_battle'] = $random_battle;
                        } 
                    }else if($select[$j] == -1){

                        if($random_battle[$j]['is_death'] == 0){

                            /**
                             * pk
                             */
                            $new_object = new Random_object($random_battle[$j]['x'],$random_battle[$j]['y'],$random_battle[$j]['name'],$random_battle[$j]['attack'],$random_battle[$j]['defense'],$random_battle[$j]['blood'],$random_battle[$j]['is_death'],$random_battle[$j]['kill_num']);
                            //查看周围是否有敌人
                            $enemys = random_pk_object($new_object,$random_battle,$map);//对手对象

                            //不存在敌人则移动
                            if($enemys == false){

                                random_move($new_object,$map,$random_battle,$j);



                                $random_battle[$j] = (array)$new_object;
                            }

                        }
                        
                        
                        
                    }
                    //移动后的操作
                    /*缓存或修改战士数据*/
                    
                    redis_cache_soldier($redis,(object)$random_battle[$j],'random_battle_'.$j.'编号战士');
                
                }
            }
            
            

            redis_cache_map($redis,$map,'map');//缓存地图信息

           
        }  
    }
    $res['map'] = $map;
    $res['random_battle'] = $random_battle;

    foreach ($ws->connections as $key => $fd) {
        if($fd == $frame->fd){
            $res['myself_info'] = $random_battle[$soldier_number];
            $res['id'] = $soldier_number;
        }else{
            $res['id'] = array_search($fd,$select);
            $res['myself_info'] = $random_battle[$res['id']];
        }
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







