<?php

class Map{

	public $width;//地图的长度
	public $height;//地图的宽度
	public $point_length;//每一个坐标点的长度

	public  $coordinate;//地图的坐标标记


	public function __construct($width, $height, $point_length, $coordinate = false){
		$this->width = $width;
		$this->height = $height;
		$this->point_length = $point_length;
		if($coordinate == false){
			for($i = 0; $i< $width/$point_length; $i++){
			 	for($j = 0; $j< $height/$point_length; $j++){
					$this->coordinate[$i][$j] = -1;
				}
			}
		}else{
			$this->coordinate = $coordinate;
		}

	}

	//修改地图坐标点为已占用
	public function occupied_point($x,$y,$data){
		$this->coordinate[$x][$y] = $data;
	}

	//修改地图坐标点为已释放
	public function release_point($x,$y){
		$this->coordinate[$x][$y] = -1;
	}

}
