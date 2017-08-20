<div class="game-box">
    <div class="preview" style="background-image:url(/images/maps/yr/8a815dd293f6eabe4e12721932ce47a38509b57d.png)">
        <a href="#" class="status status-{{ $status or "lost"}}"></a>
    </div>
 
    <a href="#" class="game-box-link">
        <div class="details text-center">
            <h4 class="title">{{ $title or "Box Title" }}</h4>
            <small class="status">Streaming</small>
        </div>
        <div class="footer text-center">
            <h5 class="player won">
                {{ $playerA or "Player A" }} <span class="points">+14</span>
            </h5>
            <p class="vs">vs</p>
            <h5 class="player lost">
                {{ $playerB or "Player B" }} <span class="points">+14</span>
            </h5>
        </div>
    </a>
</div>