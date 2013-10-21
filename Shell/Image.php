<?php // full of black magic :(

namespace Shell;

class Image {

	private $type;
	private $height;
	private $width;
	private $image;

	const GIF = IMG_GIF;
	const JPG = IMG_JPG;
	const PNG = IMG_PNG;
	const WBMP = IMG_WBMP;

	public function __construct($data = null) {
		if ($data != null) {
			$this->setImage($data);
		}
	}

	public function getType() {
		return $this->type;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getWidth() {
		return $this->width;
	}

	public function setImage($data) {
		list($this->width, $this->height, $this->type) = getimagesizefromstring($data);
		$this->image = imagecreatefromstring($data);
	}

	public function crop($left, $top, $right = 0, $bottom = 0) {
		$this->width = $this->width - ($left + $right);
		$this->height = $this->height - ($top + $bottom);
		$image = imagecreatetruecolor($this->width, $this->height);
		imagecopy($image, $this->image, 0, 0, $left, $top, $this->width, $this->height);
		imagedestroy($this->image);
		$this->image = $image;
	}

	public function hFlip() {
		$width = $this->width;
		$height = $this->height;
		$image = imagecreatetruecolor($width, $height);
		imagecopyresampled($image, $this->image, 0, 0, $width - 1, 0, $width, $height, -$width, $height); 
		imagedestroy($this->image);
		$this->image = $image;
	}

	public function resize($width, $height = null) {
		if ($width == null) {
			$width = ($this->width / $this->height) * $height;
		}
		if ($height == null) {
			$height = ($this->height / $this->width) * $width;
		}
		$image = imagecreatetruecolor($width, $height);
		imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		imagedestroy($this->image);
		$this->image = $image;
	}

	public function rotate() {
		$image = imagecreatetruecolor($this->height, $this->width);
		for ($i = 0; $i < $this->width; $i++) {
			for ($y = 0; $y < $this->height; $y++) {
				imagesetpixel($image, ($this->height - 1) - $y, $i, imagecolorat($this->image, $i, $y));
			}
		}
		imagedestroy($this->image);
		$this->image = $image;
		$this->height = $this->width;
		$this->width = $this->height;
	}

	public function vFlip() {
		$width = $this->width;
		$height = $this->height;
		$image = imagecreatetruecolor($width, $height);
		imagecopyresampled($image, $this->image, 0, 0, 0, $height - 1, $width, $height, $width, -$height);
		imagedestroy($this->image);
		$this->image = $image;
	}

	public function flush($filename = null) {
		$this->type = image_type_to_mime_type($this->type);
		if ($this->type == "image/gif") {
			if (!$filename) header("Content-Type: image/gif");
			imagegif($this->image, $filename);
		} else if ($this->type == "image/jpeg") {
			if (!$filename) header("Content-Type: image/jpeg");
			imagejpeg($this->image, $filename);
		} else if ($this->type == "image/png") {
			if (!$filename) header("Content-Type: image/png");
			imagepng($this->image, $filename);
		} else if ($this->type == "image/vnd.wap.wbmp") {
			if (!$filename) header("Content-Type: image/vnd.wap.wbmp");
			imagewbmp($this->image, $filename);
		}
		unset($this);
	}

	public function text($text, $font = null, $size = 20, $x = 0, $y = 20, $angle = 0, $color = null) {
		if ($color == null) {
			$color = imagecolorallocate($this->image, 0, 0, 0);
		} else {
			$color = imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
		}
		if ($font == null) {
			$config = new Config;
			$font = sprintf("%s/Fonts/FreeSans.ttf", $config->framework);
		}
		imagettftext($this->image, $size, $angle, $x, $y, $color, $font, $text);
	}

	public function __destruct() {
		if ($this->image) {
			imagedestroy($this->image);			
		}
	}

}
