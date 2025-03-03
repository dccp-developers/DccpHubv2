<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE TABLE `guest_guardian_contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emergencycontactname` varchar(100) DEFAULT NULL,
  `emergencycontactphone` bigint(20) DEFAULT NULL,
  `emergencycontactaddress` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci */
final class GuardianContact extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'guest_guardian_contact';

    protected $primaryKey = 'id';

    protected $fillable = [
        'emergencycontactname',
        'emergencycontactphone',
        'emergencycontactaddress',
    ];

    public function enrollee()
    {
        return $this->belongsTo(GuestEnrollment::class);
    }
}
