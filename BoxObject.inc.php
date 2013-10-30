<?php
class Cubic{
	public $dimen;
	public $storedVolume;
	public $sumOfSides;
	
	private $direction;
	
	private function dimension_Swap(){
		# swaps permutations of XYZ. you'll go through all the possible
		# permutations of XYZ in 6 cycles. If you go more you'll just start repeating.
		/* Here's the order of the changes, top being a direction of 1, bottom a direction of 6
		 * XYZ
		 * XZY
		 * ZXY
		 * ZYX
		 * YZX
		 * YXZ
		 */
		if ($this->direction % 2 == 0){ #even, XY swap
			$temp = $this->dimen['y'];
			$this->dimen['y'] = $this->dimen ['x'];
			$this->dimen['x'] = $temp;
		}else{ #odd, YZ swap
			$temp = $this->dimen['y'];
			$this->dimen['y'] = $this->dimen ['z'];
			$this->dimen['z'] = $temp;
		}
		$this->direction++;
	}
	
	private function volume($dimens){
		# Find the volume of the box.
		$volume = 1;
		foreach($dimens as $col){
			$volume *= $col;
		}
		return $volume;
	}
	
	private function sum_Box_Dimens($dimens){
		# Add up the total dimensions of the box.
		$sum = 0;
		foreach($dimens as $col){
			$sum += $col;
		}
		return $sum;
	}
	
	private function dimension_Check($dimen){
		# determines whether the item can fit inside the container given as $dimen;
		if ($dimen['x']<=$this->dimen['x']){
			if ($dimen['y']<=$this->dimen['y']){
				if ($dimen['z']<=$this->dimen['z']){
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	
	public function can_Fit($dimen){
		
		# given $dimen, returns true if the item can fit inside this cubic.
		for($i=0; $i<6 ; $i++){
			
			if($this->dimension_Check($dimen)){ # if it fits, break out of the function.
				return TRUE;
			}else{ # otherwise, swap the dimensions.
				$this->dimension_Swap();
				
			}
		}
		return FALSE;
	}
	
	function __construct($dimen){
		$this->dimen = $dimen;
		$this->storedVolume = $this->volume($dimen);
		$this->sumOfSides = $this->sum_Box_Dimens($dimen);
		$this->direction = 0;
	}
	
}

class Box extends Cubic{
	public $totalWeight;
	public $availableSubBoxes = array();
	public $boxedItem;
	private $size;
	
	public function transfer_Items(&$returnArray){
		# recurse through all boxed items, modify the passed array to contain all the items.
		if($this->boxedItem == NULL){
			return;
		}else{
			array_push($returnArray,$this->boxedItem->dimen);
			foreach($this->availableSubBoxes as $box){
				$box->transfer_Items($returnArray);
			}
			
		}
	}
	public function size_Of_Subs(){
		
		if($this->boxedItem == NULL){
			print_r($this->size);
		}else{
			
			foreach($this->availableSubBoxes as $box){
				$box->size_Of_Subs();
			}
			print_r($this->size);
		}
	}
	public function store_Item($toStore){ # accepts an array and makes a cubic.
		//print_r ($toStore);
		# make toStore into a cubic.
		$item = new Cubic($toStore);
		
		if ($this->boxedItem == NULL && $this->can_Fit($item->dimen)){
			//print "<h2>Item Stored</h2>";
			# if there are no sub boxes, and the item fits here, then insert
			$this->boxedItem = $item;
			$this->divide_Box($item);
			//print "<br>stored <br>"/*."<b>";print_r($item);print"</b>"*/;
			return TRUE;
		}else{
			
			foreach($this->availableSubBoxes as $box){ # for every sub box
				if($box->store_Item($toStore)){ # attempt to store item recursively.
					return TRUE;
				}
			}
		}
		return FALSE; # Item can't fit, and the sub boxes couldn't fit either.
	}
	
	public function divide_Box($item){
		# slices box into 3 boxes.
		$box = $this->dimen;
		$itemDimen = $item->dimen;
		/*
		 *  b1 x = x-xi; y = y; z = zi;
		 *  b2 x = xi; y = y-yi; z = z;
		 *  b3 x = xi; y = yi; z = z-zi; 
		 */
		
		# sub divide boxes.
		$subBox1 = new Box(array(
							'x'=>$box['x']-$itemDimen['x'], 
							'y'=>$box['y'], 
							'z'=>$itemDimen['z']));
		$subBox2 = new Box(array(
							'x'=>$itemDimen['x'], 
							'y'=>$box['y'] - $itemDimen['y'], 
							'z'=>$itemDimen['z']));
		$subBox3 = new Box(array(
							'x'=>$box['x'], 
							'y'=>$box['y'], 
							'z'=>$box['z'] - $itemDimen['z']));
		
		# insert the new boxes, then sort by maximum capacity
		array_push($this->availableSubBoxes , $subBox1,$subBox2,$subBox3);
		
		
		do{ #Bubble Sort. Not a very big list of boxes
			$swapped = FALSE;
			for($i= 0; $i < sizeof($this->availableSubBoxes)-1 ; $i++ ){
				if (($this->availableSubBoxes[$i]->storedVolume > $this->availableSubBoxes[$i+1]->storedVolume)){
					$temp = $this->availableSubBoxes[$i+1];
					$this->availableSubBoxes[$i+1] = $this->availableSubBoxes[$i];
					$this->availableSubBoxes[$i] = $temp;	
					$swapped = TRUE;
				}
			}
		}while ($swapped == TRUE);
	}

	

	function __toString(){
		if ($this->boxedItem == NULL){
			return"";
		}else{
			print "<br>item sized: ".$this->boxedItem->storedVolume." cubic inches stored;";
			foreach($this->availableSubBoxes as $box){
				print $box; # magic string recursion!
			}
		}
		return "";
	}
	function __construct($dimen){
		parent::__construct($dimen);
		$this->size = $dimen;
		$this->boxedItem = NULL;
	}
}


class BoxManager{
	# this class is a shipping manager and controls Boxes.
	public $box;
	private $boxSizes = array();
	private $size;
	
	public function get_Box_Size(){
		return $this->boxSizes[$this->size];
	}
	
	public function increase_Box_Size(){
		//print "increasing_Box_Size(".($this->size).")<br>";
		# increases box size, returns an array of items which don't fit.
		$temp = array();
		$this->box->transfer_Items($temp);
		$this->size++;
		
		if($this->size < sizeof($this->boxSizes) - 1){
			$this->box = new Box($this->boxSizes[$this->size]);
		}else{
			# cannot increase size
			return FALSE;
		}
		foreach($temp as $item){
			$this->box->store_Item($item);
		}
	}
	public function decrease_Box_Size(){
		//print "decreasing_Box_Size(".($this->size).")<br>";
		# returns an array of items which don't fit.
		$temp = array();
		$this->box->transfer_Items($temp);
		if($this->size>0){
			$this->size--;
		}else{
			# cannot decrease size
			return FALSE;
		}
		foreach($temp as $item){
			$this->box->store_Item($item);
		}
	}
	
	
	public function insert_Item($item){
		#attempts to store items, if it works, then return true. Else, return the item that failed. 
		if($this->box->store_Item($item)){
			return TRUE;
			
		}else{
			if ($this->size < sizeof($this->boxSizes)){ #if larger boxes exist
				
				$j = 0;
				foreach($this->boxSizes as $box){
					$this->increase_Box_Size();
					if($this->box->store_Item($item)){
						//$this->box->store_Item($item);
						
						return TRUE;
					}
					$j++;
				}
				for($i = 0 ; $i < $j; $i++) {
					$this->decrease_Box_Size();
				}return $item;
			}
		}
		return $item;		
	}
	
	public function print_Stored(){
		print $this->box;
	}
	
	function __construct($sizes){
		$this->boxSizes = $sizes;
		$this->size = 0;
		$this->box = new Box($this->boxSizes[$this->size]);
		//print_r($this->box);
	}
}

class Tim{ # The best shipping manager in the world.
	private $boxSizes;
	private $items;
	private $boxes = array();
	public $itemsStored = array();
	public $numberOfBoxes;
	public $unstoredItems=array();
	public $overallWeight;
	
	public function print_Stored_Items(){
		//$i = 0;
		foreach($this->boxes as $box){
			//print "Box $i";
			$box->print_Stored();
			$i++;
		}
	}
	
	
	
	public function boxify($items){
		if(empty($items))
		{
			return;
		}
		$item = array_shift($items); #pop the item off the front.
		//print"inserting <br>";
		//print_r($item);
		foreach($this->boxes as $box){ # Try every Box
			$result = $box->insert_Item($item);
			
			if(is_array($result)){ # If insertion failed
				$tempBoxManager = new BoxManager($this->boxSizes); #try a different box
				
				if($tempBoxManager->insert_Item($item)){
					if(isset($tempBoxManager->box->boxedItem)){
						array_unshift($this->boxes , $tempBoxManager); # append the box to the end of the array;
					
						array_push($this->itemsStored,$item);
						$this->numberOfBoxes++;
						
					}else{
						$this->unstoredItems[]=$item;
					}
					return $this->boxify($items);
					
				}else{ # This item simply cannot fit inside any existing boxes! oops!
					#throw an exception
					//print "No boxes exist which can pack item:<br>";
					print_r($item);
					$this->unstoredItems[]=$item;
					return $this->boxify($items);
					
				}
				unset($tempBoxManager);
			}else if($result == TRUE){
				//Print "Item added!";
				array_push($this->itemsStored,$item);
				return $this->boxify($items);
			}
		}
		
	}
	private function volume($dimens){
		# Find the volume of the box.
		$volume = 1;
		foreach($dimens as $col){
			$volume *= $col;
		}
		return $volume;
	}
	function __construct($sizes,$items){
		
		do{ #Bubble Sort. Not a very big list of i
			$swapped = FALSE;
			for($i= 0; $i < sizeof($items)-1 ; $i++ ){
				if (($this->volume($items[$i]) < $this->volume($items[$i+1]))){
					$temp = $items[$i+1];
					$items[$i+1] = $items[$i];
					$items[$i] = $temp;	
					$swapped = TRUE;
				}
			}
		}while ($swapped == TRUE);
		
		do{ #Bubble Sort. Not a very big list of boxes
			$swapped = FALSE;
			for($i= 0; $i < sizeof($sizes)-1 ; $i++ ){
				if (($this->volume($sizes[$i]) > $this->volume($sizes[$i+1]))){
					$temp = $sizes[$i+1];
					$sizes[$i+1] = $sizes[$i];
					$sizes[$i] = $temp;	
					$swapped = TRUE;
				}
			}
		}while ($swapped == TRUE);
		
		
		
		$this->numberOfBoxes = 1;
		$this->boxSizes = $sizes;
		$this->boxes[] = new BoxManager($sizes);
		$this->boxify($items);
		print "<pre>";
		print_r($this->itemsStored);
		print "in ".$this->numberOfBoxes." boxes, sized:<br>";
		foreach($this->boxes as $box){
			print_r( $box->get_Box_Size() );
		}
		print "<br>The following Items were not packed<br>";
		print_r($this->unstoredItems);
		print "</pre>";
	}
}

# A demo array of boxes, standard sizes Uline produces
$ulineBoxes = array(
	array('x'=> 20,'y'=>20,'z'=>20),
	array('x'=> 20,'y'=>20,'z'=>6),
	array('x'=> 20,'y'=>20,'z'=>4),
	array('x'=> 2,'y'=>20,'z'=>14),
	array('x'=> 18,'y'=>18,'z'=>12),
	array('x'=> 18,'y'=>12,'z'=>12),
	array('x'=> 12,'y'=>12,'z'=>12),
	array('x'=> 14,'y'=>10,'z'=>6),
	array('x'=> 12,'y'=>12,'z'=>3),
	array('x'=> 14,'y'=>12,'z'=>3),
	array('x'=> 18,'y'=>6,'z'=>6),
	array('x'=> 12,'y'=>6,'z'=>6),
	array('x'=> 16,'y'=>16,'z'=>6),
	array('x'=> 4,'y'=>4,'z'=>36),
	array('x'=> 36,'y'=>8,'z'=>8),
	array('x'=> 12,'y'=>6,'z'=>6),
	array('x'=> 6,'y'=>6,'z'=>6),
	array('x'=> 14,'y'=>12,'z'=>6)
);

# A Demo array of boxes, standard sizes for USPS
$uspsFlatrateBoxs = array(
	0=>array('x'=> 8.5,'y'=>5.25,'z'=>1.5),
	1=>array('x'=> 11,'y'=>8.5,'z'=>5.5),
	2=>array('x'=> 13.5,'y'=>11.75,'z'=>3.25),
	3=>array('x'=> 12,'y'=> 12,'z'=> 5.5)
);

/*$foo = new BoxObject($arrayOfDimens);
$item1 = array('x'=> 5,'y' => 3,'z' => 4);
$item2 = array('x'=> 4,'y' => 4,'z' => 4);
$item3 = array('x'=>4,'y' => 4, 'z' => 4);
$item4 = array('x'=>10,'y' => 22, 'z' => 3.5);
$item5 = array('x'=> 10,'y' => 5,'z'=>4);
//$item2 = array('x'=> 12,'y' => 3,'z' => 6);
$multiItem = array($item1,$item2,$item3,$item4,$item5);*/

# creating a test object.
$foo = new Tim($uspsFlatrateBoxs,$multiItem);
?>