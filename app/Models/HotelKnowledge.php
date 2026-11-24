<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelKnowledge extends Model
{
    protected $table = 'hotel_knowledge';

    protected $fillable = [
        'category',
        'title',
        'location',
        'operating_hours',
        'extra_fee',
        'content',
        'content_en',
        'image_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function categories(): array
    {
        return [
            'profile' => 'Profil Hotel',
            'facility' => 'Fasilitas Hotel',
            'restaurant' => 'Restoran & Dining',
            'hours' => 'Jam Operasional',
            'policy' => 'Aturan & Kebijakan',
            'transport' => 'Akses & Transportasi',
            'contact' => 'Kontak & Darurat',
            'faq' => 'FAQ Tamu',
        ];
    }

    public function getLocalizedContent(string $locale = 'id'): string
    {
        if ($locale === 'en' && !empty($this->content_en)) {
            return $this->content_en;
        }

        return $this->content;
    }
}
