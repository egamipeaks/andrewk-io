@extends('layouts.base')

@section('content')
<div class="max-w-2xl mx-auto">
    <article class="max-w-none">
        <header class="mb-8">
            <time class="text-gray-600 dark:text-gray-400">
                {{ \Carbon\Carbon::parse($publishedAt)->format('F j, Y') }}
            </time>
        </header>

        <div class="blog-content max-w-none">
            {!! $content !!}
        </div>
    </article>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Highlight.js for code syntax highlighting
    hljs.highlightAll();
});
</script>
@endsection
