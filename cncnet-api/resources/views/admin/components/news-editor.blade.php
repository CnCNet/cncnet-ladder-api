@section('head')
    <!-- Main Quill library -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
@endsection

<div class="news-editor">
    <form action="/admin/news/save" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="body">
        <input type="hidden" name="id" value="{{ $news->id or '' }}" />

        <div class="form-group">
            <label for="title">News title</label>
            <input type="text" name="title" placeholder="Title" class="form-control" id="title" value="{{ $news->title or '' }}">
        </div>

        <div class="form-group">
            <label for="description">Short description</label>
            <input type="text" name="description" class="form-control" id="description" value="{{ $news->description or '' }}">
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
    <script type="module">
        import {
            ImageActions
        } from 'https://cdn.jsdelivr.net/npm/@xeger/quill-image-actions/lib/index.mjs';
        import {
            ImageFormats
        } from 'https://cdn.jsdelivr.net/npm/@xeger/quill-image-formats/lib/index.mjs';

        Quill.register('modules/imageActions', ImageActions);
        Quill.register('modules/imageFormats', ImageFormats);

        var quill = new Quill('#editor', {
            theme: 'snow',
            formats: [
                "align",
                "background",
                "blockquote",
                "bold",
                "code-block",
                "color",
                "float",
                "font",
                "header",
                "height",
                "image",
                "italic",
                "link",
                "script",
                "strike",
                "size",
                "underline",
                "width"
            ],
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{
                        'header': 2
                    }, {
                        'header': 3
                    }],
                    ['link'],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    ['image'] // Include the image button in the toolbar
                ],
                imageActions: {},
                imageFormats: {},
            }
        });

        // Retrieve the content from Quill and set it to a hidden input before submitting the form
        var form = document.querySelector('form');
        form.onsubmit = function() {
            var editorContent = document.querySelector('input[name=body]');
            editorContent.value = quill.root.innerHTML;
        };

        // Get the default content from Laravel and set it in the editor
        var defaultContent = {!! json_encode($news->body ?? '') !!};
        quill.setContents(quill.clipboard.convert(defaultContent));
    </script>
@endsection
