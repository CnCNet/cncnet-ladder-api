@section('head')
    <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
@endsection

<div class="news-editor">
    <form action="/admin/news/save" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="body">
        <input type="hidden" name="id" value="{{ $news->id ?? '' }}" />

        <div class="form-group">
            <label for="title">News title</label>
            <input type="text" name="title" placeholder="Title" class="form-control" id="title" value="{{ $news->title ?? '' }}">
        </div>

        <div class="form-group">
            <label for="description">Short description</label>
            <input type="text" name="description" class="form-control" id="description" value="{{ $news->description ?? '' }}">
        </div>

        <div class="form-group">
            <label for="featured_image">Featured image</label>
            @if (isset($news))
                <div class="featured-image mb-2 mt-2">
                    <img src="{{ $news->getFeaturedImagePath() }}" alt="Featured image" />
                </div>
            @endif
            <input type="file" name="featured_image" class="form-control" />
        </div>

        <div class="form-group">
            <label for="editor">Full post description</label>
            <div id="editor" style="height:300px"></div>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@section('scripts')
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'link',
                    '|',
                    'bulletedList',
                    'numberedList',
                    '|',
                    'undo',
                    'redo',
                    '|',
                    'image'
                ],
                image: {
                    // Configure image options if needed
                },
                language: 'en', // Change this to the appropriate language code
            })
            .then(editor => {
                var form = document.querySelector('form');
                form.onsubmit = function() {
                    var editorContent = document.querySelector('input[name=body]');
                    editorContent.value = editor.getData();
                };

                // Get the default content from Laravel and set it in the editor
                var defaultContent = {!! json_encode($news->body ?? '') !!};
                editor.setData(defaultContent);
            })
            .catch(error => {
                console.error(error);
            });
    </script>
@endsection
