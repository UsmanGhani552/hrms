<?php

namespace App\Models;

use App\Traits\ImageUploadTrait;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use ImageUploadTrait;
    protected $fillable = [
        'user_id',
        'document',
        'date',
    ];
    protected $appends = ['document_url'];
    public function getDocumentUrlAttribute() {
        if($this->document){
            return asset('images/payrolls/'.$this->document);
        }
        return null;
    }

    public static function createPayroll(array $data) {
        $data['document'] = (new self())->uploadImage(request(),'document', 'images/payrolls');
        return self::create($data);
    }

    public function updatePayroll(array $data) {
        $data['document'] = (new self())->uploadImage(request(),'document', 'images/payrolls', "images/payrolls/{$this->document}", $this->document);
        return $this->update($data);
    }


    public function user() {
        return $this->belongsTo(User::class);
    }
}
