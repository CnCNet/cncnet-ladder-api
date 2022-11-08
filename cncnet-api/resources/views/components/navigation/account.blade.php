<div class="nav-item nav-account">
    @if (!\Auth::user())
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <div class="icon">
                @include('icons.user', ['colour' => '#00ff8a'])
            </div>

            <span class="nav-item-text">
                Account
            </span>

            <span class="caret"></span>
        </a>

        <ul class="dropdown-menu">
            <li><a href="/auth/login">Login</a></li>
        </ul>
    @else
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <div class="icon">
                @include('icons.user', ['colour' => '#00ff8a'])
            </div>

            <span class="nav-item-text">
                {{ \Auth::user()->name }}
            </span>

            <span class="caret"></span>
        </a>

        <ul class="dropdown-menu">
            @if (\Auth::user()->canEditAnyLadders())
                <li>
                    <a href="/admin/">Admin</a>
                </li>
            @endif
            <li><a href="/account">Manage account</a></li>
            <li><a href="/auth/logout">Sign out</a></li>
        </ul>
    @endif

</div>
