<?php
$size = isset($size) ? $size : 150;
$type = isset($type) ? $type : 'player';
?>

<div class="avatar-container" style="width: {{ $size }}px; height: {{ $size }}px;">
    <div class="avatar" style="background-image: url('{{ $avatar }}');">
        @if (!$avatar)
            @if ($type == 'player')
                <span class="material-symbols-outlined icon">
                    person
                </span>
            @else
                <i class="bi bi-flag-fill"></i>
            @endif
        @endif
    </div>
</div>
