<?php
session_start();

$images = array();

define('DS', DIRECTORY_SEPARATOR);
define('WWW_ROOT', dirname(dirname(__FILE__)) . DS);
define('WWW', dirname(dirname($_SERVER['SCRIPT_NAME'])));


if(isset($_GET['id']) && isset($_GET['width']) && isset($_GET['height']) && isset($_GET['id'])){
    extract($_GET);
    if(preg_match('/^[a-z\-0-9]+$/', $type) && preg_match('/^[0-9]{2,4}$/', $width) && preg_match('/^[0-9]{2,4}$/', $height)){

        // On vide le dossier au délà des 5 Go d'images par thème pour éviter la saturation
        $folder = WWW_ROOT . 'img' . DS . $type . DS . 'thumbs';
        if( $_SERVER['SERVER_NAME'] != 'localhost' && preg_match('/([0-9]+)(\t.*)/', shell_exec("du -s $folder"),$match) ){
            $size = $match[1] / 1000;
            if($size > 5000){
                shell_exec("rm -r $folder/*.jpg");
            }
        }


        // On génère l'image
        $id = $id * 1;
        $available = array();
        if($id > 10 || $id <= 0){
            if(!isset($_SESSION[$type])){
                $_SESSION[$type] = array();
            }
            $diff = array_diff($available, $_SESSION[$type]);
            if(empty($diff)){
                $diff = $available;
                $_SESSION[$type] = array();
            }
            $id = array_rand($diff);
        }
        $img = resize("img/$type/$id.jpg", $width, $height);
        header('Content-Type: image/jpeg');
        echo file_get_contents(WWW_ROOT . $img);
    }
}

function resize($file, $width, $height){

        # We find the right file
        $pathinfo   = pathinfo(trim($file, '/'));
        $file       = WWW_ROOT . trim($file, '/');
        $output     = $pathinfo['dirname'] . '/thumbs/' . $pathinfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathinfo['extension'];

        if(!file_exists($file)){
            die("Cette image n'existe pas");
        }

        if (!file_exists(WWW_ROOT . $output)) {

            # Setting defaults and meta
            $info                         = getimagesize($file);
            list($width_old, $height_old) = $info;

            # Create image ressource
            switch ( $info[2] ) {
                case IMAGETYPE_GIF:   $image = imagecreatefromgif($file);   break;
                case IMAGETYPE_JPEG:  $image = imagecreatefromjpeg($file);  break;
                case IMAGETYPE_PNG:   $image = imagecreatefrompng($file);   break;
                default: return false;
            }

            # We find the right ratio to resize the image before cropping
            $heightRatio = $height_old / $height;
            $widthRatio  = $width_old /  $width;
            if($heightRatio < 1 || $widthRatio < 1){
                die('The Image size is too large');
            }

            $optimalRatio = $widthRatio;
            if ($heightRatio < $widthRatio) {
                $optimalRatio = $heightRatio;
            }
            $height_crop = ($height_old / $optimalRatio);
            $width_crop  = ($width_old  / $optimalRatio);

            # The two image ressources needed (image resized with the good aspect ratio, and the one with the exact good dimensions)
            $image_crop = imagecreatetruecolor( $width_crop, $height_crop );
            $image_resized = imagecreatetruecolor($width, $height);

            # This is the resizing/resampling/transparency-preserving magic
            if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
                $transparency = imagecolortransparent($image);
                if ($transparency >= 0) {
                    $transparent_color  = imagecolorsforindex($image, $trnprt_indx);
                    $transparency       = imagecolorallocate($image_crop, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                    imagefill($image_crop, 0, 0, $transparency);
                    imagecolortransparent($image_crop, $transparency);
                    imagefill($image_resized, 0, 0, $transparency);
                    imagecolortransparent($image_resized, $transparency);
                }elseif ($info[2] == IMAGETYPE_PNG) {
                    imagealphablending($image_crop, false);
                    imagealphablending($image_resized, false);
                    $color = imagecolorallocatealpha($image_crop, 0, 0, 0, 127);
                    imagefill($image_crop, 0, 0, $color);
                    imagesavealpha($image_crop, true);
                    imagefill($image_resized, 0, 0, $color);
                    imagesavealpha($image_resized, true);
                }
            }

            imagecopyresampled($image_crop, $image, 0, 0, 0, 0, $width_crop, $height_crop, $width_old, $height_old);
            imagecopyresampled($image_resized, $image_crop, 0, 0, ($width_crop - $width) / 2, ($height_crop - $height) / 2, $width, $height, $width, $height);

            # Writing image according to type to the output destination and image quality
            switch ( $info[2] ) {
              case IMAGETYPE_GIF:   imagegif($image_resized, WWW_ROOT . $output, 80);    break;
              case IMAGETYPE_JPEG:  imagejpeg($image_resized, WWW_ROOT . $output, 100);   break;
              case IMAGETYPE_PNG:   imagepng($image_resized, WWW_ROOT . $output, 9);    break;
              default: return false;
            }
            chmod(WWW_ROOT . $output, 0777);

        }

        return '/' . $output;
    }