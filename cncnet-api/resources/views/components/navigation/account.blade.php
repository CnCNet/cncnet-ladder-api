@php $user = \Auth::user(); @endphp
<a href="#" class="link-dark text-decoration-none dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
    @if ($user && $user->getUserAvatar())
        @include('components.avatar', ['avatar' => $user->getUserAvatar(), 'size' => 32])
    @else
        @include('icons.user', ['colour' => '#00ff8a'])
    @endif
</a>

<ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" style="">
    @if ($user)
        @if ($user->canEditAnyLadders())
            <li>
                <a href="/admin/" class="dropdown-item">Admin</a>
            </li>
        @endif
        <li><a href="/account" class="dropdown-item">Manage account</a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li><a href="/auth/logout" class="dropdown-item">Logout</a></li>
    @else
        <li><a href="/auth/login" class="dropdown-item">Login</a></li>
        <li><a href="/auth/register" class="dropdown-item">Register account</a></li>
    @endif
</ul>
