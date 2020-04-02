<?php
namespace core\utils
{
	use \ArrayAccess;
	use \Iterator;
	use \Countable;

	/**
	 * @author NICOLAS Arnaud <arno06@gmail.com>
	 * @version 1.0
	 * @package core\utils
	 */
	class Stack implements ArrayAccess, Iterator, Countable
	{
		private $source;

		public function __construct($pStack = null)
		{
			if(!is_null($pStack))
				$this->source = $pStack;
			else
				$this->source = array();
		}

		public function indexOf($pNeedle, $pStrict = false)
		{
			if(empty($this->source))
				return -1;
			$index = -1;
			foreach($this->source as $k=>$v)
			{
				if($pStrict===true && $v === $pNeedle)
					$index = $k;
				else if ($v == $pNeedle)
					$index = $k;
				if($index > -1)
					break;
			}
			return $index;
		}

		public function extract()
		{
			$keys = func_get_args();
			$r = array();
			for($i = 0, $max = count($keys);$i<$max;$i++)
			{
				if(!isset($this->source[$keys[$i]]))
					continue;
				$r[$keys[$i]] = $this->source[$keys[$i]];
			}
			return $r;
		}

		public function cast($pClassName)
		{
			foreach($this->source as &$j)
			{
				$i = new $pClassName();
				foreach($j as $n=>$v)
				{
					if(property_exists($pClassName, $n))
						$i->$n = $v;
				}
				$j = $i;
			}
		}

		public function each($pFunction, $pContext = null, $pRef = false)
		{
			//TBD
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Offset to unset
		 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
		 * @param mixed $offset <p>
		 * The offset to unset.
		 * </p>
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			unset($this->source[$offset]);
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Offset to set
		 * @link http://php.net/manual/en/arrayaccess.offsetset.php
		 * @param mixed $offset <p>
		 * The offset to assign the value to.
		 * </p>
		 * @param mixed $value <p>
		 * The value to set.
		 * </p>
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			if(is_null($offset))
				$this->source[] = $value;
			else
				$this->source[$offset] = $value;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Offset to retrieve
		 * @link http://php.net/manual/en/arrayaccess.offsetget.php
		 * @param mixed $offset <p>
		 * The offset to retrieve.
		 * </p>
		 * @return mixed Can return all value types.
		 */
		public function offsetGet($offset)
		{
			return $this->source[$offset];
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Whether a offset exists
		 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
		 * @param mixed $offset <p>
		 * An offset to check for.
		 * </p>
		 * @return boolean Returns true on success or false on failure.
		 * </p>
		 * <p>
		 * The return value will be casted to boolean if non-boolean was returned.
		 */
		public function offsetExists($offset)
		{
			return isset($this->source[$offset]);
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Count elements of an object
		 * @link http://php.net/manual/en/countable.count.php
		 * @return int The custom count as an integer.
		 * </p>
		 * <p>
		 * The return value is cast to an integer.
		 */
		public function count()
		{
			return count($this->source);
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Rewind the Iterator to the first element
		 * @link http://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 */
		public function rewind()
		{
			reset($this->source);
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Checks if current position is valid
		 * @link http://php.net/manual/en/iterator.valid.php
		 * @return boolean The return value will be casted to boolean and then evaluated.
		 * Returns true on success or false on failure.
		 */
		public function valid()
		{
			return $this->current() !== false;
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the key of the current element
		 * @link http://php.net/manual/en/iterator.key.php
		 * @return scalar scalar on success, integer
		 * 0 on failure.
		 */
		public function key()
		{
			return key($this->source);
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Move forward to next element
		 * @link http://php.net/manual/en/iterator.next.php
		 * @return void Any returned value is ignored.
		 */
		public function next()
		{
			next($this->source);
		}

		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the current element
		 * @link http://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 */
		public function current()
		{
			return current($this->source);
		}

		/**
		 * Permet d'accéder rapidement &agrave; une valeur présente dans un tableau par rapport &agrave; ses clés :
		 *
		 * $ar = array("key"=>array("key2"=>array("key3"=>"une valeur)));
		 *
		 * $value = Stack::get("key.key2.key3", $ar); //une valeur
		 *
		 * @static
		 * @param string $pId
		 * @param array $pStack
		 * @return mixed
		 */
		static public function get($pId, &$pStack)
		{
			$value = $pStack;
			$keys = explode(".", $pId);
			foreach($keys as &$k)
			{
				if(!isset($value[$k]))
					return null;
				$value = $value[$k];
			}
			return $value;
		}


        /**
         * @param $pValue
         * @param $pKey
         * @param $pArray
         * @return bool
         */
		static public function inArrayKey($pValue ,$pKey, &$pArray)
		{
			foreach($pArray as &$obj)
			{
				if($obj[$pKey] == $pValue)
					return true;
			}
			return false;
		}
	}
}
