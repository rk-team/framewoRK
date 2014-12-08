<?php

namespace rk\helper;

class image {
	
	/**
	 * @desc create thumbs for file
	 * @param string $relativePath : relative of the file 
	 * @param array $resizes : array that describe resizes (ex : array('icon' => array('width' => 50, 'height' => 50))) 
	 * @throws \rk\exception
	 */
	public static function createThumbs ($relativePath, array $resizes = array()) {
		
		if (empty($resizes)) {
			//no resize get all the resize configuration
			$resizes = \rk\manager::getConfigParam('image.resizes', array());
		}
		
		foreach ($resizes as $key => $oneResize) {
			
			if (empty($key) || empty($oneResize['width']) || empty($oneResize['height'])) {
				throw new \rk\exception('wrong paramter for resize', array('name' => $oneResize));
			}
			
			$absPath = \rk\helper\fileSystem::getAbsolutePath($relativePath);
			
			$image = new \Imagick($absPath);			
			$imageDesc = $image->identifyImage();
			
			//If resize param is bigger than desc, we create a new image with the old one in the middle
			if (($imageDesc['geometry']['width'] < $oneResize['width']) 
				&& ($imageDesc['geometry']['height'] < $oneResize['height'])) {
				
				$newImage = new \Imagick();
				$newImage->newImage($oneResize['width'], $oneResize['height'], 'none');
				$newImage->compositeImage($image, \Imagick::COMPOSITE_DEFAULT, 
						$oneResize['width']/2 - $imageDesc['geometry']['width']/2,
						$oneResize['height']/2 - $imageDesc['geometry']['height']/2);
				
				$newImage->setColorspace(\Imagick::COLORSPACE_RGB);
				$newImage->setImageFormat($image->getImageFormat());
				$image = $newImage;
			} else {
				$image->resizeImage($oneResize['width'], $oneResize['height'], \Imagick::FILTER_BOX, 1, true);
			}
			
			$fileName = self::getPathForThumbs($relativePath, $key);						
			$image->writeImage(\rk\helper\fileSystem::getAbsolutePath($fileName));
		}
	}
	
	/**
	 * @desc return the relative path for a given thumbName
	 * @param string $relativePath relativePath of the src file
	 * @param string $thumbName thumb name
	 */
	public static function getPathForThumbs($relativePath, $thumbName) {
		$pos = strrpos($relativePath, '.');
		if ($pos !== false) {
			$res = substr($relativePath, 0, $pos) . '_' . $thumbName . substr($relativePath, $pos);
		} else {
			$res = $relativePath . '_' . $thumbName;
		}
		
		return $res;
	}
		
	/**
	 * use to remove all files created createThumbs and the src file
	 * @param string $relativePath : relative path for image file
	 */
	public static function removeFiles ($relativePath) {
		
		$path = \rk\helper\fileSystem::getAbsolutePath($relativePath);
		$srcFileName = basename($relativePath);
		
		if (file_exists($path)) {
			unlink($path);
		}
		
		$entries = \rk\helper\fileSystem::scandir(dirname($path));
		foreach ($entries as $oneEntry) {
			if (strpos($oneEntry, $srcFileName . '_') === 0) {
				unlink(dirname($path) . '/' . $oneEntry);
			}
		}
	}
}
