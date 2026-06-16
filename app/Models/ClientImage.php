<?php
// app/Models/ClientImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientImage extends Model
{
    use HasFactory;

    // Define fillable fields
    protected $fillable = ['our_client_id', 'image_path'];

    /**
     * Inverse relationship with the OurClient model.
     * A ClientImage belongs to one Client.
     */
    public function ourClient()
    {
        return $this->belongsTo(Client::class);
    }
}
    