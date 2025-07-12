<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use League\CommonMark\CommonMarkConverter;

class BlogController extends Controller
{
    public function show(string $slug): View
    {
        $filePath = resource_path("blog/{$slug}.md");

        if (! file_exists($filePath)) {
            abort(404);
        }

        $markdown = file_get_contents($filePath);
        $converter = new CommonMarkConverter;

        $title = $this->extractTitle($markdown);

        $content = $converter->convert($markdown)->getContent();

        return view('blog.show', [
            'title' => $title,
            'content' => $content,
            'publishedAt' => $this->getFileDate($filePath),
        ]);
    }

    private function extractTitle(string $markdown): string
    {
        if (preg_match('/^# (.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }

        return 'Blog Post';
    }

    private function getFileDate(string $filePath): string
    {
        return date('Y-m-d', filemtime($filePath));
    }
}
