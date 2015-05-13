<?php

class TournamentsController extends BaseController {

	public function index() {
	
		$this->listing();
	}
	
	public function listing() {
		
		$tournaments = Tournament::orderBy("created_at", "DESC")->get();
		
		$this->display('tournaments.table', [
			'tournaments' => $tournaments,
		]);
	}
	
	public function ranking($tournament) {
		
		$players = $tournament->orderedPlayers()->get();
		
		return View::make('players.ranking', array('players' => $players));
	}
	
	public function show($tournament) {
	
		$players = $tournament->orderedPlayers()->get();
		
		$this->display(array('rounds.table', 'players.ranking'), array(
			'tournament' => $tournament,
			'players' => $players,
		)
		);
	}
	
	public function getCreate() {
				
		$term = Input::get('term', '');
		$players =  Auth::user()
				->playersButFantom()
				->where('name', 'LIKE', '%'.$term.'%')
				->orderBy('name')
				->get();
				
                $maps = Auth::user()->maps;
				
		$this->display(array('tournaments.create'), array(
			'players' => $players,
			'tournament' => new Tournament,
			'tournamentPlayers' => array(),
			'maps' => $maps,
		));
		
	}
	
	public function postCreate() {
		
		$tournament = new Tournament;
		$tournament->user = Auth::user()->id;
		
		return App::make('TournamentsController')->postUpdate($tournament);
	}
	
	public function continuous() {
	
		$ids = array_keys(Input::get('tournaments', []));
		$tournaments = Tournament::WhereIn('id', $ids)->get();
		$players = Player::all();
		
		$tournaments->each(function($t) use(&$players) {
			foreach($t->orderedPlayers()->get() as $ranking => $player) {
				$players->find($player->id)->addTournamentScore($ranking);
			}
		});
		
		$players = $players->filter(function($player) {
			return $player->ts > 0;
		})->sortByDesc('ts');
		
		$this->display('tournaments.continuous', [
			'players' => $players,
		]);
		
	}
	
	public function getUpdate($tournament) {
	
		if($tournament->user != Auth::user()->id) return Redirect::to('tournaments');
		
		$tournamentPlayers = array();
		foreach($tournament->players as $player) {
			$tournamentPlayers[] = $player->id;
		}
				
		$this->display(array('tournaments.update', 'players.management'), array(
			'players' => Auth::user()->playersButFantom()->get(),
			'tournament' => $tournament,
			'tournamentPlayers' => $tournamentPlayers
		));
	}
	
	public function postUpdate($tournament) {
	
		if($tournament->user != Auth::user()->id) return Redirect::to('tournaments');
	
		$tournament->name = Input::get('name');
		$maps = array_keys(Input::get('maps', []));
		$players = array_keys(Input::get('players.ids', []));
		$names = array_keys(Input::get('players.names', []));
		if(is_null($tournament->name)) {
                        return Redirect::back();
		}
		
		$total = count($names) + count($players);
		if(ceil($total / 2) != count($maps)) {
                        return Redirect::back();
		}
		
		$tournament->save();
		$tournament->maps()->detach();
		foreach($maps as $map) {
			$tournament->maps()->attach($map);
		}
		
		$tournament->players()->detach();
		foreach($players as $player) {
			$tournament->players()->attach($player);
		}
		
		foreach($names as $name) {
			$player = new Player;
			$player->name = $name;
			$player->user = Auth::user()->id;
			$player->save();
			
			$tournament->players()->attach($player->id);
		}
		
		if($total % 2 != 0) {
			$tournament->players()->attach(User::fantom()->id);
		}
		
		return Redirect::to('tournaments/'.$tournament->id);
	}
	
	public function delete($tournament) {
	
		if($tournament->user != Auth::user()->id) return Redirect::to('tournaments');
		
		$tournament->delete();
		return Redirect::back();
	}
}

?>
 
