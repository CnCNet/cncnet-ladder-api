<div class="duplicate-accounts">
<?php if($user->ip_address_id != null) :?>
    <h5>Duplicates/Shared accounts:</h5> 

    <?php $ips = \App\IpAddressHistory::where("ip_address_id", $user->ip_address_id)->get(); ?>
    
    <ul class="list-styled">
    <?php foreach($ips as $ip): ?>
        <?php $u = \App\User::where("id", $ip->user_id)
            ->where("id", "!=", $user->id)
            ->first(); 
        ?>
        @if($u != null)
        <li>
            <a href="?userId={{$u->id}}">{{ $u->name }}</a> - {{ $u->email }}
        </li>
        @endif
    <?php endforeach; ?>
    </ul>

    <h5>Ip address:</h5> 
    <ul class="list-styled">
    <?php foreach($user->ipHistory as $ipHistory): ?>
        <li>
            <div>
                @if($hostname == "true")
                <strong>Hostname: {{ gethostbyaddr ($ipHistory->ipaddress->address) }}</strong>
                @endif
            </div> 
            <a href="https://www.whoismyisp.org/ip/{{ $ipHistory->ipaddress->address }}" target="_blank">
                {{ $ipHistory->ipaddress->address }}
            </a>
        </li>
    <?php endforeach; ?>
    </ul>

<?php endif; ?>
</div>