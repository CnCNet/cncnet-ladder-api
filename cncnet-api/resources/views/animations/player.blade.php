<?php
    $loop = isset($loop) ? $loop: "false"; 
    $src = isset($src) ? $src : ""; 
    $width = isset($width) ? $width :"";
    $height = isset($height) ? $height :"";
?> 

<lottie-player 
    src="{{ $src}}"  
    {{ $loop ? $loop : "" }}
    style="width: {{$width}}; height: {{$height}};"
    speed="1"
    autoplay
    background="transparent" 
>
</lottie-player>