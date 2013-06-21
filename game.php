<?php 
/* 

TODO 
- validate incoming moves, e.g. "c3,g5" gets through
- ineficient endgame checking: checks both players, instead of 
  only the one, who just played

*/
class Check {
	private $a, $b, $n;
	private $ALPHA, $letters, $last_col, $last_row;
	private $first_col_a, $first_col_b;
	private $graph;
	public $res;
	public $winner;

	public function __construct ( $a, $b, $n ){

		$this->a = $a;
		sort($this->a);
		$this->b = $b;
		$this->n = $n;
		$this->ALPHA = range('a', 'z');
		$this->letters = range('a', $this->ALPHA[ $n -1 ]);
		$this->last_col = $this->ALPHA[ $n - 1 ];
		$this->last_row = $this->n;
	}

	private function possible_neighbours($field) {
		$x = $field{0};
		$x_num = array_search($x, $this->ALPHA);
		$y = substr($field, 1);
		$y_num = (int) $y;
	
		$res = [];
	
		if ($x_num - 1 >= 0 && $y_num - 1 > 0)
			$res[] = $this->ALPHA[$x_num - 1] . strval($y_num - 1);
		if ( $y_num - 1 > 0)
			$res[] = $this->ALPHA[$x_num] . strval($y_num - 1);
	
		if ( $x_num + 1 < $this->n)
			$res[] = $this->ALPHA[$x_num + 1] . strval($y_num);
	
		if ( $x_num + 1 < $this->n && $y_num + 1 <= $this->n)
			$res[] = $this->ALPHA[$x_num + 1] . strval($y_num + 1);
	
		if ( $y_num + 1 <= $this->n)
			$res[] = $this->ALPHA[$x_num] . strval($y_num + 1);
	
		if ( $x_num - 1 >= 0)	
			$res[] = $this->ALPHA[$x_num - 1] . strval($y_num);
	
		return $res;
	}

	private function get_first () {
		foreach ($this->a as $m) {
			if ( $m{0} == 'a')
				$this->first_col_a[] = $m;
			else
				break;
		}
		
		foreach ( $this->b as $m) {
			if ( substr($m, 1) == '1')
				$this->first_col_b[] = $m;
		}
	}

	private function build_graph ( $moves ) {
		sort($moves);
		foreach ($moves as $move) {
			$neighbrs = array_intersect($this->possible_neighbours($move, $this->n), $moves);
			$this->graph[ $move ] = array('neighbrs' => $neighbrs, 'visited' => false );	
		}
	}

	private function check ( $move , $list = array(), $depth = 0) {
		$depth++;
		$list[] = $move;
		if ( $move{0} == $this->last_col ){ 
			//return $list;
			$this->res = $list;
		}
	
		if ( !$this->graph[ $move ]['visited'] ){
			$this->graph[ $move ]['visited'] = true;/*
			echo $depth; echo 'a: ';
			print_r($list);
			echo ( $move . '<br>');*/
			foreach( $this->graph[$move]['neighbrs'] as $m ){
				$this->check( $m, $list, $depth );
			}
		}
	}

	private function check_b ( $move , $list = array(), $depth = 0) {
		$depth++;
		$list[] = $move;
		if ( substr($move, 1) == $this->last_row ){ 
			$this->res = $list;
		}
	
		if ( !$this->graph[ $move ]['visited'] ){
			$this->graph[ $move ]['visited'] = true;/*
			echo $depth; echo 'b: ';
			print_r($list);
			echo ( $move . '<br>');*/
			foreach( $this->graph[$move]['neighbrs'] as $m ){
				$this->check_b( $m, $list, $depth );
			}
		}
	}

	public function end () {
		$this->get_first();

		$this->build_graph($this->a);
		// can't win without column a
		if (sizeof($this->first_col_a) > 0){
			foreach ($this->first_col_a as $a_move) {
				$this->check($a_move);
				if (sizeof($this->res) > 0){
					$this->winner = 'P1';
					return true;
				}
			}
		}

		$this->graph = array();
		$this->build_graph($this->b);
		if (sizeof($this->first_col_b) > 0){
			foreach ($this->first_col_b as $b_move){
				$this->check_b($b_move);
				if (sizeof($this->res) > 0){
					$this->winner = 'P2';
					return true;
				}
			}
		}
		return false;
	}
}

function precheck ( $mvs ) {
	global $game_data;
	if (sizeof($mvs) < $game_data['size']){
		return false;
	}
	build( $mvs, $game_data['size'] );
	return true;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function p1starts(){
	if (rand(0,1) == 0)
		return true;
	return false;
}

// is it player 1's turn?
function p1turn(){
	global $game_data;

	$c = count($game_data['P1_moves']) - count($game_data['P2_moves']);
	if ($c == 1){
		return false;
	}
	if ($c == 0){
		return $game_data['P1_starts'];
	}
	if ($c == -1){
		return true;
	}
}

// checks validity of a move: if its in game field and whether this move has been made before
function valid_move( $m ){
	global $ALPHA, $game_data;
	if ( strcmp($m , $ALPHA[$game_data['size']] ) < 0 
		&& !in_array($m, $game_data['P1_moves']) 
		&& !in_array($m, $game_data['P2_moves'])) {
		return true;
	}
	return false;
}

// if player doesn't have an id yet, we will create it now
if (!isset($_COOKIE['id'])){
	$r = generateRandomString();
	setcookie('id', $r );
	$_COOKIE['id'] = $r;
}

$ALPHA = range('a', 'z');
$gameid = '';
$playerid = htmlspecialchars($_COOKIE['id']);
$move = '';
$winner = null;
$winning_moves = null;
$resp = array();

try {
	$gameid = $_GET['g'];
	if ( isset($_GET['m']) )
		$move = $_GET['m'];
} catch (Exception $e) {
	echo $e->getMessage();
}

// some file handling here
$filepath = 'game_files/' . $gameid . '.json';
$game_file = fopen($filepath, 'r') or die($filepath . 'game doesnt exist');
$game_json = fread($game_file, filesize($filepath));
fclose($game_file);
$game_data = json_decode($game_json, true);

$letters = range('a', $ALPHA[ $game_data['size'] -1 ]);
$last = $ALPHA[ $game_data['size'] - 1];

// first player connecting to game
if ( strlen($game_data['P1_id']) == 0 && strlen($game_data['P2_id'] ) == 0 ) {
	$game_data['P1_id'] = $playerid;
	$game_data['in_progress'] = false;
}
// second player connecting 
elseif ( strlen($game_data['P2_id'] ) == 0  &&  $playerid != $game_data['P1_id'] ) {
	$game_data['P2_id'] = $playerid;
	$game_data['P1_starts'] = p1starts();
	$game_data['in_progress'] = true;
}

else { 
	if ( strlen($game_data['P1_id']) > 0 && strlen($game_data['P2_id']) > 0 
		&& $game_data['in_progress']){
	// game has already started, check if its this player's turn  
		if ( valid_move( $move ) && strlen($move) != 0) {
			if (p1turn() && $playerid == $game_data['P1_id']){
				$game_data['P1_moves'][] = $move;
			}
			elseif (!p1turn() && $playerid == $game_data['P2_id']){
				$game_data['P2_moves'][] = $move;
			}
		}
	}
}




// prepare and send response

// check for winners
if (sizeof($game_data['P1_moves']) >= $game_data['size'] 
	|| sizeof($game_data['P2_moves']) >= $game_data['size']){
	$checker = new Check($game_data['P1_moves'],
						$game_data['P2_moves'], $game_data['size']);
	if ($checker->end()){
		$resp['winner'] = $checker->winner;
		$resp['winning_moves'] = $checker->res;
		$game_data['in_progress'] = false;
	}
}

// is this player's turn:
// isP1'sTurn?  XNOR  isThisP1? 
$A = p1turn();
$B = $playerid == $game_data['P1_id'];

if (( $A && $B ) || ( !$A && !$B )) { 
	$resp['your_turn'] = true;
}
else {
	$resp['your_turn'] = false;
}

// is this Player 1?
$resp['P1'] = $B;

if (strlen($game_data['P2_id']) == 0) {
	$resp['game_started'] = false;
	$resp['your_turn'] = null;
}
else {
	$resp['game_started'] = true;
}

$resp['P1_moves'] = $game_data['P1_moves'];
$resp['P2_moves'] = $game_data['P2_moves'];


// write and close files
$game_file = fopen($filepath, 'w') or die($filepath . 'game doesnt exist');
fwrite($game_file, json_encode($game_data));
fclose($game_file);

// finally send response
echo json_encode($resp);

?>