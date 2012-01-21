<?php
namespace  PhpBit;

abstract class Num {

	/**
	 * @var int
	 */
	protected $val;

	public function __construct($val){
		if(is_string($val)) $val = bindec($val);
		$this->val = ($val&$this->getValMask());
	}

	public function __toString(){
		return $this->toS();
	}

	public function toS(){
		$str = decbin($this->val);
		$str = str_pad($str, $this->getValSize(), "0",STR_PAD_LEFT);
		return $str;
	}

	public function toHexString($split=0){
		$str = sprintf("%X",$this->val);
		$str = str_pad($str, $this->getValSize()/4, "0",STR_PAD_LEFT);
		if($split !== 0) $str = chunk_split($str,$split," ");
		return $str;
	}

	public function toN(){
		return $this->val;
	}

	public function setBit($pos, $val){
		if($pos > $this->getValSize())throw new Exception("Invalid position value");
		$mask = pow(2, ($this->getValSize()-$pos));
		($val)?$this->val|=$mask:$this->val&=(~$mask);
	}

	public function invert(){
		$this->val=~($this->val);
		$this->val&=$this->getValMask();
	}

	public function shiftLeft($num=1){
		if($num > $this->getValSize()-1) throw new Exception("Invalid shift value");
		$this->val <<= $num;
		$this->val&=$this->getValMask();
	}

	public function shiftRight($num=1){
		if($num > $this->getValSize()-1) throw new Exception("Invalid shift value");
		$this->val >>= $num;
		$this->val&=$this->getValMask();
	}

	public function makeOr(Num $a){
		$this->val |= $a->toN();
	}

	public function makeAnd(Num $a){
		$this->val &= $a->toN();
	}

	public function makeXor(Num $a){
		$this->val ^= $a->toN();
	}

	/**
	 * @abstract
	 * @return int
	 */
	abstract function getValMask();
	/**
	 * @abstract
	 * @return int
	 */
	abstract function getValSize();

	/**
	 * @abstract
	 * @return string
	 */
	abstract function packVal($mode);

}

class Byte extends Num {

	function getValMask(){ return 0xff; }

	function getValSize(){ return 8; }

	function packVal($mode){ return pack("c",$this->val); }
}

class Word extends Num {

	function getValMask(){ return 0xffff; }

	function getValSize(){ return 16; }

	function packVal($mode){ return pack("n",$this->val); }

	public function __construct(){
		if(count($args = func_get_args()) == 2){
			$this->_constructor2($args[0], $args[1]);
		}else{
			parent::__construct($args[0]);
		}
	}

	protected function _constructor2(Byte $byte1, Byte $byte2){
		$concat = $byte1->toS().$byte2->toS();
		$this->val = bindec($concat);
	}
}


class Dword extends Num {

	function getValMask(){ return 0xffffffff; }

	function getValSize(){ return 32; }

	function packVal($mode){ return pack("N",$this->val); }

	public function __construct(){
		if(count($args = func_get_args()) == 4){
			$this->_constructor4($args[0], $args[1], $args[2], $args[3]);
		}elseif(count($args = func_get_args()) == 2){
			$this->_constructor2($args[0], $args[1]);
		}else{
			parent::__construct($args[0]);
		}
	}

	protected function _constructor2(Word $word1, Word $word2){
		$concat = $word1->toS().$word2->toS();
		$this->val = bindec($concat);
	}

	protected function _constructor4(Byte $byte1, Byte $byte2, Byte $byte3, Byte $byte4){
		$concat = $byte1->toS().$byte2->toS().$byte3->toS().$byte4->toS();
		$this->val = bindec($concat);
	}
}


class Stream {
	/**
	 * @var array
	 */
	protected $buff = array();

	const TO_HEX_STYLE_AUTO = 1;
	const TO_HEX_STYLE_WEB = 2;
	const TO_HEX_STYLE_CONSOLE = 3;
	const TO_STRING_STYLE_AUTO =0;
	const TO_STRING_STYLE_WEB =1;
	const TO_STRING_STYLE_CONSOLE =2;
	const PACK_MODE_BIGENDIAN = 0;
	const PACK_MODE_LITTLEENDIAN = 1;

	public function add(Num $num){
		$this->buff[] = $num;
	}

	public function __toString(){
		return $this->toS(self::TO_STRING_STYLE_AUTO);
	}

	public function toS(){
		$res = "";

		foreach($this->buff as $item){
			$res.=$item->toS();
		}

		$args = func_get_args();
		if(count($args) == 1){
			$style = $args[0];
			$args[0] = 8;
			$args[1] = '|';
			$args[2] = 72;
			if($style == self::TO_STRING_STYLE_AUTO){
				$style = (isset($_SERVER['argv']))?self::TO_STRING_STYLE_CONSOLE:self::TO_STRING_STYLE_WEB;
			}
			
			switch($style){
				case self::TO_STRING_STYLE_CONSOLE:
					$args[3] = "\n";
				break;
				case self::TO_STRING_STYLE_WEB:
					$args[3] = "<br />";
				break;
			}
		}
		
		if(count($args)>1){
			for($i=0;$i<(count($args)-1);$i+=2){
				$res = chunk_split($res, $args[$i],$args[$i+1]);
			}
		}
		
		return $res;
	}

	public function toHexString(){
		$pack = $this->pack();
		$res = unpack("H*",$pack);
		$res = strtoupper($res[1]);

		$args = func_get_args();

		if(count($args) == 1){
			$style = $args[0];
			$args[0] = 2;
			$args[1] = ' ';
			$args[2] = 12;
			$args[3] = '| ';
			$args[4] = 56;
			if($style == self::TO_HEX_STYLE_AUTO){
				$style = (isset($_SERVER['argv']))?self::TO_HEX_STYLE_CONSOLE:self::TO_HEX_STYLE_WEB;
			}
			switch($style){
				case self::TO_HEX_STYLE_CONSOLE:
					$args[5] = "\n";
				break;
				case self::TO_HEX_STYLE_WEB:
					$args[5] = "<br />";
				break;
			}
		}

		if(count($args)>1){
			for($i=0;$i<(count($args)-1);$i+=2){
				$res = chunk_split($res, $args[$i],$args[$i+1]);
			}
		}
		return $res;
	}

	public function pack($mode=self::PACK_MODE_BIGENDIAN){
		$res = "";
		foreach($this->buff as $item){
			$res.=$item->packVal($mode);
		}
		return $res;
	}
}