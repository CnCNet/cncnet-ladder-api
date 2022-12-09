<?php
$loop = isset($loop) ? $loop : '';
$src = isset($src) ? $src : '';
$width = isset($width) ? $width : '';
$height = isset($height) ? $height : '';
$speed = isset($speed) ? $speed : '1';
?>

<lottie-player src="{{ $src }}" {{ $loop == 'true' ? 'loop' : '' }} style="width: {{ $width }}; height: {{ $height }};" speed="{{ $speed }}" autoplay
    background="transparent">
</lottie-player>
