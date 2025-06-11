@if ($paginator->lastPage() > 1)
    <nav aria-label="Paginate ladder results" style="overflow:hidden;">
        <ul class="pagination">

            @php
                $prevIndex = 1;
                $nextIndex = 2;
                
                if ($paginator->currentPage() > 1) {
                    $prevIndex = $paginator->currentPage() - 1;
                }
                
                if ($paginator->currentPage() < $paginator->lastPage()) {
                    $nextIndex = $paginator->currentPage() + 1;
                }
                
                $limitCount = $paginator->currentPage() + 10;
                if ($limitCount >= $paginator->lastPage()) {
                    $limitCount = $paginator->lastPage();
                }
            @endphp

            <li class="page-item {{ $paginator->currentPage() == 1 ? ' disabled' : '' }}">
                <a href="{{ $paginator->url(1) }}" class="page-link">
                    <i class="bi bi-arrow-bar-left"></i>
                </a>
            </li>

            <li class="page-item {{ $paginator->currentPage() == 1 ? ' disabled' : '' }}">
                <a href="{{ $paginator->url($prevIndex) }}" class="page-link">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            @for ($i = $paginator->currentPage(); $i <= $limitCount; $i++)
                <li class="page-item {{ $paginator->currentPage() == $i ? ' active' : '' }}">
                    <a href="{{ $paginator->url($i) }}" class="page-link">{{ $i }}</a>
                </li>
            @endfor

            <li class="page-item {{ $paginator->currentPage() == $paginator->lastPage() ? ' disabled' : '' }}">
                <a href="{{ $paginator->url($nextIndex) }}" class="page-link">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>

            <li class="page-item {{ $paginator->currentPage() == $paginator->lastPage() ? ' disabled' : '' }}">
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="page-link">
                    <i class="bi bi-arrow-bar-right"></i>
                </a>
            </li>
        </ul>
    </nav>
@endif
