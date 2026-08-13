<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'price' => $this->price,
            'publish_date' => $this->publish_date?->format('Y-m-d'),

            // ✅ Phase 2: إضافة language (كانت مفقودة في Phase 1)
            'language' => $this->language,
            'file_type' => $this->file_type,
            'duration' => $this->duration,

            // صورة الغلاف (عامة)
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,

            // رابط ملف الكتاب PDF/EPUB المحمي (يعود عبر الـ Stream Route)
            'pdf_url' => $this->file_path
                ? url('/api/books/' . $this->id . '/stream')
                : null,

            // رابط العينة الصوتية (عام)
            'audio_sample_url' => $this->audio_sample_path
                ? url('/api/books/' . $this->id . '/stream-sample')
                : null,

            // رابط الصوت الكامل المحمي
            'has_audio' => (bool) $this->audio_path,
            'audio_stream_url' => $this->audio_path
                ? url('/api/books/' . $this->id . '/stream-audio')
                : null,

            'categories' => $this->categories->map(function ($category) {
                return ['id' => $category->id, 'name' => $category->name];
            }),
            'authors' => $this->authors->map(function ($author) {
                return ['id' => $author->id, 'name' => $author->name];
            }),
        ];
    }
}
