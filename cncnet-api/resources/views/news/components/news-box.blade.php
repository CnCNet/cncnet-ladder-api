<div class="news-box" style="background-image: url( {{ $newsItem->getFeaturedImagePath() }})">
    <div class="news-container">
        <h5 class="news-title">{{ $newsItem->title }}</h5>
        <p class="news-description">{{ $newsItem->description }}</p>
        <a href="/news/{{ $newsItem->slug }}" class="btn btn-icon read-more">
            Read more
            <span class="material-symbols-outlined">
                navigate_next
            </span>
        </a>
    </div>
</div>
