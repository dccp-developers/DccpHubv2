<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE TABLE "rooms" (
    "id" bigint unsigned NOT NULL AUTO_INCREMENT,
    "name" varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    "class_room" varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    "created_at" timestamp NULL DEFAULT NULL,
    "updated_at" timestamp NULL DEFAULT NULL,
    PRIMARY KEY ("id")
) */
final class rooms extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'name',
        'class_code',
    ];

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'room_id', 'id');
    }

    public function getSchedulesAttribute()
    {
        return $this->schedules()->get();
    }
}
