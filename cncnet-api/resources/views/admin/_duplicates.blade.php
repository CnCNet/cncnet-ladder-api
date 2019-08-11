<div class="duplicate-accounts">
<?php if($user->ip_address_id != null) :?>
    <h5>Duplicates/Shared accounts:</h5> 
    <?php $duplicates = \App\IpAddressHistory::where("ip_address_id", $user->ip_address_id)->get(); ?>
    
    <ul class="list-styled">
    <?php foreach($duplicates as $duplicate): ?>
        <?php $u = \App\User::where("id", $duplicate->user_id)
            ->where("id", "!=", $user->id)
            ->first(); 
        ?>

        @if($u != null)
        <li>
            {{ $u->name }} - {{ $u->email }}
            <br/>
            IP: {{ $duplicate->ipaddress->address }} -- Country: {{ $duplicate->ipaddress->country }}
        </li>
        @endif
        
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
</div>