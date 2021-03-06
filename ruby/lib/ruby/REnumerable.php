<?php 

namespace Ruby;

require_once 'REnumerator.php';
require_once 'RHash.php';

/**
 * http://ruby-doc.org/core-1.9.3/Enumerable.html
 *
 * @package default
 * @author Koen Punt
 */
class Enumerable implements Countable{
		
	protected 
		$_enumerable = null;
	
	public function __construct($enumerable){
		if(is_array($enumerable)){
			$this->_enumerable = $enumerable;
		}elseif(is_string($enumerable)){
			/*
				TODO If $enumerable is string parse as RRange
			*/
			#$this->_range = new RRange($enumerable);
			#$this->_enumerable = $this->_range->to_a();
			if(preg_match('/(?<start>\d+)\.\.(?<end>\d+)/', $enumerable, $matches)){
				$this->_enumerable = range(intval($matches['start']), intval($matches['end']));
			}elseif(preg_match('/(.* ?)*/', $enumerable)){
				$this->_enumerable = explode(' ', $enumerable);
			}
			
		}
	}
	
	
	public function all__($block = false){
		if(!$block){
			$block = function($entry){
				return (!is_null($entry) && $entry !== false);
			};
		}
		$all = array_filter($this->_enumerable, $block);
		return count($all) == count($this->_enumerable);
	}
	
	public function any__($block = false){
		if(!$block){
			$block = function($entry){
				return (!is_null($entry) && $entry !== false);
			};
		}
		$all = array_filter($this->_enumerable, $block);
		return count($all) > 0;
	}
	
	public function chunk($initial_state_or_block, $block = false){
		/*
			TODO Implement initial_state
		*/
		
		if(!$block){
			$block = $initial_state_or_block;
		}else{
			$initial_state = $initial_state_or_block;
		}
		$chunks = array();
		$chunk = null;
		$last_value = null;
		foreach($this->_enumerable as $entry){
			$current_value = call_user_func($block, $entry);
			if($last_value != $current_value){
				$last_value = $current_value;
				if($chunk){
					array_push($chunks, $chunk);
				}
				$chunk = array();
			}
			array_push($chunk, $entry);
		}
		array_push($chunks, $chunk);
		
		return new REnumerable($chunks);
	}
	
	public function collect($block = false){
		if(!$block){
			return new REnumerator($this->_enumerable);
		}
		
		return array_map($block, $this->_enumerable);
	}
	
	public function collect_concat($block = false){
		$result = array();
		$stack = array();
		array_push($stack, array("", $this->_enumerable));

		while (count($stack) > 0) {
			list($prefix, $array) = array_pop($stack);
			foreach ($array as $key => $value) {
				$new_key = $prefix . strval($key);
				if (is_array($value)){
					array_unshift($stack, array($new_key, $value));
				}else{
					array_push($result, $value);
				}
			}
		}
		if(!$block){
			return new REnumerator($result);
		}
		
		return $result;
	}
	
	public function count(){
		return count($this->_enumerable);
	}
	
	public function cycle($cycles_or_block, $block = false){
		if(!$block){
			$cycles = -1;
			$block = $cycles_or_block;
		}else{
			$cycles = $cycles_or_block;
		}
		$entries = $this->_enumerable;
		$result = array();
		for($i = $cycles ; $i !== 0 ; $i--){
			foreach($entries as $entry){
				array_push($result, call_user_func($block, $entry));
			}
		}
		return $result;
	}
	
	public function detect($ifnone = null, $block = false){
		$result = null;
		foreach($this->_enumerable as $entry){
			if(call_user_func($block, $entry)){
				$result = $entry; 
				break;
			}
		}
		if($result){
			return $result;
		}
		if(is_callable($ifnone)){
			return call_user_func($ifnone);
		}
	}
	
	public function drop($n){
		return array_slice($this->_enumerable, $n);
	}
	
	public function drop_while($block){
		if(!$block){
			return $this;
		}
		$result = array();
		$i = -1;
		foreach($this->_enumerable as $entry){
			$i ++;
			$result = call_user_func($block, $entry);
			if(!$result || is_null($result)) break;
		}
		return $this->drop($i);
	}
	
	public function each_cons($n, $block = false){
		if(!$block){
			$result = array();
			$block = function($entry) use (&$result){
				array_push($result, $entry);
			};
		}
		$count = count($this->_enumerable);
		$count = $count - ($n % $count) + 1;
		for($i = 0; $i < $count ; $i ++){
			call_user_func($block, array_slice($this->_enumerable, $i, $n));
		}
		
		return $result ? new REnumerator($result) : null;
	}
	
	public function each_entry(){
		/**
		 * Not implementable? see: http://ruby-doc.org/core-1.9.3/Enumerable.html#method-i-each_entry
		 *
		 * @author Koen Punt
		 */
	}
	
	public function each_slice($n, $block = false){
		#if($n <= 0)throw new ArgumentError('invalid slice slice');
		$slices = array_chunk($this->_enumerable, $n);
		if($block){
			foreach($slices as $_slice){
				call_user_func($block, $_slice);
			}
			return null;
		}
		
		return new REnumerator($slices);
		
	}
	
	public function each_with_index($args_or_block = null, $block = false){
		if(!$block){
			if(is_callable($args_or_block)){
				$args = array();
				$block = $args_or_block;
			}else{
				$args = is_null($args_or_block) ? array() : $args;
				$result = array();
				$block = function($entry, $key) use (&$result){
					$result[$entry] = $key;
				};
			}
		}
		
		foreach($this->_enumerable as $key => $entry){
			call_user_func_array($block, array($entry, $key) + $args);
		}
		
		return $result ? new REnumerator($result) : null;
	}
	
	public function each_with_object($object, $block = false){
		if(!$block){
			$result = array();
			$block = function($entry, $memo_obj) use (&$result){
				array_push($result, array($entry, $memo_obj));
			};
		}
		
		foreach($this->_enumerable as $entry){
			call_user_func($block, $entry, &$object);
		}
		return $result ? new REnumerator($result) : $object;
		
	}
	
	public function entries(){
		return $this->_enumerable;
	}
	
	public function find($ifnone = null, $block = false){
		return $this->detect($ifnone, $block);
	}
	
	public function find_all($block = false){
		if(!$block){
			return new REnumerator($this->_enumerable);
		}
		$result = array();
		foreach($this->_enumerable as $entry){
			if(call_user_func($block, $entry)){
				array_push($result, $entry);
			}
		}
		return $result;
	}
	
	public function find_index($value_or_block = null, $block = false){
		if(!is_null($value_or_block)){
			if(is_callable($value_or_block)){
				$block = $value_or_block;
			}else{
				$value = $value_or_block;
			}
		}
		if($block){
			/*
				TODO Revise
			*/
			$value = current(array_filter($this->_enumerable, $block));
		}
		
		return array_search($value, $this->_enumerable);
	}
	
	public function first($n = false){
		if(!count($this->_enumerable)){
			return null;
		}
		return $n ? array_slice($this->_enumerable, 0, $n) : reset($this->_enumerable);
	}
	
	public function flat_map($block = false){
		return $this->collect_concat($block);
	}
	
	public function grep($pattern, $block = false){
		#(1..100).grep 38..44   #=> [38, 39, 40, 41, 42, 43, 44]
		#c = IO.constants
		#c.grep(/SEEK/)         #=> [:SEEK_SET, :SEEK_CUR, :SEEK_END]
		#res = c.grep(/SEEK/) {|v| IO.const_get(v) }
		#res                    #=> [0, 1, 2]
	}
	
	public function group_by($block = false){
		if(!$block){
			return new REnumerator($this->_enumerable);
		}
		$result = array();
		foreach($this->_enumerable as $entry){
			$_result = call_user_func($block, $entry);
			if($result[$_result]){
				array_push($result[$_result], $entry);
			}else{
				$result[$_result] = array($entry);
			}
		}
		return new RHash($result);
	}
	
	public function include__($object){
		
	}
	public function inject(){
		
	}
	
	public function map($block = false){
		return $this->collect($block);
	}
	
	public function max(){
		
	}
	public function max_by(){
		
	}
	public function member__($object){
		return include__($object);
	}
	public function min(){
		
	}
	public function min_by(){
		
	}
	public function minmax(){
		
	}
	public function minmax_by(){
		
	}
	public function none__(){
		
	}
	public function one__(){
		
	}
	public function partition(){
		
	}
	public function reduce(){
		
	}
	public function reject(){
		
	}
	public function reverse_each(){
		
	}
	public function select($block = false){
		return $this->find_all($block);
	}
	public function slice_before(){
		
	}
	public function sort(){
		
	}
	public function sort_by(){
		
	}
	public function take($n){
		return array_slice($this->_enumerable, 0, $n);
	}
	public function take_while(){
		
	}
	
	public function to_a(){
		return $this->entries();
	}
	
	public function zip(){
		
	}
	
}
