<?php
namespace core\tools\form
{
	use core\system\Image;
	use core\utils\SimpleRandom;

	/**
	 * Class Captcha
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package tools
	 * @subpackage form
	 */
	class Captcha
	{
		/**
		 * @type string
		 */
		const DEFAULT_FONT = "includes/libs/core/tools/form/font.LinLibertine.ttf";

		/**
		 * @type string
		 */
		const SESSION_VAR_NAME = "captcha";

		/**
		 * @var int
		 */
		public $width = 100;

		/**
		 * @var int
		 */
		public $height = 30;

		/**
		 * @var string
		 */
		public $backgroundColor = "#ffffff";

		/**
		 * @var bool
		 */
		public $transparent = false;

		/**
		 * @var int
		 */
		public $rotation = 15;

		/**
		 * @var array
		 */
		private $fonts = array(Captcha::DEFAULT_FONT);

		/**
		 * @var array
		 */
		private $fontColors = array();

		/**
		 * @var int
		 */
		public $fontSizeMax = 13;

		/**
		 * @var int
		 */
		public $fontSizeMin = 13;

		/**
		 * @var
		 */
		private $length;

		/**
		 * @var string
		 */
		private $name = "";

		/**
		 * @var string
		 */
		private $value;


		/**
		 * Constructor
		 * @param int $pLength
		 * @param string $pName
		 */
		public function __construct($pLength, $pName)
		{
			$this->length = $pLength;
			$this->name = $pName;
			$this->value = SimpleRandom::string($this->length);
		}


		/**
		 * @param string $pColor  Format #rrggbb
		 * @return void
		 */
		public function addFontColor($pColor)
		{
			$this->fontColors[] = $pColor;
		}


		/**
		 * @param string $pTTFFile
		 * @return void
		 */
		public function addFontFace($pTTFFile)
		{
			$this->fonts[] = $pTTFFile;
		}


		/**
		 * @return string
		 */
		public function getValue()
		{
			return $_SESSION[self::SESSION_VAR_NAME][$this->name];
		}


		/**
		 * @return void
		 */
		public function unsetSessionVar()
		{
			unset($_SESSION[self::SESSION_VAR_NAME][$this->name]);
			if(empty($_SESSION[self::SESSION_VAR_NAME]))
				unset($_SESSION[self::SESSION_VAR_NAME]);
		}


		/**
		 * @return void
		 */
		public function render()
		{
			if(empty($this->fontColors))
				$this->fontColors[] = "#000000";
			if(!$this->fontSizeMax)
				$this->fontSizeMax = 12;
			if(!$this->fontSizeMin)
				$this->fontSizeMin = 12;
			$distance = $this->width/$this->length;
			$_SESSION[self::SESSION_VAR_NAME][$this->name]=$this->value;
			$img = new Image($this->width, $this->height, Image::PNG, 1);
			if(!$this->transparent)
				$img->beginFill(hexdec(substr($this->backgroundColor, 1,2)), hexdec(substr($this->backgroundColor, 3,2)), hexdec(substr($this->backgroundColor, 5,2)));
			$img->drawRectangle(0, 0, $this->width, $this->height);
			$img->endFill();
			$value = $this->getValue();
			for($i = 0, $max = strlen($value); $i<$max;$i++)
			{
				$c = $this->fontColors[rand(0, count($this->fontColors)-1)];
				$f = $this->fonts[rand(0, count($this->fonts)-1)];
				$s = rand($this->fontSizeMin, $this->fontSizeMax);
				$img->drawText(substr($value, $i, 1), $s, $f, ($distance/4) + $i*$distance, $s + (($this->height-$s)/2), hexdec(substr($c, 1,2)),hexdec(substr($c, 3,2)),hexdec(substr($c, 5,2)), rand(-$this->rotation,$this->rotation));
			}
			$img->render();
		}
	}
}
