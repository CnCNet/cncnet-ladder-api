@php $user = \Auth::user(); @endphp

<li class="nav-item m-xl-auto">
    <a href="#" class="dropdown-toggle d-flex align-items-center nav-link me-1 ms-1 ps-2 pe-4" data-bs-toggle="dropdown" aria-expanded="false">
        @if ($user && $user->getUserAvatar())
            @include('components.avatar', ['avatar' => $user->getUserAvatar(), 'size' => 32])
        @else
            <span class="material-symbols-outlined icon">
                person
            </span>
        @endif

        <span class="ps-2 ms-2 me-2 text d-block d-md-none fw-bold">
            @if ($user)
                {{ $user->name }}
            @else
                Your account
            @endif
        </span>
    </a>

    <ul class="dropdown-menu dropdown-menu-end">
        @if ($user)
            <li>
                <h4 class=" dropdown-header text-uppercase">Hi {{ $user->name }}</h4>
            </li>

            <li><a href="/account" class="dropdown-item">Your Account</a></li>
            <li><a href="/account/settings" class="dropdown-item">Account Settings</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            @if ($user->canEditAnyLadders())
                <li>
                    <a href="/admin/" class="dropdown-item">Admin</a>
                </li>
            @endif
            <li><a href="/auth/logout" class="dropdown-item">Logout</a></li>
        @else
            <li>
                <h4 class=" dropdown-header text-uppercase">Ladder Account</h4>
            </li>
            <li><a href="/auth/login" class="dropdown-item">Login</a></li>
            <li><a href="/auth/register" class="dropdown-item">Register account</a></li>
        @endif
    </ul>
</li>
