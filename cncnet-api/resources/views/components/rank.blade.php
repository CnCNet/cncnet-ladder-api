<?php
$showType = isset($showType) ? $showType: false; 
$size = isset($size) ? $size: "1x";  // e.g 1x, 2x.
?>
<div class="player-badge badge-{{$size}}">
    <img src="/images/badges/{{ $badge . ".png" }}">
    @if($showType)
    <p class="lead text-center">{{ $type}}</p>
    @endif
</div>