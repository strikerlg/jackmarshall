<?php

class Game extends Eloquent {

	public function tournament() {
	
		return $this->round()->tournament();
	}
	
	public function round() {
	
		return $this->belongsTo('Round', 'round')->first();
	}
	
	public function reports() {
	
		return $this->hasMany('Report', 'game');
	}
	
	public function user() {
	
		return $this->tournament()->user();
	}
}
 
